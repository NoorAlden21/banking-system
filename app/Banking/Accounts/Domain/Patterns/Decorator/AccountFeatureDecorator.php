<?php

namespace App\Banking\Accounts\Domain\Patterns\Decorator;

abstract class AccountFeatureDecorator implements AccountFeatureComponent
{
    public function __construct(protected readonly AccountFeatureComponent $inner)
    {
    }

    public function accountId(): int
    {
        return $this->inner->accountId();
    }
    public function accountPublicId(): string
    {
        return $this->inner->accountPublicId();
    }

    public function balance(): string
    {
        return $this->inner->balance();
    }
    public function availableToWithdraw(): string
    {
        return $this->inner->availableToWithdraw();
    }

    public function transferFee(string $amount): string
    {
        return $this->inner->transferFee($amount);
    }
    public function monthlyFixedFees(): string
    {
        return $this->inner->monthlyFixedFees();
    }

    public function enabledFeatures(): array
    {
        return $this->inner->enabledFeatures();
    }
}
