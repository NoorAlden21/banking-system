<?php

namespace App\Banking\CustomerSupport\Application\UseCases;

use App\Banking\CustomerSupport\Domain\Contracts\SupportTicketReadRepository;

final class ShowSupportTicket
{
    public function __construct(private readonly SupportTicketReadRepository $read)
    {
    }

    public function handle(
        int $actorUserId,
        bool $canViewAll,
        string $scope,
        string $publicId,
        bool $canSeeInternal
    ): mixed {
        return $this->read->findDetail($actorUserId, $canViewAll, $scope, $publicId, $canSeeInternal);
    }
}
