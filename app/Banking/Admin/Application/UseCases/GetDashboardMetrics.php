<?php

namespace App\Banking\Admin\Application\UseCases;

use App\Banking\Admin\Domain\Contracts\MetricsReadRepository;

final class GetDashboardMetrics
{
    public function __construct(private readonly MetricsReadRepository $reads)
    {
    }

    public function handle(): array
    {
        return $this->reads->dashboard();
    }
}
