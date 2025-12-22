<?php

namespace App\Banking\Accounts\Infrastructure\Providers;

use App\Banking\Accounts\Domain\Contracts\AccountFeatureRepository;
use Illuminate\Support\ServiceProvider;
use App\Banking\Accounts\Domain\Contracts\AccountRepository;
use App\Banking\Accounts\Domain\Services\AccountFeatureDecoratorBuilder;
use App\Banking\Accounts\Infrastructure\Persistence\Repositories\EloquentAccountFeatureRepository;
use App\Banking\Accounts\Infrastructure\Persistence\Repositories\EloquentAccountRepository;

use App\Banking\Accounts\Domain\Services\Interest\MarketConditionProvider;
use App\Banking\Accounts\Infrastructure\Services\Interest\ConfigMarketConditionProvider;

use App\Banking\Accounts\Domain\Services\Interest\InterestCalculator;
use App\Banking\Accounts\Domain\Services\Interest\InterestStrategyResolver;

use App\Banking\Accounts\Domain\Patterns\Strategy\Strategies\SavingsInterestStrategy;
use App\Banking\Accounts\Domain\Patterns\Strategy\Strategies\CheckingInterestStrategy;
use App\Banking\Accounts\Domain\Patterns\Strategy\Strategies\InvestmentInterestStrategy;
use App\Banking\Accounts\Domain\Patterns\Strategy\Strategies\LoanInterestStrategy;

class AccountsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AccountRepository::class, EloquentAccountRepository::class);

        $this->app->bind(AccountFeatureRepository::class, EloquentAccountFeatureRepository::class);
        $this->app->singleton(AccountFeatureDecoratorBuilder::class);

        $this->app->bind(MarketConditionProvider::class, ConfigMarketConditionProvider::class);

        $this->app->singleton(SavingsInterestStrategy::class);
        $this->app->singleton(CheckingInterestStrategy::class);
        $this->app->singleton(InvestmentInterestStrategy::class);
        $this->app->singleton(LoanInterestStrategy::class);

        $this->app->singleton(InterestStrategyResolver::class);
        $this->app->singleton(InterestCalculator::class);
    }

    public function boot(): void
    {
        //
    }
}
