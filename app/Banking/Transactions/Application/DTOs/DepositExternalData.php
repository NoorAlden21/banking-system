<?php

namespace App\Banking\Transactions\Application\DTOs;

final class DepositExternalData
{
    public function __construct(
        public readonly string $accountPublicId,
        public readonly string $amount,
        public readonly ?string $description,
        public readonly ?string $gateway,        // card | wire | legacy
        public readonly ?string $paymentToken,   // opaque token
    ) {
    }
}
