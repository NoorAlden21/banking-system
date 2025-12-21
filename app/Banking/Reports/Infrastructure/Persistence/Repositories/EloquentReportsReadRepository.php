<?php

namespace App\Banking\Reports\Infrastructure\Persistence\Repositories;

use Illuminate\Support\Facades\DB;

use App\Banking\Accounts\Infrastructure\Persistence\Models\AccountModel;
use App\Banking\Transactions\Infrastructure\Persistence\Models\TransactionModel;
use App\Banking\Shared\Infrastructure\Persistence\Models\AuditLogModel;

use App\Banking\Reports\Domain\Contracts\ReportsReadRepository;

final class EloquentReportsReadRepository implements ReportsReadRepository
{
    public function dailyTransactions(string $date): array
    {
        // date = YYYY-MM-DD
        $rows = TransactionModel::query()
            ->select([
                'type',
                'status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount'),
            ])
            ->whereDate('created_at', $date)
            ->groupBy('type', 'status')
            ->orderBy('type')
            ->orderBy('status')
            ->get()
            ->toArray();

        return [
            'date' => $date,
            'rows' => $rows,
        ];
    }

    public function accountSummaries(int $perPage, int $page): array
    {
        $q = AccountModel::query()
            ->with(['user:id,name,email'])
            ->where('type', '!=', 'group')
            ->orderByDesc('balance');

        $p = $q->paginate(perPage: $perPage, page: $page);

        return [
            'data' => $p->items(),
            'meta' => [
                'total' => $p->total(),
                'per_page' => $p->perPage(),
                'current_page' => $p->currentPage(),
                'last_page' => $p->lastPage(),
            ],
        ];
    }

    public function auditLogs(array $filters, int $perPage, int $page): array
    {
        $q = AuditLogModel::query()->orderByDesc('created_at');

        if (!empty($filters['action'])) $q->where('action', $filters['action']);
        if (!empty($filters['actor_user_id'])) $q->where('actor_user_id', (int) $filters['actor_user_id']);
        if (!empty($filters['subject_public_id'])) $q->where('subject_public_id', $filters['subject_public_id']);
        if (!empty($filters['from'])) $q->where('created_at', '>=', $filters['from']);
        if (!empty($filters['to'])) $q->where('created_at', '<=', $filters['to']);

        $p = $q->paginate(perPage: $perPage, page: $page);

        return [
            'data' => $p->items(),
            'meta' => [
                'total' => $p->total(),
                'per_page' => $p->perPage(),
                'current_page' => $p->currentPage(),
                'last_page' => $p->lastPage(),
            ],
        ];
    }
}
