<?php

namespace App\Banking\Transactions\Domain\Entities;

final class TransactionForPosting
{
    public function __construct(
        public readonly int $id,
        public readonly string $publicId,
        public readonly string $type,
        public readonly string $status,
        public readonly ?int $sourceAccountId,
        public readonly ?int $destinationAccountId,
        public readonly ?string $sourceAccountPublicId,
        public readonly ?string $destinationAccountPublicId,
        public readonly string $amount,
        public readonly string $currency,
        public readonly ?string $description,
    ) {
    }
}
