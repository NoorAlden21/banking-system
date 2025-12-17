<?php

namespace App\Banking\Accounts\Domain\Patterns\Composite;

use App\Banking\Accounts\Domain\Entities\Account;

final class AccountLeaf implements AccountComponent
{
    public function __construct(private readonly Account $account)
    {
    }

    public function publicId(): string
    {
        return $this->account->publicId;
    }
    public function type(): string
    {
        return $this->account->type->value;
    }
    public function state(): string
    {
        return $this->account->state->value;
    }

    public function balance(): string
    {
        return $this->account->balance;
    }
    public function totalBalance(): string
    {
        return $this->account->balance;
    }

    public function children(): array
    {
        return [];
    }
}
