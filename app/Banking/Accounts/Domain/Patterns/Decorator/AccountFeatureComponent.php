<?php

namespace App\Banking\Accounts\Domain\Patterns\Decorator;

interface AccountFeatureComponent
{
    public function accountId(): int;
    public function accountPublicId(): string;

    public function balance(): string;
    public function availableToWithdraw(): string;

    public function transferFee(string $amount): string;
    public function monthlyFixedFees(): string;

    /** @return array<int, string> */
    public function enabledFeatures(): array;
}
