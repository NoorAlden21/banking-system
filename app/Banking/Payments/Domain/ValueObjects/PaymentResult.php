<?php

namespace App\Banking\Payments\Domain\ValueObjects;

final class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,      // authorized | queued | failed | refunded
        public readonly ?string $reference,  // external ref
        /** @var array<string,mixed> */
        public readonly array $raw = [],
        public readonly ?string $errorMessage = null,
    ) {
    }
}
