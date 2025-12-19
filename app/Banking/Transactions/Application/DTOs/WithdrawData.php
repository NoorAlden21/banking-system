<?php

namespace App\Banking\Transactions\Application\DTOs;

final class WithdrawData
{
    public function __construct(
        public readonly string $accountPublicId,
        public readonly string $amount,
        public readonly ?string $description,
    ) {
    }
}
