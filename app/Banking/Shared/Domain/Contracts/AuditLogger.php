<?php

namespace App\Banking\Shared\Domain\Contracts;

use App\Banking\Shared\Application\DTOs\AuditEntryData;

interface AuditLogger
{
    public function log(AuditEntryData $data): void;
}
