<?php

namespace App\Banking\Transactions\Infrastructure\Persistence\Repositories;

use App\Banking\Transactions\Domain\Contracts\ApprovalRepository;
use App\Banking\Transactions\Infrastructure\Persistence\Models\TransactionApprovalModel;

final class EloquentApprovalRepository implements ApprovalRepository
{
    public function createPending(int $transactionId, int $requestedByUserId): void
    {
        TransactionApprovalModel::query()->create([
            'transaction_id' => $transactionId,
            'status' => 'pending',
            'requested_by_user_id' => $requestedByUserId,
        ]);
    }
}
