<?php

namespace App\Banking\Accounts\Domain\Entities;

use App\Banking\Accounts\Domain\Enums\AccountStateEnum;
use App\Banking\Accounts\Domain\Enums\AccountTypeEnum;

final class Account
{
    public function __construct(
        public readonly int $id,
        public readonly string $publicId,
        public readonly int $userId,
        public readonly ?int $parentId,
        public readonly AccountTypeEnum $type,
        public readonly AccountStateEnum $state,
        public readonly string $balance,
        public readonly ?string $dailyLimit,
        public readonly ?string $monthlyLimit,
        public readonly ?string $closedAt,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }

    public function isGroup(): bool
    {
        return $this->type === AccountTypeEnum::GROUP;
    }
}
