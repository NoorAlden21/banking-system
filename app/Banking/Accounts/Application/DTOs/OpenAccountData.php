<?php

namespace App\Banking\Accounts\Application\DTOs;

use App\Banking\Accounts\Domain\Enums\AccountTypeEnum;

final class OpenAccountData
{
    public function __construct(
        public readonly AccountTypeEnum $type,
        public readonly ?string $dailyLimit,
        public readonly ?string $monthlyLimit,
    ) {
    }
}
