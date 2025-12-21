<?php

namespace App\Banking\Admin\Domain\Contracts;

interface AuditLogRepository
{
    public function create(array $data): void;
}
