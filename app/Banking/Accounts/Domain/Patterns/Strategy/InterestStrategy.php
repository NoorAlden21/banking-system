<?php

namespace App\Banking\Accounts\Domain\Patterns\Strategy;

use App\Banking\Accounts\Domain\Services\Interest\MarketCondition;

interface InterestStrategy
{
    /**
     * @param string $principal balance/principal as string (DECIMAL)
     * @param int $days period length
     * @return array{interest:string, rate_used:float, details:array}
     */
    public function calculate(string $principal, int $days, MarketCondition $market): array;
}
