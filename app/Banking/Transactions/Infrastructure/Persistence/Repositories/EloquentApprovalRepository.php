<?php

namespace App\Banking\Transactions\Infrastructure\Persistence\Repositories;

use App\Banking\Transactions\Domain\Contracts\ApprovalRepository;
use App\Banking\Transactions\Domain\Entities\ApprovalRecord;
use App\Banking\Transactions\Infrastructure\Persistence\Models\TransactionApprovalModel;

final class EloquentApprovalRepository implements ApprovalRepository
{
    public function createPending(int $transactionId, int $requestedByUserId): void
    {
        $exists = TransactionApprovalModel::query()
            ->where('transaction_id', $transactionId)
            ->exists();

        if ($exists) return;

        TransactionApprovalModel::query()->create([
            'transaction_id' => $transactionId,
            'status' => 'pending',
            'requested_by_user_id' => $requestedByUserId,
            'decided_by_user_id' => null,
            'reason' => null,
            'decided_at' => null,
        ]);
    }

    public function lockPendingByTransactionId(int $transactionId): ?ApprovalRecord
    {
        $m = TransactionApprovalModel::query()
            ->where('transaction_id', $transactionId)
            ->where('status', 'pending')
            ->lockForUpdate()
            ->first();

        if (!$m) return null;

        return new ApprovalRecord(
            id: (int) $m->id,
            transactionId: (int) $m->transaction_id,
            status: (string) $m->status,
            requestedByUserId: (int) $m->requested_by_user_id,
        );
    }

    public function markApproved(int $approvalId, int $decidedByUserId, ?string $note = null): void
    {
        TransactionApprovalModel::query()
            ->where('id', $approvalId)
            ->update([
                'status' => 'approved',
                'decided_by_user_id' => $decidedByUserId,
                'reason' => $note,
                'decided_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function markRejected(int $approvalId, int $decidedByUserId, ?string $note = null): void
    {
        TransactionApprovalModel::query()
            ->where('id', $approvalId)
            ->update([
                'status' => 'rejected',
                'decided_by_user_id' => $decidedByUserId,
                'reason' => $note,
                'decided_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
