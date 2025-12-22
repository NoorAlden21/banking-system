<?php

namespace App\Banking\Accounts\Domain\Patterns\Strategy\Strategies;

use Illuminate\Support\Facades\Config;
use App\Banking\Accounts\Domain\Patterns\Strategy\InterestStrategy;
use App\Banking\Accounts\Domain\Services\Interest\MarketCondition;

final class SavingsInterestStrategy implements InterestStrategy
{
    public function calculate(string $principal, int $days, MarketCondition $market): array
    {
        $base = (float) Config::get('banking_interest.base_rates.savings', 0.03);
        $rate = $base * $market->multiplier;

        $interest = $this->fmt(((float)$principal) * $rate * ($days / 365.0));

        return [
            'interest' => $interest,
            'rate_used' => $rate,
            'details' => [
                'type' => 'savings',
                'market' => $market->code,
                'multiplier' => $market->multiplier,
                'base_rate' => $base,
            ],
        ];
    }

    private function fmt(float $x): string
    {
        return number_format(max(0, $x), 2, '.', '');
    }
}
