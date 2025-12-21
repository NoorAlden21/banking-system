<?php

namespace App\Banking\CustomerSupport\Domain\Contracts;

interface SupportTicketReadRepository
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
        string $publicId,
        bool $canSeeInternal
    ): mixed;
}
