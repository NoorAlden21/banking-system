<?php

namespace App\Banking\Payments\Infrastructure\Adapters;

use App\Banking\Payments\Domain\Contracts\PaymentGateway;
use App\Banking\Payments\Domain\ValueObjects\PaymentCharge;
use App\Banking\Payments\Domain\ValueObjects\PaymentResult;
use App\Banking\Payments\Infrastructure\Clients\InternationalWireClient;

final class InternationalWireGatewayAdapter implements PaymentGateway
{
    public function __construct(private readonly InternationalWireClient $client)
    {
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $from = (string)($charge->meta['from_iban'] ?? '');
        $to   = (string)($charge->meta['to_iban'] ?? '');

        if ($from === '' || $to === '') {
            return new PaymentResult(false, 'failed', null, [], 'Missing IBANs');
        }

        $res = $this->client->sendWire($from, $to, $charge->amount, $charge->currency, $charge->meta['note'] ?? null);

        return new PaymentResult(true, (string)$res['state'], (string)$res['wire_id'], $res);
    }

    public function refund(string $reference, string $amount, string $currency): PaymentResult
    {
        $res = $this->client->refund($reference, $amount, $currency);
        return new PaymentResult(true, (string)($res['state'] ?? 'refunded'), (string)($res['wire_id'] ?? $reference), $res);
    }
}
