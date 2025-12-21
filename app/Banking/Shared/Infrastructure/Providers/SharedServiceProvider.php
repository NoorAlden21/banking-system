<?php

namespace App\Banking\Shared\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

use App\Banking\Shared\Domain\Contracts\AuditLogger;
use App\Banking\Shared\Infrastructure\Audit\DbAuditLogger;
use App\Banking\Shared\Infrastructure\Audit\EventAuditLogger;

final class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuditLogger::class, EventAuditLogger::class);
    }
}
