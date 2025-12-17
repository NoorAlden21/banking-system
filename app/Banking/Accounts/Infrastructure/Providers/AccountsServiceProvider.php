<?php

namespace App\Banking\Accounts\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use App\Banking\Accounts\Domain\Contracts\AccountRepository;
use App\Banking\Accounts\Infrastructure\Persistence\Repositories\EloquentAccountRepository;

class AccountsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccountRepository::class, EloquentAccountRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
