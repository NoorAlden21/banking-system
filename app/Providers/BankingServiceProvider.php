<?php

namespace App\Providers;

use App\Banking\Accounts\Infrastructure\Providers\AccountsServiceProvider;
use Illuminate\Support\ServiceProvider;

class BankingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(AccountsServiceProvider::class);
    }

    public function boot(): void
    {
        //
    }
}
