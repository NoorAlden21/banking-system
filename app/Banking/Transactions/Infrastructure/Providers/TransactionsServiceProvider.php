<?php

namespace App\Banking\Transactions\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

use App\Banking\Transactions\Domain\Contracts\TransactionRepository;
use App\Banking\Transactions\Domain\Contracts\LedgerRepository;
use App\Banking\Transactions\Domain\Contracts\AccountGateway;
use App\Banking\Transactions\Domain\Contracts\ApprovalRepository;

use App\Banking\Transactions\Infrastructure\Persistence\Repositories\EloquentTransactionRepository;
use App\Banking\Transactions\Infrastructure\Persistence\Repositories\EloquentLedgerRepository;
use App\Banking\Transactions\Infrastructure\Persistence\Repositories\EloquentApprovalRepository;
use App\Banking\Transactions\Infrastructure\Persistence\Gateways\EloquentAccountGateway;

use App\Banking\Transactions\Application\Facades\BankingFacade;

final class TransactionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TransactionRepository::class, EloquentTransactionRepository::class);
        $this->app->bind(LedgerRepository::class, EloquentLedgerRepository::class);

        $this->app->bind(AccountGateway::class, EloquentAccountGateway::class);
        $this->app->bind(ApprovalRepository::class, EloquentApprovalRepository::class);

        // Facade كـConcrete class (الـcontainer هيحقنه عادي)
        $this->app->singleton(BankingFacade::class, BankingFacade::class);
    }
}
