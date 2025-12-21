<?php

namespace App\Banking\Transactions\Domain\Entities;

final class ScheduledTransactionRecord
{
    public function __construct(
        public readonly int $id,
        public readonly string $publicId,
    ) {
    }
}
