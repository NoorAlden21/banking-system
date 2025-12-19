<?php

namespace App\Banking\Transactions\Domain\Contracts;

interface ApprovalRepository
{
    public function createPending(int $transactionId, int $requestedByUserId): void;
}
