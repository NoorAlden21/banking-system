<?php

namespace App\Banking\Payments\Infrastructure\Clients;

final class LegacyCoreClient
{
    public function doTxn(string $payloadJson): string
    {
        if (str_contains($payloadJson, '"force_fail":true')) {
            return 'ERR:LEGACY_FAIL';
        }

        return 'OK:' . ('LG_' . bin2hex(random_bytes(6)));
    }

    public function refund(string $ref): string
    {
        return 'OK:REFUND_' . $ref;
    }
}
