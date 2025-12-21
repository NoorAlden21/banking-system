<?php

namespace App\Banking\Admin\Domain\Contracts;

interface MetricsReadRepository
{
    public function dashboard(): array;
}
