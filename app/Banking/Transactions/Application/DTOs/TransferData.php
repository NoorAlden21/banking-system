<?php

namespace App\Banking\Transactions\Application\DTOs;

final class TransferData
{
    public function __construct(
        public readonly string $sourceAccountPublicId,
        public readonly string $destinationAccountPublicId,
        public readonly string $amount,
        public readonly ?string $description,
    ) {
    }
}
