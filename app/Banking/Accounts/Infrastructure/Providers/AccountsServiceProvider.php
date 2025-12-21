<?php

namespace App\Banking\Accounts\Infrastructure\Providers;

use App\Banking\Accounts\Domain\Contracts\AccountFeatureRepository;
use Illuminate\Support\ServiceProvider;
use App\Banking\Accounts\Domain\Contracts\AccountRepository;
use App\Banking\Accounts\Domain\Services\AccountFeatureDecoratorBuilder;
use App\Banking\Accounts\Infrastructure\Persistence\Repositories\EloquentAccountFeatureRepository;
use App\Banking\Accounts\Infrastructure\Persistence\Repositories\EloquentAccountRepository;

class AccountsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccountRepository::class, EloquentAccountRepository::class);

        $this->app->bind(AccountFeatureRepository::class, EloquentAccountFeatureRepository::class);
        $this->app->singleton(AccountFeatureDecoratorBuilder::class);
    }

    public function boot(): void
    {
        //
    }
}
