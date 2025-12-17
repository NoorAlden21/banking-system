<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Banking\Accounts\Domain\Events\CustomerOnboarded;
use App\Banking\Notifications\Infrastructure\Listeners\SendWelcomeCredentialsEmail;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CustomerOnboarded::class => [
            SendWelcomeCredentialsEmail::class,
        ],
    ];
}
