<?php

namespace App\Banking\Transactions\Infrastructure\Persistence\Repositories;

use App\Banking\Transactions\Domain\Contracts\TransactionRepository;
use App\Banking\Transactions\Domain\Entities\TransactionForPosting;
use App\Banking\Transactions\Domain\Entities\TransactionRecord;
use App\Banking\Transactions\Infrastructure\Persistence\Models\TransactionApprovalModel;
use App\Banking\Transactions\Infrastructure\Persistence\Models\TransactionModel;

final class EloquentTransactionRepository implements TransactionRepository
{
    public function create(array $data): TransactionRecord
    {
        $m = TransactionModel::query()->create($data);

        return new TransactionRecord(
            id: (int) $m->id,
            publicId: (string) $m->public_id,
            status: (string) $m->status,
        );
    }

    public function findByPublicId(string $publicId): ?TransactionRecord
    {
        $m = TransactionModel::query()->where('public_id', $publicId)->first();
        if (!$m) return null;

        return new TransactionRecord(
            id: (int) $m->id,
            publicId: (string) $m->public_id,
            status: (string) $m->status,
        );
    }

    public function lockForPostingByPublicId(string $publicId): ?TransactionForPosting
    {
        $m = TransactionModel::query()
            ->where('public_id', $publicId)
            ->lockForUpdate()
            ->first();

        if (!$m) return null;

        return new TransactionForPosting(
            id: (int) $m->id,
            publicId: (string) $m->public_id,
            type: (string) $m->type,
            status: (string) $m->status,
            sourceAccountId: $m->source_account_id ? (int) $m->source_account_id : null,
            destinationAccountId: $m->destination_account_id ? (int) $m->destination_account_id : null,
            amount: (string) $m->amount,
            currency: (string) $m->currency,
            description: $m->description,
        );
    }

    public function markPosted(int $transactionId): void
    {
        TransactionModel::query()
            ->where('id', $transactionId)
            ->update([
                'status' => 'posted',
                'posted_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function markRejected(int $transactionId): void
    {
        TransactionModel::query()
            ->where('id', $transactionId)
            ->update([
                'status' => 'rejected',
                'updated_at' => now(),
            ]);
    }
}
