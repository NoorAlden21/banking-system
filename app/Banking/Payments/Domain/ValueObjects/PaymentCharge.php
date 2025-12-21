<?php

namespace App\Banking\Payments\Domain\ValueObjects;

final class PaymentCharge
{
    /**
     * $gateway: card | wire | legacy
     * $token: opaque token (NOT PAN)
     */
    public function __construct(
        public readonly string $gateway,
        public readonly string $amount,
        public readonly string $currency,
        public readonly ?string $token,
        /** @var array<string,mixed> */
        public readonly array $meta = [],
    ) {
    }
}
