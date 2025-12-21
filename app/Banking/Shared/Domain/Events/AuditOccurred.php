<?php

namespace App\Banking\Shared\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AuditOccurred
{
    use Dispatchable, SerializesModels;

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
