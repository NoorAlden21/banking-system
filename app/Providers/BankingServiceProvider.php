<?php

namespace App\Providers;

use App\Banking\Accounts\Infrastructure\Providers\AccountsServiceProvider;
use App\Banking\Auth\Infrastructure\Providers\AuthServiceProvider;
use Illuminate\Support\ServiceProvider;

class BankingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(AccountsServiceProvider::class);
        $this->app->register(AuthServiceProvider::class);
    }

    public function boot(): void
    {
        //
    }
}
