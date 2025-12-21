<?php

namespace App\Banking\Admin\Infrastructure\Listeners;

use Illuminate\Support\Str;

use App\Banking\Shared\Domain\Events\AuditOccurred;
use App\Banking\Admin\Domain\Contracts\AuditLogRepository;

final class WriteAuditLog
{
    public function __construct(private readonly AuditLogRepository $repo)
    {
    }

    public function handle(AuditOccurred $e): void
    {
        $safeMeta = $e->meta;
        unset($safeMeta['plain_password'], $safeMeta['token'], $safeMeta['access_token']);

        $this->repo->create([
            'public_id'         => (string) Str::uuid(),
            'actor_user_id'     => $e->actorUserId,
            'actor_role'        => $e->actorRole,
            'action'            => $e->action,
            'subject_type'      => $e->subjectType,
            'subject_public_id' => $e->subjectPublicId,
            'ip'                => $e->ip,
            'user_agent'        => $e->userAgent,
            'meta'              => $safeMeta ?: null,
            'created_at'        => now(),
        ]);
    }
}
