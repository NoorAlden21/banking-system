<?php

namespace App\Banking\Shared\Application\DTOs;

final class AuditEntryData
{
    public function __construct(
        public readonly int $actorUserId,
        public readonly ?string $actorRole,
        public readonly string $action,
        public readonly string $subjectType,
        public readonly ?string $subjectPublicId,
        public readonly ?string $ip,
        public readonly ?string $userAgent,
        public readonly array $meta = [],
    ) {
    }
}
