<?php

namespace App\Banking\Transactions\Domain\Contracts;

use Illuminate\Support\Carbon;

interface ScheduledTransactionReadRepository
{
    public function paginate(
        int $actorUserId,
        bool $canViewAll,
        string $scope,
        array $filters,
        int $perPage,
        int $page
    ): array;

    public function findDetail(
        int $actorUserId,
        bool $canViewAll,
        string $scope,
        string $publicId
    ): ?array;

    public function listDue(Carbon $now, int $limit, Carbon $staleBefore): array;
}
