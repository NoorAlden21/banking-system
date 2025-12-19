<?php

namespace App\Banking\Transactions\Infrastructure\Persistence\Repositories;

use App\Banking\Transactions\Domain\Contracts\TransactionRepository;
use App\Banking\Transactions\Domain\Entities\TransactionRecord;
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
}
