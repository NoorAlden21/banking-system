<?php

namespace App\Banking\Payments\Infrastructure\Clients;

final class InternationalWireClient
{
    public function sendWire(string $fromIban, string $toIban, string $amountDecimal, string $currency, ?string $note): array
    {
        // simulate queued wire
        return [
            'state' => 'queued',
            'wire_id' => 'WIRE_' . bin2hex(random_bytes(6)),
        ];
    }

    public function refund(string $wireId, string $amountDecimal, string $currency): array
    {
        return [
            'state' => 'refunded',
            'wire_id' => $wireId,
        ];
    }
}
