<?php

namespace App\Banking\Accounts\Domain\Patterns\Decorator;

final class InsuranceDecorator extends AccountFeatureDecorator
{
    public function __construct(AccountFeatureComponent $inner, private readonly string $monthlyFee)
    {
        parent::__construct($inner);
    }

    public function monthlyFixedFees(): string
    {
        return bcadd($this->inner->monthlyFixedFees(), $this->monthlyFee, 2);
    }

    public function enabledFeatures(): array
    {
        return array_values(array_unique(array_merge(
            $this->inner->enabledFeatures(),
            ["insurance(monthly_fee={$this->monthlyFee})"]
        )));
    }
}
