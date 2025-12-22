<?php

namespace App\Banking\Accounts\Domain\Services\Interest;

final class MarketCondition
{
    public function __construct(
        public readonly string $code,
        public readonly float $multiplier,
    ) {
    }
}
