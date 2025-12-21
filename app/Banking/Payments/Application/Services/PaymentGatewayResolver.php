<?php

namespace App\Banking\Payments\Application\Services;

use App\Banking\Payments\Domain\Contracts\PaymentGateway;

final class PaymentGatewayResolver
{
    /**
     * @param array<string, PaymentGateway> $gateways
     */
    public function __construct(
        private readonly array $gateways,
        private readonly string $defaultGateway = 'card'
    ) {
    }

    public function resolve(?string $gateway): PaymentGateway
    {
        $key = $gateway ?: $this->defaultGateway;

        if (!isset($this->gateways[$key])) {
            // fallback
            $key = $this->defaultGateway;
        }

        return $this->gateways[$key];
    }
}
