<?php

namespace App\Banking\Transactions\Application\UseCases;

use App\Banking\Transactions\Domain\Contracts\TransactionReadRepository;

final class ListTransactions
{
    public function __construct(private readonly TransactionReadRepository $reads)
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
        if ($scope === 'all') {
            if (!$canViewAll) {
                throw new \RuntimeException('Forbidden');
            }
            return $this->reads->paginateAll($filters, $perPage, $page);
        }

        return $this->reads->paginateForUser($actorUserId, $filters, $perPage, $page);
    }
}
