<?php

namespace App\Banking\Transactions\Domain\Contracts;

use App\Banking\Transactions\Domain\Entities\ScheduledTransactionRecord;
use App\Banking\Transactions\Domain\Entities\ScheduledTransactionForRun;
use Illuminate\Support\Carbon;

interface ScheduledTransactionRepository
{
    public function create(array $data): ScheduledTransactionRecord;

    public function updateByPublicId(string $publicId, array $data): void;

    public function cancelByPublicId(string $publicId): void;

    public function findForRunByPublicId(string $publicId): ?ScheduledTransactionForRun;

    /**
     * Claim due batch by setting locked_at to now (avoid double dispatch).
     * @return string[] publicIds
     */
    public function claimDueBatch(int $limit, int $staleMinutes = 10): array;

    public function acquireLock(int $id, Carbon $lockedAt, Carbon $staleBefore): bool;

    public function releaseLock(int $scheduledId): void;

    public function markRunSuccess(
        int $scheduledId,
        string $lastRunAt,
        string $nextRunAt,
        ?string $lastTxPublicId,
        string $lastStatus
    ): void;

    public function markRunFailure(
        int $scheduledId,
        string $error,
        string $nextRunAt
    ): void;
}
