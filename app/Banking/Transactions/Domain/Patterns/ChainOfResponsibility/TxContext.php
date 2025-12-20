<?php

namespace App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility;

use App\Banking\Transactions\Domain\Contracts\LockedAccount;

final class TxContext
{
    public function __construct(
        public string $type,
        public int $initiatorUserId,
        public string $amount,
        public string $currency,
        public ?string $description = null,

        public ?LockedAccount $sourceAccount = null,
        public ?LockedAccount $destAccount = null,

        public ?string $dailyLimit = null,
        public ?string $monthlyLimit = null,

        public bool $needsApproval = false,
    ) {
    }
}
