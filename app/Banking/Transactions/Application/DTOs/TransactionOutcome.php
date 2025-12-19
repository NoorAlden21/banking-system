<?php

namespace App\Banking\Transactions\Application\DTOs;

final class TransactionOutcome
{
    public function __construct(
        public readonly string $message,
        public readonly string $transactionPublicId,
        public readonly string $status, // posted | pending_approval | ...
        public readonly array $data = [], // balances / ids ...
    ) {
    }
}
