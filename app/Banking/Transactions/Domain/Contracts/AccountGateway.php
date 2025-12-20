<?php

namespace App\Banking\Transactions\Domain\Contracts;

final class LockedAccount
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $publicId,
        public readonly ?int $parentId,
        public readonly string $type,
        public readonly string $state,
        public readonly string $balance,
    ) {
    }
}

interface AccountGateway
{
    /** @return array<string, LockedAccount> map[public_id] */
    public function lockByPublicIdsForUpdate(array $publicIds): array;

    public function lockByIdForUpdate(int $id): ?LockedAccount;

    public function updateBalance(int $accountId, string $newBalance): void;
}
