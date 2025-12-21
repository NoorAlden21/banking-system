<?php

namespace App\Banking\Reports\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

use App\Banking\Reports\Domain\Contracts\ReportsReadRepository;
use App\Banking\Reports\Infrastructure\Persistence\Repositories\EloquentReportsReadRepository;

final class ReportsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReportsReadRepository::class, EloquentReportsReadRepository::class);
    }

    public function boot(): void
    {
    }
}
