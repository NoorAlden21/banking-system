<?php

namespace App\Banking\Reports\Application\UseCases;

use App\Banking\Reports\Domain\Contracts\ReportsReadRepository;

final class GenerateDailyTransactionsReport
{
    public function __construct(private readonly ReportsReadRepository $reads)
    {
    }

    public function handle(string $date): array
    {
        return $this->reads->dailyTransactions($date);
    }
}
