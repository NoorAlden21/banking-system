<?php

namespace App\Banking\Accounts\Domain\Services\Interest;

use Illuminate\Support\Facades\Config;

final class InterestCalculator
{
    public function __construct(
        private readonly MarketConditionProvider $marketProvider,
        private readonly InterestStrategyResolver $resolver,
    ) {
    }

    /**
     * @return array{
     *   principal:string,
     *   days:int,
     *   market:string,
     *   rate_used:float,
     *   interest:string,
     *   details:array
     * }
     */
    public function preview(string $principal, string $accountType, int $days, ?string $marketCode): array
    {
        $days = max(1, min(365, $days));

        $market = $this->marketProvider->current($marketCode);

        $strategy = $this->resolver->resolve($accountType);

        $calc = $strategy->calculate($principal, $days, $market);

        $min = (float) Config::get('banking_interest.min_interest', 0.01);
        $interestFloat = (float) $calc['interest'];
        if ($interestFloat < $min) {
            $calc['interest'] = number_format(0, 2, '.', '');
        }

        return [
            'principal' => $this->fmt((float)$principal),
            'days' => $days,
            'market' => $market->code,
            'rate_used' => (float) $calc['rate_used'],
            'interest' => (string) $calc['interest'],
            'details' => (array) ($calc['details'] ?? []),
        ];
    }

    private function fmt(float $x): string
    {
        return number_format($x, 2, '.', '');
    }
}
