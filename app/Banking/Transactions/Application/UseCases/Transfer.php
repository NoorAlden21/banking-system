<?php

namespace App\Banking\Transactions\Application\UseCases;

use App\Banking\Transactions\Application\DTOs\TransferData;
use App\Banking\Transactions\Application\DTOs\TransactionOutcome;
use App\Banking\Transactions\Domain\Services\TransactionProcessor;

final class Transfer
{
    public function __construct(private readonly TransactionProcessor $processor)
    {
    }

    public function handle(int $initiatorUserId, TransferData $data): TransactionOutcome
    {
        return $this->processor->transfer($initiatorUserId, $data);
    }
}
