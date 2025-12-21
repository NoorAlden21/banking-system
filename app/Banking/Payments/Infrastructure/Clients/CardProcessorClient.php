<?php

namespace App\Banking\Payments\Infrastructure\Clients;

final class CardProcessorClient
{
    /**
     * Simulates a card processor.
     * - if token starts with "decline_" => fail
     */
    public function makePaymentWithToken(string $token, int $amountCents, string $currencyCode): array
    {
        if (str_starts_with($token, 'decline_')) {
            return [
                'ok' => false,
                'status' => 'declined',
                'ref' => null,
                'message' => 'Card declined',
            ];
        }

        return [
            'ok' => true,
            'status' => 'authorized',
            'ref' => 'CP_' . bin2hex(random_bytes(6)),
            'auth_code' => strtoupper(bin2hex(random_bytes(3))),
        ];
    }

    public function refund(string $ref, int $amountCents, string $currencyCode): array
    {
        return [
            'ok' => true,
            'status' => 'refunded',
            'ref' => $ref,
        ];
    }
}
