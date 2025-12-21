<?php

namespace App\Banking\Accounts\Domain\Patterns\Decorator;

final class OverdraftProtectionDecorator extends AccountFeatureDecorator
{
    public function __construct(AccountFeatureComponent $inner, private readonly string $overdraftLimit)
    {
        parent::__construct($inner);
    }

    public function availableToWithdraw(): string
    {
        // available = balance + overdraftLimit
        return bcadd($this->inner->availableToWithdraw(), $this->overdraftLimit, 2);
    }

    public function enabledFeatures(): array
    {
        return array_values(array_unique(array_merge(
            $this->inner->enabledFeatures(),
            ["overdraft(limit={$this->overdraftLimit})"]
        )));
    }
}
