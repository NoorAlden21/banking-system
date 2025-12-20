<?php

namespace App\Banking\Transactions\Domain\Entities;

final class ApprovalRecord
{
    public function __construct(
        public readonly int $id,
        public readonly int $transactionId,
        public readonly string $status,
        public readonly int $requestedByUserId,
    ) {
    }
}
