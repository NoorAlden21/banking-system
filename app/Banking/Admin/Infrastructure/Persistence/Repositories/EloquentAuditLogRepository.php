<?php

namespace App\Banking\Admin\Infrastructure\Persistence\Repositories;

use App\Banking\Admin\Domain\Contracts\AuditLogRepository;
use App\Banking\Shared\Infrastructure\Persistence\Models\AuditLogModel;

final class EloquentAuditLogRepository implements AuditLogRepository
{
    public function create(array $data): void
    {
        AuditLogModel::query()->create($data);
    }
}
