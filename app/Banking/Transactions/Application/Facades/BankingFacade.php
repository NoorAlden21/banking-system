<?php

namespace App\Banking\Transactions\Application\Facades;

use App\Banking\Transactions\Application\DTOs\DepositData;
use App\Banking\Transactions\Application\DTOs\WithdrawData;
use App\Banking\Transactions\Application\DTOs\TransferData;
use App\Banking\Transactions\Application\DTOs\TransactionOutcome;

use App\Banking\Transactions\Application\UseCases\Deposit;
use App\Banking\Transactions\Application\UseCases\Withdraw;
use App\Banking\Transactions\Application\UseCases\Transfer;

final class BankingFacade
{
    public function __construct(
        private readonly Deposit $deposit,
        private readonly Withdraw $withdraw,
        private readonly Transfer $transfer,
    ) {
    }

    public function deposit(int $userId, DepositData $data): TransactionOutcome
    {
        return $this->deposit->handle($userId, $data);
    }

    public function withdraw(int $userId, WithdrawData $data, bool $canOperateAny): TransactionOutcome
    {
        return $this->withdraw->handle($userId, $data, $canOperateAny);
    }

    public function transfer(int $userId, TransferData $data, bool $canOperateAny): TransactionOutcome
    {
        return $this->transfer->handle($userId, $data, $canOperateAny);
    }
}
