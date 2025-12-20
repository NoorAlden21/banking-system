<?php

namespace App\Banking\Transactions\Domain\Contracts;

use App\Banking\Transactions\Domain\Entities\ApprovalRecord;

interface ApprovalRepository
{
    public function createPending(int $transactionId, int $requestedByUserId): void;

    public function lockPendingByTransactionId(int $transactionId): ?ApprovalRecord;

    public function markApproved(int $approvalId, int $decidedByUserId, ?string $note = null): void;

    public function markRejected(int $approvalId, int $decidedByUserId, ?string $note = null): void;
}
