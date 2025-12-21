<?php

namespace App\Banking\Shared\Application\DTOs;

final class AuditEntryData
{
    public function __construct(
        public readonly ?int $actorUserId,
        public readonly ?string $actorRole,
        public readonly string $action,
        public readonly ?string $subjectType = null,
        public readonly ?string $subjectPublicId = null,
        public readonly ?string $ip = null,
        public readonly ?string $userAgent = null,
        public readonly array $meta = [],
    ) {
    }
}
