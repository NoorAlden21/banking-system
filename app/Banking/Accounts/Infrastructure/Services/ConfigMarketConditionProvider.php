<?php

namespace App\Banking\Accounts\Infrastructure\Services\Interest;

use Illuminate\Support\Facades\Config;
use App\Banking\Accounts\Domain\Services\Interest\MarketCondition;
use App\Banking\Accounts\Domain\Services\Interest\MarketConditionProvider;

final class ConfigMarketConditionProvider implements MarketConditionProvider
{
    public function current(?string $code = null): MarketCondition
    {
        $default = (string) Config::get('banking_interest.default_market', 'normal');

        $code = $code ?: $default;

        $markets = (array) Config::get('banking_interest.markets', []);
        $multiplier = (float) (($markets[$code]['multiplier'] ?? null) ?? 1.0);

        return new MarketCondition(code: $code, multiplier: $multiplier);
    }
}
