<?php

namespace App\Banking\Transactions\Domain\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final class LargeTransactionOccurred implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly string $transactionPublicId,
        public readonly string $amount,
        public readonly string $type,
        public readonly string $currency,
    ) {
    }
}
