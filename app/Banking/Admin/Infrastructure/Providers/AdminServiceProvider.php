<?php

namespace App\Banking\Admin\Infrastructure\Providers;

use App\Banking\Admin\Domain\Contracts\AuditLogRepository;
use Illuminate\Support\ServiceProvider;

use App\Banking\Admin\Domain\Contracts\MetricsReadRepository;
use App\Banking\Admin\Infrastructure\Persistence\Repositories\EloquentMetricsReadRepository;
use Illuminate\Support\Facades\Event;

use App\Banking\Shared\Domain\Events\AuditOccurred;
use App\Banking\Admin\Infrastructure\Listeners\WriteAuditLog;
use App\Banking\Admin\Infrastructure\Persistence\Repositories\EloquentAuditLogRepository;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MetricsReadRepository::class, EloquentMetricsReadRepository::class);
        $this->app->bind(AuditLogRepository::class, EloquentAuditLogRepository::class);
    }

    public function boot(): void
    {
        Event::listen(AuditOccurred::class, WriteAuditLog::class);
    }
}
