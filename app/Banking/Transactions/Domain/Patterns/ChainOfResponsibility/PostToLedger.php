<?php

namespace App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility;

use App\Banking\Transactions\Domain\Contracts\TransactionRepository;
use App\Banking\Transactions\Domain\Contracts\LedgerRepository;
use App\Banking\Transactions\Domain\Contracts\AccountGateway;

use App\Banking\Transactions\Domain\Exceptions\TransactionRuleViolation;
use App\Banking\Transactions\Domain\Exceptions\InsufficientFunds;

final class PostToLedger implements TxStep
{
    public function __construct(
        private readonly TransactionRepository $txRepo,
        private readonly LedgerRepository $ledgerRepo,
        private readonly AccountGateway $accounts,
    ) {
    }

    public function handle(TxContext $ctx, callable $next): array
    {
        if ($ctx->outcome !== null) {
            return $ctx->outcome;
        }

        // Approval posting safety: لا تعيد posting لو ledger موجود
        if ($ctx->isApprovalPosting()) {
            if ($this->ledgerRepo->existsForTransaction($ctx->existingTransactionId)) {
                return [
                    'message' => 'تم ترحيل هذه المعاملة مسبقًا',
                    'transaction_public_id' => $ctx->existingTransactionPublicId,
                    'status' => 'posted',
                ];
            }
        }

        // deposit
        if ($ctx->isDeposit()) {
            $dest = $ctx->dest;
            if (!$dest) throw new TransactionRuleViolation('الحساب غير موجود');

            $before = $dest->balance;
            $after  = bcadd($before, $ctx->amount, 2);

            $tx = $this->txRepo->create([
                'initiator_user_id' => $ctx->initiatorUserId,
                'type' => 'deposit',
                'status' => 'posted',
                'source_account_id' => null,
                'destination_account_id' => $dest->id,
                'amount' => $ctx->amount,
                'currency' => $ctx->currency,
                'description' => $ctx->description,
                'posted_at' => now(),
            ]);

            $this->ledgerRepo->create([
                'transaction_id' => $tx->id,
                'account_id' => $dest->id,
                'direction' => 'credit',
                'amount' => $ctx->amount,
                'currency' => $ctx->currency,
                'balance_before' => $before,
                'balance_after' => $after,
            ]);

            $this->accounts->updateBalance($dest->id, $after);

            // update parent cached balance
            if ($dest->parentId) {
                $p = $this->accounts->lockByIdForUpdate($dest->parentId);
                if ($p) $this->accounts->updateBalance($p->id, bcadd($p->balance, $ctx->amount, 2));
            }

            return [
                'message' => 'تم الإيداع بنجاح',
                'transaction_public_id' => $tx->publicId,
                'status' => 'posted',
                'account_public_id' => $dest->publicId,
                'new_balance' => $after,
            ];
        }

        // withdraw
        if ($ctx->isWithdraw()) {
            $src = $ctx->source;
            if (!$src) throw new TransactionRuleViolation('الحساب غير موجود');

            $before = $src->balance;
            $after  = bcsub($before, $ctx->amount, 2);

            if (bccomp($after, '0.00', 2) < 0) {
                throw new InsufficientFunds('رصيد غير كافٍ');
            }

            $tx = $this->txRepo->create([
                'initiator_user_id' => $ctx->initiatorUserId,
                'type' => 'withdraw',
                'status' => 'posted',
                'source_account_id' => $src->id,
                'destination_account_id' => null,
                'amount' => $ctx->amount,
                'currency' => $ctx->currency,
                'description' => $ctx->description,
                'posted_at' => now(),
            ]);

            $this->ledgerRepo->create([
                'transaction_id' => $tx->id,
                'account_id' => $src->id,
                'direction' => 'debit',
                'amount' => $ctx->amount,
                'currency' => $ctx->currency,
                'balance_before' => $before,
                'balance_after' => $after,
            ]);

            $this->accounts->updateBalance($src->id, $after);

            if ($src->parentId) {
                $p = $this->accounts->lockByIdForUpdate($src->parentId);
                if ($p) $this->accounts->updateBalance($p->id, bcsub($p->balance, $ctx->amount, 2));
            }

            return [
                'message' => 'تم السحب بنجاح',
                'transaction_public_id' => $tx->publicId,
                'status' => 'posted',
                'account_public_id' => $src->publicId,
                'new_balance' => $after,
            ];
        }

        // transfer
        $src = $ctx->source;
        $dst = $ctx->dest;
        if (!$src || !$dst) throw new TransactionRuleViolation('حساب مصدر/وجهة غير موجود');

        $srcBefore = $src->balance;
        $srcAfter  = bcsub($srcBefore, $ctx->amount, 2);
        if (bccomp($srcAfter, '0.00', 2) < 0) {
            throw new InsufficientFunds('رصيد غير كافٍ');
        }

        $dstBefore = $dst->balance;
        $dstAfter  = bcadd($dstBefore, $ctx->amount, 2);

        // إذا posting من approval: استخدم tx الموجود
        if ($ctx->isApprovalPosting()) {
            $txId = $ctx->existingTransactionId;
            $txPublic = $ctx->existingTransactionPublicId;

            $this->ledgerRepo->create([
                'transaction_id' => $txId,
                'account_id' => $src->id,
                'direction' => 'debit',
                'amount' => $ctx->amount,
                'currency' => $ctx->currency,
                'balance_before' => $srcBefore,
                'balance_after' => $srcAfter,
            ]);

            $this->ledgerRepo->create([
                'transaction_id' => $txId,
                'account_id' => $dst->id,
                'direction' => 'credit',
                'amount' => $ctx->amount,
                'currency' => $ctx->currency,
                'balance_before' => $dstBefore,
                'balance_after' => $dstAfter,
            ]);

            $this->accounts->updateBalance($src->id, $srcAfter);
            $this->accounts->updateBalance($dst->id, $dstAfter);

            if ($src->parentId) {
                $p = $this->accounts->lockByIdForUpdate($src->parentId);
                if ($p) $this->accounts->updateBalance($p->id, bcsub($p->balance, $ctx->amount, 2));
            }
            if ($dst->parentId) {
                $p = $this->accounts->lockByIdForUpdate($dst->parentId);
                if ($p) $this->accounts->updateBalance($p->id, bcadd($p->balance, $ctx->amount, 2));
            }

            return [
                'message' => 'تم التحويل بنجاح',
                'transaction_public_id' => $txPublic,
                'status' => 'posted',
                'source_new_balance' => $srcAfter,
                'destination_new_balance' => $dstAfter,
            ];
        }

        // normal transfer: create posted tx
        $tx = $this->txRepo->create([
            'initiator_user_id' => $ctx->initiatorUserId,
            'type' => 'transfer',
            'status' => 'posted',
            'source_account_id' => $src->id,
            'destination_account_id' => $dst->id,
            'amount' => $ctx->amount,
            'currency' => $ctx->currency,
            'description' => $ctx->description,
            'posted_at' => now(),
        ]);

        $this->ledgerRepo->create([
            'transaction_id' => $tx->id,
            'account_id' => $src->id,
            'direction' => 'debit',
            'amount' => $ctx->amount,
            'currency' => $ctx->currency,
            'balance_before' => $srcBefore,
            'balance_after' => $srcAfter,
        ]);

        $this->ledgerRepo->create([
            'transaction_id' => $tx->id,
            'account_id' => $dst->id,
            'direction' => 'credit',
            'amount' => $ctx->amount,
            'currency' => $ctx->currency,
            'balance_before' => $dstBefore,
            'balance_after' => $dstAfter,
        ]);

        $this->accounts->updateBalance($src->id, $srcAfter);
        $this->accounts->updateBalance($dst->id, $dstAfter);

        if ($src->parentId) {
            $p = $this->accounts->lockByIdForUpdate($src->parentId);
            if ($p) $this->accounts->updateBalance($p->id, bcsub($p->balance, $ctx->amount, 2));
        }
        if ($dst->parentId) {
            $p = $this->accounts->lockByIdForUpdate($dst->parentId);
            if ($p) $this->accounts->updateBalance($p->id, bcadd($p->balance, $ctx->amount, 2));
        }

        return [
            'message' => 'تم التحويل بنجاح',
            'transaction_public_id' => $tx->publicId,
            'status' => 'posted',
            'source_new_balance' => $srcAfter,
            'destination_new_balance' => $dstAfter,
        ];
    }
}
