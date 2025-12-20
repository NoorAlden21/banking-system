<?php

namespace App\Banking\Transactions\Infrastructure\Persistence\Repositories;

use Illuminate\Support\Facades\DB;
use App\Banking\Transactions\Domain\Contracts\LimitUsageRepository;
use App\Banking\Transactions\Infrastructure\Persistence\Models\TransactionModel;

final class EloquentLimitUsageRepository implements LimitUsageRepository
{
    public function sumOutflows(int $sourceAccountId, string $from, string $to): string
    {
        $sum = TransactionModel::query()
            ->where('source_account_id', $sourceAccountId)
            ->whereIn('type', ['withdraw', 'transfer'])
            ->where('status', 'posted')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        return number_format((float)$sum, 2, '.', '');
    }
}
