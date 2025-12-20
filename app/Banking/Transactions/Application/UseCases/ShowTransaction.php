<?php

namespace App\Banking\Transactions\Application\UseCases;

use App\Banking\Transactions\Domain\Contracts\TransactionReadRepository;

final class ShowTransaction
{
    public function __construct(private readonly TransactionReadRepository $reads)
    {
    }

    public function handle(int $actorUserId, bool $canViewAll, string $publicId, string $scope): ?array
    {
        if ($scope === 'all') {
            if (!$canViewAll) throw new \RuntimeException('Forbidden');
            return $this->reads->findDetail($publicId);
        }

        return $this->reads->findDetailForUser($publicId, $actorUserId);
    }
}
