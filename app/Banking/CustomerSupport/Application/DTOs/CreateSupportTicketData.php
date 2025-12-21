<?php

namespace App\Banking\CustomerSupport\Application\DTOs;

final class CreateSupportTicketData
{
    public function __construct(
        public readonly string $subject,
        public readonly string $messageBody,
        public readonly ?string $category,
        public readonly string $priority,
    ) {
    }
}
