<?php

namespace App\Banking\Transactions\Domain\Services;

use Illuminate\Support\Facades\Config;

use App\Banking\Transactions\Application\DTOs\DepositData;
use App\Banking\Transactions\Application\DTOs\WithdrawData;
use App\Banking\Transactions\Application\DTOs\TransferData;
use App\Banking\Transactions\Application\DTOs\TransactionOutcome;

use App\Banking\Transactions\Domain\Contracts\AccountGateway;
use App\Banking\Transactions\Domain\Contracts\TransactionRepository;
use App\Banking\Transactions\Domain\Contracts\LedgerRepository;
use App\Banking\Transactions\Domain\Contracts\ApprovalRepository;

use App\Banking\Transactions\Domain\Enums\TransactionTypeEnum;
use App\Banking\Transactions\Domain\Enums\TransactionStatusEnum;
use App\Banking\Transactions\Domain\Enums\LedgerDirectionEnum;

use App\Banking\Transactions\Domain\Exceptions\TransactionRuleViolation;
use App\Banking\Transactions\Domain\Exceptions\InsufficientFunds;

final class TransactionProcessor
{
    public function __construct(
        private readonly AccountGateway $accounts,
        private readonly TransactionRepository $txRepo,
        private readonly LedgerRepository $ledgerRepo,
        private readonly ApprovalRepository $approvals,
    ) {
    }

    public function deposit(int $initiatorUserId, DepositData $data): TransactionOutcome
    {
        $currency = (string) Config::get('banking.currency', 'USD');

        $locked = $this->accounts->lockByPublicIdsForUpdate([$data->accountPublicId]);
        $account = $locked[$data->accountPublicId] ?? null;

        if (!$account) throw new TransactionRuleViolation('الحساب غير موجود');
        if ($account->type === 'group') throw new TransactionRuleViolation('لا يمكن الإيداع في حساب group');
        if ($account->state !== 'active') throw new TransactionRuleViolation('الحساب غير نشط لإجراء عملية');
        if (bccomp($data->amount, '0.00', 2) <= 0) throw new TransactionRuleViolation('المبلغ يجب أن يكون أكبر من صفر');

        $before = $account->balance;
        $after  = bcadd($before, $data->amount, 2);

        $tx = $this->txRepo->create([
            'initiator_user_id' => $initiatorUserId,
            'type' => TransactionTypeEnum::DEPOSIT->value,
            'status' => TransactionStatusEnum::POSTED->value,
            'source_account_id' => null,
            'destination_account_id' => $account->id,
            'amount' => $data->amount,
            'currency' => $currency,
            'description' => $data->description,
            'posted_at' => now(),
        ]);

        $this->ledgerRepo->create([
            'transaction_id' => $tx->id,
            'account_id' => $account->id,
            'direction' => LedgerDirectionEnum::CREDIT->value,
            'amount' => $data->amount,
            'currency' => $currency,
            'balance_before' => $before,
            'balance_after' => $after,
        ]);

        $this->accounts->updateBalance($account->id, $after);

        // تحديث group cached balance (parent)
        if ($account->parentId) {
            $parent = $this->accounts->lockByIdForUpdate($account->parentId);
            if ($parent) {
                $parentAfter = bcadd($parent->balance, $data->amount, 2);
                $this->accounts->updateBalance($parent->id, $parentAfter);
            }
        }

        return new TransactionOutcome(
            message: 'تم الإيداع بنجاح',
            transactionPublicId: $tx->publicId,
            status: $tx->status,
            data: [
                'account_public_id' => $account->publicId,
                'new_balance' => $after,
            ]
        );
    }

    public function withdraw(int $initiatorUserId, WithdrawData $data): TransactionOutcome
    {
        $currency = (string) Config::get('banking.currency', 'USD');

        $locked = $this->accounts->lockByPublicIdsForUpdate([$data->accountPublicId]);
        $account = $locked[$data->accountPublicId] ?? null;

        if (!$account) throw new TransactionRuleViolation('الحساب غير موجود');
        if ($account->type === 'group') throw new TransactionRuleViolation('لا يمكن السحب من حساب group');
        if ($account->state !== 'active') throw new TransactionRuleViolation('الحساب غير نشط لإجراء عملية');
        if (bccomp($data->amount, '0.00', 2) <= 0) throw new TransactionRuleViolation('المبلغ يجب أن يكون أكبر من صفر');

        $before = $account->balance;
        $after  = bcsub($before, $data->amount, 2);

        if (bccomp($after, '0.00', 2) < 0) {
            throw new InsufficientFunds('رصيد غير كافٍ');
        }

        $tx = $this->txRepo->create([
            'initiator_user_id' => $initiatorUserId,
            'type' => TransactionTypeEnum::WITHDRAW->value,
            'status' => TransactionStatusEnum::POSTED->value,
            'source_account_id' => $account->id,
            'destination_account_id' => null,
            'amount' => $data->amount,
            'currency' => $currency,
            'description' => $data->description,
            'posted_at' => now(),
        ]);

        $this->ledgerRepo->create([
            'transaction_id' => $tx->id,
            'account_id' => $account->id,
            'direction' => LedgerDirectionEnum::DEBIT->value,
            'amount' => $data->amount,
            'currency' => $currency,
            'balance_before' => $before,
            'balance_after' => $after,
        ]);

        $this->accounts->updateBalance($account->id, $after);

        if ($account->parentId) {
            $parent = $this->accounts->lockByIdForUpdate($account->parentId);
            if ($parent) {
                $parentAfter = bcsub($parent->balance, $data->amount, 2);
                $this->accounts->updateBalance($parent->id, $parentAfter);
            }
        }

        return new TransactionOutcome(
            message: 'تم السحب بنجاح',
            transactionPublicId: $tx->publicId,
            status: $tx->status,
            data: [
                'account_public_id' => $account->publicId,
                'new_balance' => $after,
            ]
        );
    }

