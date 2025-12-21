<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Banking\Shared\Infrastructure\Providers\SharedServiceProvider;
use App\Banking\Accounts\Infrastructure\Providers\AccountsServiceProvider;
use App\Banking\Auth\Infrastructure\Providers\AuthServiceProvider;
use App\Banking\Transactions\Infrastructure\Providers\TransactionsServiceProvider;
use App\Banking\Reports\Infrastructure\Providers\ReportsServiceProvider;
use App\Banking\Admin\Infrastructure\Providers\AdminServiceProvider;
use App\Banking\Payments\Infrastructure\Providers\PaymentsServiceProvider;

final class BankingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(SharedServiceProvider::class);

        $this->app->register(AccountsServiceProvider::class);
        $this->app->register(AuthServiceProvider::class);
        $this->app->register(TransactionsServiceProvider::class);

        $this->app->register(ReportsServiceProvider::class);
        $this->app->register(AdminServiceProvider::class);

        $this->app->register(PaymentsServiceProvider::class);
    }
}
