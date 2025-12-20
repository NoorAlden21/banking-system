<?php

namespace App\Banking\Transactions\Domain\Patterns\ChainOfResponsibility;

use App\Banking\Transactions\Domain\Contracts\LockedAccount as ContractsLockedAccount;
use App\Banking\Transactions\Domain\Entities\LockedAccount;

final class TxContext
{
    public function __construct(
        public int $initiatorUserId,
        public bool $canOperateAny,

        public string $type, // deposit|withdraw|transfer
        public string $amount,
        public string $currency,
        public ?string $description,

        // ids (public) for locking
        public ?string $sourceAccountPublicId,
        public ?string $destinationAccountPublicId,

        // locked accounts
        public ?LockedAccount $source = null,
        public ?LockedAccount $dest = null,

        // for approvals posting (existing tx)
        public ?int $existingTransactionId = null,
        public ?string $existingTransactionPublicId = null,

        // output control
        public ?array $outcome = null, // payload array (message/status/tx id + data)
    ) {
    }

    public function isTransfer(): bool
    {
        return $this->type === 'transfer';
    }
    public function isWithdraw(): bool
    {
        return $this->type === 'withdraw';
    }
    public function isDeposit(): bool
    {
        return $this->type === 'deposit';
    }

    public function isApprovalPosting(): bool
    {
        return $this->existingTransactionId !== null && $this->existingTransactionPublicId !== null;
    }

    public function stopWith(array $payload): void
    {
        $this->outcome = $payload;
    }
}
