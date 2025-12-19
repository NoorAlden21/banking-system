<?php

namespace App\Banking\Transactions\Domain\Entities;

final class TransactionRecord
{
    public function __construct(
        public readonly int $id,
        public readonly string $publicId,
        public readonly string $status,
    ) {
    }
}
