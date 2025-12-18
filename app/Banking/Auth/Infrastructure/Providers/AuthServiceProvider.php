<?php

namespace App\Banking\Auth\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use App\Banking\Auth\Domain\Contracts\AuthService;
use App\Banking\Auth\Infrastructure\Services\SanctumAuthService;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthService::class, SanctumAuthService::class);
    }
}
