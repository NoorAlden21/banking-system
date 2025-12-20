<?php

namespace App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility;

use Illuminate\Support\Facades\Config;
use App\Banking\Transactions\Domain\Exceptions\TransactionRuleViolation;

final class AMLRules implements TxStep
{
    public function handle(TxContext $ctx, callable $next): array
    {
        if (!Config::get('banking.aml.enabled', true)) {
            return $next($ctx);
        }

        $block = (string) Config::get('banking.aml.block_threshold', '99999999.00');

        if (bccomp($ctx->amount, $block, 2) === 1) {
            throw new TransactionRuleViolation('العملية مرفوضة وفق قواعد AML (placeholder)');
        }

        return $next($ctx);
    }
}
