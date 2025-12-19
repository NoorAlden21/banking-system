<?php

namespace App\Banking\Transactions\Application\UseCases;

use App\Banking\Transactions\Application\DTOs\DepositData;
use App\Banking\Transactions\Application\DTOs\TransactionOutcome;
use App\Banking\Transactions\Domain\Services\TransactionProcessor;

final class Deposit
{
    public function __construct(private readonly TransactionProcessor $processor)
    {
    }

    public function handle(int $initiatorUserId, DepositData $data): TransactionOutcome
    {
        return $this->processor->deposit($initiatorUserId, $data);
    }
}
