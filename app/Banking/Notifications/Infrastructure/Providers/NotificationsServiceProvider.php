<?php

namespace App\Banking\Notifications\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

use App\Banking\Accounts\Domain\Events\CustomerOnboarded;
use App\Banking\Notifications\Infrastructure\Listeners\SendWelcomeCredentialsEmail;

use App\Banking\Transactions\Domain\Events\TransactionPosted;
use App\Banking\Notifications\Infrastructure\Listeners\OnTransactionPosted;

use App\Banking\Transactions\Domain\Events\LargeTransactionOccurred;
use App\Banking\Notifications\Infrastructure\Listeners\OnLargeTransactionOccurred;

final class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Event::listen(CustomerOnboarded::class, SendWelcomeCredentialsEmail::class);
        Event::listen(TransactionPosted::class, OnTransactionPosted::class);
        Event::listen(LargeTransactionOccurred::class, OnLargeTransactionOccurred::class);
    }
}
