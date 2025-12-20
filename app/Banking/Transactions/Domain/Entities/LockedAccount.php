<?php

namespace App\Banking\Transactions\Domain\Entities;

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
        public readonly ?string $dailyLimit,
        public readonly ?string $monthlyLimit,
    ) {
    }
}
