<?php

namespace App\Banking\CustomerSupport\Application\DTOs;

final class ChangeSupportTicketStatusData
{
    public function __construct(
        public readonly string $status,
    ) {
    }
}
