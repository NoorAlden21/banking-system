<?php

namespace App\Banking\CustomerSupport\Application\UseCases;

use App\Banking\CustomerSupport\Domain\Contracts\SupportTicketReadRepository;

final class ListSupportTickets
{
    public function __construct(private readonly SupportTicketReadRepository $read)
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
