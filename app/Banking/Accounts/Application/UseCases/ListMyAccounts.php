<?php

namespace App\Banking\Accounts\Application\UseCases;

use App\Banking\Accounts\Domain\Contracts\AccountRepository;
use App\Banking\Accounts\Domain\Entities\Account;

final class ListMyAccounts
{
    public function __construct(private readonly AccountRepository $accounts)
    {
    }

    /** @return Account[] */
    public function handle(int $authUserId): array
    {
        return $this->accounts->listByUser($authUserId);
    }
}
