<?php

namespace App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility;

use Carbon\Carbon;
use App\Banking\Transactions\Domain\Contracts\LimitUsageRepository;
use App\Banking\Transactions\Domain\Exceptions\TransactionRuleViolation;

final class LimitChecks extends TxHandler
{
    public function __construct(private readonly LimitUsageRepository $limits)
    {
    }

    public function handle(TxContext $ctx): TxContext
    {
        // فقط على withdraw/transfer (outflow من source)
        if (!in_array($ctx->type, ['withdraw', 'transfer'], true)) {
            return $this->next($ctx);
        }

        $acc = $ctx->sourceAccount;
        if (!$acc) return $this->next($ctx);

        // daily limit
        if ($ctx->dailyLimit !== null) {
            $from = Carbon::now()->startOfDay()->toDateTimeString();
            $to   = Carbon::now()->endOfDay()->toDateTimeString();
            $used = $this->limits->sumOutflows($acc->id, $from, $to);
            $after = bcadd($used, $ctx->amount, 2);

            if (bccomp($after, $ctx->dailyLimit, 2) === 1) {
                throw new TransactionRuleViolation('تجاوز الحد اليومي للحساب');
            }
        }

        // monthly limit
        if ($ctx->monthlyLimit !== null) {
            $from = Carbon::now()->startOfMonth()->toDateTimeString();
            $to   = Carbon::now()->endOfMonth()->toDateTimeString();
            $used = $this->limits->sumOutflows($acc->id, $from, $to);
            $after = bcadd($used, $ctx->amount, 2);

            if (bccomp($after, $ctx->monthlyLimit, 2) === 1) {
                throw new TransactionRuleViolation('تجاوز الحد الشهري للحساب');
            }
        }

        return $this->next($ctx);
    }
}
