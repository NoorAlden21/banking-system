<?php

namespace App\Banking\Transactions\Infrastructure\Persistence\Repositories;

use App\Banking\Transactions\Domain\Contracts\LedgerRepository;
use App\Banking\Transactions\Infrastructure\Persistence\Models\LedgerEntryModel;

final class EloquentLedgerRepository implements LedgerRepository
{
    public function create(array $data): void
    {
        LedgerEntryModel::query()->create($data);
    }
}
