<?php

namespace App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility;

use App\Banking\Transactions\Domain\Exceptions\TransactionRuleViolation;

final class ValidateBasics implements TxStep
{
    public function handle(TxContext $ctx, callable $next): array
    {
        if (bccomp($ctx->amount, '0.00', 2) <= 0) {
            throw new TransactionRuleViolation('المبلغ يجب أن يكون أكبر من صفر');
        }

        if ($ctx->isTransfer()) {
            if (!$ctx->sourceAccountPublicId || !$ctx->destinationAccountPublicId) {
                throw new TransactionRuleViolation('بيانات حساب المصدر/الوجهة مطلوبة');
            }
            if ($ctx->sourceAccountPublicId === $ctx->destinationAccountPublicId) {
                throw new TransactionRuleViolation('لا يمكن التحويل لنفس الحساب');
            }
        }

        if ($ctx->isWithdraw() && !$ctx->sourceAccountPublicId) {
            throw new TransactionRuleViolation('account_public_id مطلوب');
        }

        if ($ctx->isDeposit() && !$ctx->destinationAccountPublicId) {
            throw new TransactionRuleViolation('account_public_id مطلوب');
        }

        return $next($ctx);
    }
}
