<?php

namespace App\Banking\Transactions\Domain\Contracts;

interface LimitUsageRepository
{
    /** مجموع المبالغ الخارجة (withdraw + transfer) لحساب مصدر خلال فترة */
    public function sumOutflows(int $sourceAccountId, string $from, string $to): string;
}
