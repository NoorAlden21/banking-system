<?php

namespace App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility;

use App\Banking\Transactions\Domain\Contracts\AccountGateway;
use App\Banking\Transactions\Domain\Exceptions\TransactionRuleViolation;

final class CheckAccountState implements TxStep
{
    public function __construct(private readonly AccountGateway $accounts)
    {
    }

    public function handle(TxContext $ctx, callable $next): array
    {
        $ids = [];

        if ($ctx->isDeposit()) {
            $ids = [$ctx->destinationAccountPublicId];
        } elseif ($ctx->isWithdraw()) {
            $ids = [$ctx->sourceAccountPublicId];
        } else { // transfer
            $ids = [$ctx->sourceAccountPublicId, $ctx->destinationAccountPublicId];
        }

        $locked = $this->accounts->lockByPublicIdsForUpdate($ids);

        if ($ctx->isDeposit()) {
            $acc = $locked[$ctx->destinationAccountPublicId] ?? null;
            if (!$acc) throw new TransactionRuleViolation('الحساب غير موجود');
            if ($acc->type === 'group') throw new TransactionRuleViolation('لا يمكن الإيداع في حساب group');
            if ($acc->state !== 'active') throw new TransactionRuleViolation('الحساب غير نشط لإجراء عملية');
            $ctx->dest = $acc;
        }

        if ($ctx->isWithdraw()) {
            $acc = $locked[$ctx->sourceAccountPublicId] ?? null;
            if (!$acc) throw new TransactionRuleViolation('الحساب غير موجود');
            if ($acc->type === 'group') throw new TransactionRuleViolation('لا يمكن السحب من حساب group');
            if ($acc->state !== 'active') throw new TransactionRuleViolation('الحساب غير نشط لإجراء عملية');

            // ✅ ownership for customers
            if (!$ctx->canOperateAny && $acc->userId !== $ctx->initiatorUserId) {
                throw new TransactionRuleViolation('لا تملك صلاحية السحب من هذا الحساب');
            }

            $ctx->source = $acc;
        }

        if ($ctx->isTransfer()) {
            $src = $locked[$ctx->sourceAccountPublicId] ?? null;
            $dst = $locked[$ctx->destinationAccountPublicId] ?? null;

            if (!$src || !$dst) throw new TransactionRuleViolation('حساب مصدر/وجهة غير موجود');
            if ($src->type === 'group' || $dst->type === 'group') throw new TransactionRuleViolation('لا يمكن التحويل من/إلى حساب group');
            if ($src->state !== 'active' || $dst->state !== 'active') throw new TransactionRuleViolation('حساب غير نشط لإجراء عملية');

            // ✅ ownership: source must belong to customer
            if (!$ctx->canOperateAny && $src->userId !== $ctx->initiatorUserId) {
                throw new TransactionRuleViolation('لا تملك صلاحية التحويل من هذا الحساب');
            }

            $ctx->source = $src;
            $ctx->dest = $dst;
        }

        return $next($ctx);
    }
}
