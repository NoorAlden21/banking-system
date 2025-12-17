<?php

namespace App\Banking\Accounts\Domain\Contracts;

use App\Banking\Accounts\Domain\Entities\Account;
use App\Banking\Accounts\Domain\Enums\AccountTypeEnum;

interface AccountRepository
{
    public function findUserGroup(int $userId): ?Account;

    public function createGroup(int $userId): Account;

    public function createChildAccount(
        int $userId,
        int $groupId,
        AccountTypeEnum $type,
        ?string $dailyLimit,
        ?string $monthlyLimit
    ): Account;

    /** @return Account[] */
    public function listByUser(int $userId): array;

    public function findByPublicId(string $publicId): ?Account;

    public function updateStateByPublicId(string $publicId, string $newState): Account;
}
