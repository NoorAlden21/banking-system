<?php

namespace App\Banking\Transactions\Application\UseCases;

use App\Banking\Transactions\Application\DTOs\WithdrawData;
use App\Banking\Transactions\Application\DTOs\TransactionOutcome;
use App\Banking\Transactions\Domain\Services\TransactionProcessor;

final class Withdraw
{
    public function __construct(private readonly TransactionProcessor $processor)
    {
    }

    public function handle(int $initiatorUserId, WithdrawData $data): TransactionOutcome
    {
        return $this->processor->withdraw($initiatorUserId, $data);
    }
}
