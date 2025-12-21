<?php

namespace App\Banking\Shared\Infrastructure\Audit;

use Illuminate\Support\Str;

use App\Banking\Shared\Domain\Contracts\AuditLogger;
use App\Banking\Shared\Application\DTOs\AuditEntryData;
use App\Banking\Shared\Infrastructure\Persistence\Models\AuditLogModel;

final class DbAuditLogger implements AuditLogger
{
    public function log(AuditEntryData $data): void
    {
        AuditLogModel::query()->create([
            'public_id' => (string) Str::uuid(),
            'actor_user_id' => $data->actorUserId,
            'actor_role' => $data->actorRole,
            'action' => $data->action,
            'subject_type' => $data->subjectType,
            'subject_public_id' => $data->subjectPublicId,
            'ip' => $data->ip,
            'user_agent' => $data->userAgent,
            'meta' => $data->meta ?: null,
            'created_at' => now(),
        ]);
    }
}
