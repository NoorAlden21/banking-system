<?php

namespace App\Banking\Transactions\Domain\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final class TransactionPosted implements ShouldDispatchAfterCommit
{
    public function __construct(
        public readonly string $transactionPublicId
    ) {
    }
}
