<?php

namespace App\Banking\Accounts\Domain\Services\Interest;

interface MarketConditionProvider
{
    public function current(?string $code = null): MarketCondition;
}
