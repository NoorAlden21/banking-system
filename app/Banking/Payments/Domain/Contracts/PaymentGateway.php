<?php

namespace App\Banking\Payments\Domain\Contracts;

use App\Banking\Payments\Domain\ValueObjects\PaymentCharge;
use App\Banking\Payments\Domain\ValueObjects\PaymentResult;

interface PaymentGateway
{
    public function charge(PaymentCharge $charge): PaymentResult;

    public function refund(string $reference, string $amount, string $currency): PaymentResult;
}
