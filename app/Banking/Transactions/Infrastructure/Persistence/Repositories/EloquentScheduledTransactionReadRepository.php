<?php

namespace App\Banking\Transactions\Infrastructure\Persistence\Repositories;

use App\Banking\Transactions\Domain\Contracts\ScheduledTransactionReadRepository;
use App\Banking\Transactions\Domain\Entities\ScheduledDueRecord;
use App\Banking\Transactions\Infrastructure\Persistence\Models\ScheduledTransactionModel;
use Illuminate\Support\Carbon;

final class EloquentScheduledTransactionReadRepository implements ScheduledTransactionReadRepository
{
    public function paginate(
        int $actorUserId,
        bool $canViewAll,
        string $scope,
        array $filters,
        int $perPage,
        int $page
    ): array {
        $q = ScheduledTransactionModel::query()->whereNull('deleted_at');

        $scope = $scope ?: 'mine';
        if ($scope === 'all' && $canViewAll) {
            // staff view all
        } else {
            $q->where('owner_user_id', $actorUserId);
        }

        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['frequency'])) $q->where('frequency', $filters['frequency']);

        $q->orderByDesc('id');

        $p = $q->paginate(perPage: $perPage, page: $page);

        return [
            'data' => $p->items(),
            'meta' => [
                'page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
            ],
        ];
    }

    public function findDetail(
        int $actorUserId,
        bool $canViewAll,
        string $scope,
        string $publicId
    ): ?array {
        $q = ScheduledTransactionModel::query()
            ->where('public_id', $publicId)
            ->whereNull('deleted_at');

        if (!($scope === 'all' && $canViewAll)) {
            $q->where('owner_user_id', $actorUserId);
        }

        $m = $q->first();
        if (!$m) return null;

        return $m->toArray();
    }

    public function listDue(Carbon $now, int $limit, Carbon $staleBefore): array
    {
        $rows = ScheduledTransactionModel::query()
            ->where('status', 'active')
            ->where('next_run_at', '<=', $now)
            ->where(function ($q) use ($staleBefore) {
                $q->whereNull('locked_at')
                    ->orWhere('locked_at', '<=', $staleBefore);
            })
            ->orderBy('next_run_at')
            ->limit($limit)
            ->get(['id', 'public_id']);

        $out = [];
        foreach ($rows as $r) {
            $out[] = new ScheduledDueRecord(
                id: (int) $r->id,
                publicId: (string) $r->public_id,
            );
        }

        return $out;
    }
}
