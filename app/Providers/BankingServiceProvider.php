<?php

namespace App\Providers;

use App\Banking\Accounts\Infrastructure\Providers\AccountsServiceProvider;
use App\Banking\Auth\Infrastructure\Providers\AuthServiceProvider;
use App\Banking\Transactions\Infrastructure\Providers\TransactionsServiceProvider;
use Illuminate\Support\ServiceProvider;

class BankingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(AccountsServiceProvider::class);
        $this->app->register(AuthServiceProvider::class);
        $this->app->register(TransactionsServiceProvider::class);
    }

    public function boot(): void
    {
        //
    }
}
