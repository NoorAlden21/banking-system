<?php

namespace App\Banking\Transactions\Domain\Contracts;

use App\Banking\Transactions\Domain\Entities\LockedAccount;

interface AccountGateway
{
    /** @return array<string, LockedAccount> map[public_id] */
    public function lockByPublicIdsForUpdate(array $publicIds): array;

    public function lockByIdForUpdate(int $id): ?LockedAccount;

    public function updateBalance(int $accountId, string $newBalance): void;
}
