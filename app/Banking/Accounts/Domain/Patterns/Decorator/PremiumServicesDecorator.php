<?php

namespace App\Banking\Accounts\Domain\Patterns\Decorator;

final class PremiumServicesDecorator extends AccountFeatureDecorator
{
    public function __construct(AccountFeatureComponent $inner, private readonly string $feeRatePercent)
    {
        parent::__construct($inner);
    }

    public function transferFee(string $amount): string
    {
        // fee = amount * rate%
        // Example: rate=0.50 => 0.5%
        $rate = bcdiv($this->feeRatePercent, '100', 6);
        return bcmul($amount, $rate, 2);
    }

    public function enabledFeatures(): array
    {
        return array_values(array_unique(array_merge(
            $this->inner->enabledFeatures(),
            ["premium(fee_rate={$this->feeRatePercent}%)"]
        )));
    }
}
