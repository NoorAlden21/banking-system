<?php

namespace App\Banking\Reports\Application\UseCases;

use App\Banking\Reports\Domain\Contracts\ReportsReadRepository;

final class GenerateAccountSummariesReport
{
    public function __construct(private readonly ReportsReadRepository $reads)
    {
    }

    public function handle(int $perPage, int $page): array
    {
        return $this->reads->accountSummaries($perPage, $page);
    }
}
