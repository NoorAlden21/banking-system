<?php

namespace App\Banking\CustomerSupport\Domain\Entities;

final class SupportTicketForUpdate
{
    public function __construct(
        public readonly int $id,
        public readonly string $publicId,
        public readonly int $ownerUserId,
        public readonly ?int $assignedToUserId,
        public readonly string $status,
        public readonly string $subject,
    ) {
    }
}
