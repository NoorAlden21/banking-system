<?php

namespace App\Banking\Transactions\Application\UseCases;

use App\Banking\Transactions\Domain\Contracts\ScheduledTransactionReadRepository;

final class ListScheduledTransactions
{
    public function __construct(private readonly ScheduledTransactionReadRepository $read)
    {
    }

    public function handle(
        int $actorUserId,
        bool $canViewAll,
        string $scope,
        array $filters,
        int $perPage,
        int $page
    ): array {
        return $this->read->paginate($actorUserId, $canViewAll, $scope, $filters, $perPage, $page);
    }
}
