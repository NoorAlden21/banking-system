<?php

namespace App\Banking\Accounts\Domain\Patterns\Strategy\Strategies;

use Illuminate\Support\Facades\Config;
use App\Banking\Accounts\Domain\Patterns\Strategy\InterestStrategy;
use App\Banking\Accounts\Domain\Services\Interest\MarketCondition;

final class InvestmentInterestStrategy implements InterestStrategy
{
    public function calculate(string $principal, int $days, MarketCondition $market): array
    {
        $base = (float) Config::get('banking_interest.base_rates.investment', 0.06);

        // investment reacts stronger to market (demo)
        $rate = $base * ($market->multiplier + 0.05);

        $interest = $this->fmt(((float)$principal) * $rate * ($days / 365.0));

        return [
            'interest' => $interest,
            'rate_used' => $rate,
            'details' => [
                'type' => 'investment',
                'market' => $market->code,
                'multiplier' => $market->multiplier,
                'base_rate' => $base,
                'extra_market_sensitivity' => 0.05,
            ],
        ];
    }

    private function fmt(float $x): string
    {
        return number_format(max(0, $x), 2, '.', '');
    }
}