    public function transfer(int $initiatorUserId, TransferData $data): TransactionOutcome
    {
        $currency = (string) Config::get('banking.currency', 'USD');
        $threshold = (string) Config::get('banking.approvals.manager_threshold', '10000.00');

        if ($data->sourceAccountPublicId === $data->destinationAccountPublicId) {
            throw new TransactionRuleViolation('لا يمكن التحويل لنفس الحساب');
        }
        if (bccomp($data->amount, '0.00', 2) <= 0) {
            throw new TransactionRuleViolation('المبلغ يجب أن يكون أكبر من صفر');
        }

        $locked = $this->accounts->lockByPublicIdsForUpdate([
            $data->sourceAccountPublicId,
            $data->destinationAccountPublicId,
        ]);

        $source = $locked[$data->sourceAccountPublicId] ?? null;
        $dest   = $locked[$data->destinationAccountPublicId] ?? null;

        if (!$source || !$dest) throw new TransactionRuleViolation('حساب مصدر/وجهة غير موجود');
        if ($source->type === 'group' || $dest->type === 'group') throw new TransactionRuleViolation('لا يمكن التحويل من/إلى حساب group');
        if ($source->state !== 'active' || $dest->state !== 'active') throw new TransactionRuleViolation('حساب غير نشط لإجراء عملية');

        $sourceBefore = $source->balance;
        $sourceAfter  = bcsub($sourceBefore, $data->amount, 2);
        if (bccomp($sourceAfter, '0.00', 2) < 0) throw new InsufficientFunds('رصيد غير كافٍ');

        $needsApproval = (bccomp($data->amount, $threshold, 2) === 1);

        $status = $needsApproval
            ? TransactionStatusEnum::PENDING_APPROVAL->value
            : TransactionStatusEnum::POSTED->value;

        $tx = $this->txRepo->create([
            'initiator_user_id' => $initiatorUserId,
            'type' => TransactionTypeEnum::TRANSFER->value,
            'status' => $status,
            'source_account_id' => $source->id,
            'destination_account_id' => $dest->id,
            'amount' => $data->amount,
            'currency' => $currency,
            'description' => $data->description,
            'posted_at' => $needsApproval ? null : now(),
        ]);

        if ($needsApproval) {
            $this->approvals->createPending($tx->id, $initiatorUserId);

            return new TransactionOutcome(
                message: 'تم إنشاء التحويل,  بانتظار الموافقة',
                transactionPublicId: $tx->publicId,
                status: $tx->status,
                data: []
            );
        }

        $destBefore = $dest->balance;
        $destAfter  = bcadd($destBefore, $data->amount, 2);

        $this->ledgerRepo->create([
            'transaction_id' => $tx->id,
            'account_id' => $source->id,
            'direction' => LedgerDirectionEnum::DEBIT->value,
            'amount' => $data->amount,
            'currency' => $currency,
            'balance_before' => $sourceBefore,
            'balance_after' => $sourceAfter,
        ]);

        $this->ledgerRepo->create([
            'transaction_id' => $tx->id,
            'account_id' => $dest->id,
            'direction' => LedgerDirectionEnum::CREDIT->value,
            'amount' => $data->amount,
            'currency' => $currency,
            'balance_before' => $destBefore,
            'balance_after' => $destAfter,
        ]);

        $this->accounts->updateBalance($source->id, $sourceAfter);
        $this->accounts->updateBalance($dest->id, $destAfter);

        if ($source->parentId) {
            $p = $this->accounts->lockByIdForUpdate($source->parentId);
            if ($p) $this->accounts->updateBalance($p->id, bcsub($p->balance, $data->amount, 2));
        }
        if ($dest->parentId) {
            $p = $this->accounts->lockByIdForUpdate($dest->parentId);
            if ($p) $this->accounts->updateBalance($p->id, bcadd($p->balance, $data->amount, 2));
        }

        return new TransactionOutcome(
            message: 'تم التحويل بنجاح',
            transactionPublicId: $tx->publicId,
            status: $tx->status,
            data: [
                'source_new_balance' => $sourceAfter,
                'destination_new_balance' => $destAfter,
            ]
        );
    }
}
