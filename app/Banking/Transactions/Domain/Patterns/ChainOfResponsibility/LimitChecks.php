<?php

namespace App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility;

use Illuminate\Support\Carbon;

use App\Banking\Transactions\Domain\Contracts\TransactionReadRepository;
use App\Banking\Transactions\Domain\Exceptions\TransactionRuleViolation;

final class LimitChecks implements TxStep
{
    public function __construct(private readonly TransactionReadRepository $reads)
    {
    }

    public function handle(TxContext $ctx, callable $next): array
    {
        if (!($ctx->isWithdraw() || $ctx->isTransfer())) {
            return $next($ctx);
        }

        $source = $ctx->source;
        if (!$source) return $next($ctx);

        // daily/monthly limits from account
        $daily = $source->dailyLimit;     // string|null
        $monthly = $source->monthlyLimit; // string|null

        $now = Carbon::now();

        // posted_at ranges
        $dayFrom = $now->copy()->startOfDay()->toDateTimeString();
        $dayTo   = $now->copy()->endOfDay()->toDateTimeString();

        $monthFrom = $now->copy()->startOfMonth()->startOfDay()->toDateTimeString();
        $monthTo   = $now->copy()->endOfMonth()->endOfDay()->toDateTimeString();

        if ($daily !== null) {
            $used = $this->reads->sumPostedOutflowForAccount($source->id, $dayFrom, $dayTo);
            $newTotal = bcadd($used, $ctx->amount, 2);

            if (bccomp($newTotal, $daily, 2) === 1) {
                throw new TransactionRuleViolation("تجاوز الحد اليومي للحساب (daily_limit={$daily})");
            }
        }

        if ($monthly !== null) {
            $used = $this->reads->sumPostedOutflowForAccount($source->id, $monthFrom, $monthTo);
            $newTotal = bcadd($used, $ctx->amount, 2);

            if (bccomp($newTotal, $monthly, 2) === 1) {
                throw new TransactionRuleViolation("تجاوز الحد الشهري للحساب (monthly_limit={$monthly})");
            }
        }

        return $next($ctx);
    }
}
