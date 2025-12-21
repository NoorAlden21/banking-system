<?php

namespace App\Banking\Shared\Infrastructure\Audit;

use App\Banking\Shared\Domain\Contracts\AuditLogger;
use App\Banking\Shared\Application\DTOs\AuditEntryData;
use App\Banking\Shared\Domain\Events\AuditOccurred;

final class EventAuditLogger implements AuditLogger
{
    public function log(AuditEntryData $data): void
    {
        event(new AuditOccurred(
            actorUserId: $data->actorUserId,
            actorRole: $data->actorRole,
            action: $data->action,
            subjectType: $data->subjectType,
            subjectPublicId: $data->subjectPublicId,
            ip: $data->ip,
            userAgent: $data->userAgent,
            meta: $data->meta ?? [],
        ));
    }
}
