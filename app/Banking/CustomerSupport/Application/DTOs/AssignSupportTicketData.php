<?php

namespace App\Banking\CustomerSupport\Application\DTOs;

final class AssignSupportTicketData
{
    public function __construct(
        public readonly int $assignedToUserId,
    ) {
    }
}
