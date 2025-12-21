<?php

namespace App\Banking\Transactions\Infrastructure\Persistence\Repositories;

use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

use App\Banking\Transactions\Domain\Contracts\ScheduledTransactionRepository;
use App\Banking\Transactions\Domain\Entities\ScheduledTransactionRecord;
use App\Banking\Transactions\Domain\Entities\ScheduledTransactionForRun;

use App\Banking\Transactions\Infrastructure\Persistence\Models\ScheduledTransactionModel;
use App\Banking\Accounts\Infrastructure\Persistence\Models\AccountModel;
use Illuminate\Support\Carbon;

final class EloquentScheduledTransactionRepository implements ScheduledTransactionRepository
{
    public function create(array $data): ScheduledTransactionRecord
    {
        $m = ScheduledTransactionModel::query()->create($data);

        return new ScheduledTransactionRecord(
            id: (int) $m->id,
            publicId: (string) $m->public_id,
        );
    }

    public function updateByPublicId(string $publicId, array $data): void
    {
        ScheduledTransactionModel::query()
            ->where('public_id', $publicId)
            ->update($data);
    }

    public function cancelByPublicId(string $publicId): void
    {
        $m = ScheduledTransactionModel::query()->where('public_id', $publicId)->first();
        if (!$m) return;

        $m->status = 'canceled';
        $m->save();
        $m->delete();
    }

    public function claimDueBatch(int $limit, int $staleMinutes = 10): array
    {
        $now = now();
        $stale = now()->subMinutes($staleMinutes);

        return DB::transaction(function () use ($now, $stale, $limit) {

            $rows = ScheduledTransactionModel::query()
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->where('next_run_at', '<=', $now)
                ->where(function ($q) use ($stale) {
                    $q->whereNull('locked_at')
                        ->orWhere('locked_at', '<', $stale);
                })
                ->orderBy('next_run_at', 'asc')
                ->limit($limit)
                ->lockForUpdate()
                ->get(['id', 'public_id']);

            if ($rows->isEmpty()) return [];

            $ids = $rows->pluck('id')->all();

            ScheduledTransactionModel::query()
                ->whereIn('id', $ids)
                ->update(['locked_at' => $now]);

            return $rows->pluck('public_id')->map(fn ($v) => (string) $v)->all();
        });
    }

    public function acquireLock(int $id, Carbon $lockedAt, Carbon $staleBefore): bool
    {
        $affected = ScheduledTransactionModel::query()
            ->where('id', $id)
            ->where(function ($q) use ($staleBefore) {
                $q->whereNull('locked_at')
                    ->orWhere('locked_at', '<=', $staleBefore);
            })
            ->update(['locked_at' => $lockedAt]);

        return $affected === 1;
    }


    public function releaseLock(int $scheduledId): void
    {
        ScheduledTransactionModel::query()
            ->where('id', $scheduledId)
            ->update(['locked_at' => null]);
    }

    public function findForRunByPublicId(string $publicId): ?ScheduledTransactionForRun
    {
        // نحتاج public_id للحسابات عشان TransferData
        $m = ScheduledTransactionModel::query()
            ->where('public_id', $publicId)
            ->lockForUpdate()
            ->first();

        if (!$m) return null;

        $src = AccountModel::query()->find($m->source_account_id);
        $dst = AccountModel::query()->find($m->destination_account_id);

        if (!$src || !$dst) return null;

        return new ScheduledTransactionForRun(
            id: (int) $m->id,
            publicId: (string) $m->public_id,
            ownerUserId: (int) $m->owner_user_id,

            type: (string) $m->type,
            currency: (string) $m->currency,
            amount: (string) $m->amount,
            description: $m->description ? (string) $m->description : null,

            frequency: (string) $m->frequency,
            interval: (int) $m->interval,
            dayOfWeek: $m->day_of_week !== null ? (int) $m->day_of_week : null,
            dayOfMonth: $m->day_of_month !== null ? (int) $m->day_of_month : null,
            runTime: (string) $m->run_time,

            nextRunAt: (string) $m->next_run_at,
            endAt: $m->end_at ? (string) $m->end_at : null,

            sourceAccountPublicId: (string) $src->public_id,
            destinationAccountPublicId: (string) $dst->public_id,
        );
    }

    public function markRunSuccess(
        int $scheduledId,
        string $lastRunAt,
        string $nextRunAt,
        ?string $lastTxPublicId,
        string $lastStatus
    ): void {
        ScheduledTransactionModel::query()
            ->where('id', $scheduledId)
            ->update([
                'last_run_at' => $lastRunAt,
                'next_run_at' => $nextRunAt,
                'last_transaction_public_id' => $lastTxPublicId,
                'last_status' => $lastStatus,
                'last_error' => null,
                'runs_count' => DB::raw('runs_count + 1'),
                'locked_at' => null,
            ]);
    }

    public function markRunFailure(int $scheduledId, string $error, string $nextRunAt): void
    {
        ScheduledTransactionModel::query()
            ->where('id', $scheduledId)
            ->update([
                'last_error' => $error,
                'last_status' => 'failed',
                'next_run_at' => $nextRunAt,
                'locked_at' => null,
            ]);
    }
}
