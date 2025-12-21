<?php

namespace App\Banking\Transactions\Application\UseCases;

use App\Banking\Transactions\Domain\Contracts\ScheduledTransactionReadRepository;

final class ShowScheduledTransaction
{
    public function __construct(private readonly ScheduledTransactionReadRepository $read)
    {
    }

    public function handle(int $actorUserId, bool $canViewAll, string $scope, string $publicId): ?array
    {
        return $this->read->findDetail($actorUserId, $canViewAll, $scope, $publicId);
    }
}
