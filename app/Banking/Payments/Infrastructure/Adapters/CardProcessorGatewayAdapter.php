<?php

namespace App\Banking\Payments\Infrastructure\Adapters;

use App\Banking\Payments\Domain\Contracts\PaymentGateway;
use App\Banking\Payments\Domain\ValueObjects\PaymentCharge;
use App\Banking\Payments\Domain\ValueObjects\PaymentResult;
use App\Banking\Payments\Infrastructure\Clients\CardProcessorClient;

final class CardProcessorGatewayAdapter implements PaymentGateway
{
    public function __construct(private readonly CardProcessorClient $client)
    {
    }

    public function charge(PaymentCharge $charge): PaymentResult
    {
        $token = $charge->token ?? '';
        if ($token === '') {
            return new PaymentResult(false, 'failed', null, [], 'Missing payment token');
        }

        $cents = (int) round(((float) $charge->amount) * 100);

        $res = $this->client->makePaymentWithToken($token, $cents, $charge->currency);

        if (!($res['ok'] ?? false)) {
            return new PaymentResult(false, (string)($res['status'] ?? 'failed'), null, $res, (string)($res['message'] ?? 'Charge failed'));
        }

        return new PaymentResult(true, (string)$res['status'], (string)$res['ref'], $res);
    }

    public function refund(string $reference, string $amount, string $currency): PaymentResult
    {
        $cents = (int) round(((float) $amount) * 100);
        $res = $this->client->refund($reference, $cents, $currency);

        return new PaymentResult((bool)($res['ok'] ?? false), (string)($res['status'] ?? 'refunded'), (string)($res['ref'] ?? $reference), $res);
    }
}
