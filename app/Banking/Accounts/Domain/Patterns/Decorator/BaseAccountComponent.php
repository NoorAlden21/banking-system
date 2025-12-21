<?php

namespace App\Banking\Accounts\Domain\Patterns\Decorator;

final class BaseAccountComponent implements AccountFeatureComponent
{
    public function __construct(
        private readonly int $accountId,
        private readonly string $accountPublicId,
        private readonly string $balance,
    ) {
    }

    public function accountId(): int
    {
        return $this->accountId;
    }
    public function accountPublicId(): string
    {
        return $this->accountPublicId;
    }

    public function balance(): string
    {
        return $this->balance;
    }

    public function availableToWithdraw(): string
    {
        return $this->balance;
    }

    public function transferFee(string $amount): string
    {
        return '0.00';
    }

    public function monthlyFixedFees(): string
    {
        return '0.00';
    }

    public function enabledFeatures(): array
    {
        return [];
    }
}
