<?php

namespace App\Banking\Payments\Infrastructure\Adapters;

use App\Banking\Payments\Domain\Contracts\PaymentGateway;
use App\Banking\Payments\Domain\ValueObjects\PaymentCharge;
use App\Banking\Payments\Domain\ValueObjects\PaymentResult;
use App\Banking\Payments\Infrastructure\Clients\LegacyCoreClient;

final class LegacyCoreGatewayAdapter implements PaymentGateway
{
    public function __construct(private readonly LegacyCoreClient $client)
    {
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $payload = [
            'amount' => $charge->amount,
            'currency' => $charge->currency,
            'token' => $charge->token,
            'meta' => $charge->meta,
        ];

        $response = $this->client->doTxn(json_encode($payload, JSON_UNESCAPED_UNICODE));

        if (str_starts_with($response, 'ERR:')) {
            return new PaymentResult(false, 'failed', null, ['legacy' => $response], $response);
        }

        // OK:REF
        $ref = substr($response, 3);
        return new PaymentResult(true, 'authorized', $ref, ['legacy' => $response]);
    }

    public function refund(string $reference, string $amount, string $currency): PaymentResult
    {
        $response = $this->client->refund($reference);
        $ok = str_starts_with($response, 'OK:');
        return new PaymentResult($ok, $ok ? 'refunded' : 'failed', $reference, ['legacy' => $response], $ok ? null : $response);
    }
}
