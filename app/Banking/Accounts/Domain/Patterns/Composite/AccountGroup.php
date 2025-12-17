<?php

namespace App\Banking\Accounts\Domain\Patterns\Composite;

use App\Banking\Accounts\Domain\Entities\Account;

final class AccountGroup implements AccountComponent
{
    /** @var AccountComponent[] */
    private array $children = [];

    public function __construct(private readonly Account $groupAccount)
    {
    }

    public function add(AccountComponent $child): void
    {
        $this->children[] = $child;
    }

    public function publicId(): string
    {
        return $this->groupAccount->publicId;
    }
    public function type(): string
    {
        return $this->groupAccount->type->value;
    }
    public function state(): string
    {
        return $this->groupAccount->state->value;
    }

    public function balance(): string
    {
        return $this->groupAccount->balance;
    }

    public function totalBalance(): string
    {
        $sum = '0.00';
        foreach ($this->children as $child) {
            $sum = bcadd($sum, $child->totalBalance(), 2);
        }
        return $sum;
    }

    public function children(): array
    {
        return $this->children;
    }
}
