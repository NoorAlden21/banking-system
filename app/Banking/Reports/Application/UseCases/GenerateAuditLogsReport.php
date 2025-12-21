<?php

namespace App\Banking\Reports\Application\UseCases;

use App\Banking\Reports\Domain\Contracts\ReportsReadRepository;

final class GenerateAuditLogsReport
{
    public function __construct(private readonly ReportsReadRepository $reads)
    {
    }

    public function handle(array $filters, int $perPage, int $page): array
    {
        return $this->reads->auditLogs($filters, $perPage, $page);
    }
}
