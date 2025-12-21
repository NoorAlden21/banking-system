<?php

namespace App\Banking\CustomerSupport\Domain\Events;

final class SupportMessageAdded
{
    public function __construct(
        public readonly string $ticketPublicId,
        public readonly int $ownerUserId,
        public readonly ?int $assignedToUserId,
        public readonly int $senderUserId,
        public readonly bool $isInternal,
    ) {
    }
}
