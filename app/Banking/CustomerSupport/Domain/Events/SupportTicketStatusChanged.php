<?php

namespace App\Banking\CustomerSupport\Domain\Events;

final class SupportTicketStatusChanged
{
    public function __construct(
        public readonly string $ticketPublicId,
        public readonly int $ownerUserId,
        public readonly ?int $assignedToUserId,
        public readonly string $newStatus,
        public readonly int $changedByUserId,
    ) {
    }
}
