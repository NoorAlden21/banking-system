<?php

namespace App\Banking\Reports\Domain\Contracts;

interface ReportsReadRepository
{
    public function dailyTransactions(string $date): array;

    public function accountSummaries(int $perPage, int $page): array;

    public function auditLogs(array $filters, int $perPage, int $page): array;
}
