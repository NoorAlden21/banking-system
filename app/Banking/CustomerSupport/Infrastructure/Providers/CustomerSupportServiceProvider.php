<?php

namespace App\Banking\CustomerSupport\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

use App\Banking\CustomerSupport\Domain\Contracts\SupportTicketRepository;
use App\Banking\CustomerSupport\Domain\Contracts\SupportMessageRepository;
use App\Banking\CustomerSupport\Domain\Contracts\SupportTicketReadRepository;

use App\Banking\CustomerSupport\Infrastructure\Persistence\Repositories\EloquentSupportTicketRepository;
use App\Banking\CustomerSupport\Infrastructure\Persistence\Repositories\EloquentSupportMessageRepository;
use App\Banking\CustomerSupport\Infrastructure\Persistence\Repositories\EloquentSupportTicketReadRepository;

use App\Banking\CustomerSupport\Domain\Events\SupportTicketCreated;
use App\Banking\CustomerSupport\Domain\Events\SupportMessageAdded;
use App\Banking\CustomerSupport\Domain\Events\SupportTicketAssigned;
use App\Banking\CustomerSupport\Domain\Events\SupportTicketStatusChanged;

use App\Banking\Notifications\Infrastructure\Listeners\SendSupportTicketCreatedEmail;
use App\Banking\Notifications\Infrastructure\Listeners\SendSupportTicketMessageEmail;
use App\Banking\Notifications\Infrastructure\Listeners\SendSupportTicketAssignedEmail;
use App\Banking\Notifications\Infrastructure\Listeners\SendSupportTicketStatusChangedEmail;

final class CustomerSupportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SupportTicketRepository::class, EloquentSupportTicketRepository::class);
        $this->app->bind(SupportMessageRepository::class, EloquentSupportMessageRepository::class);
        $this->app->bind(SupportTicketReadRepository::class, EloquentSupportTicketReadRepository::class);
    }

    public function boot(): void
    {
        Event::listen(SupportTicketCreated::class, SendSupportTicketCreatedEmail::class);
        Event::listen(SupportMessageAdded::class, SendSupportTicketMessageEmail::class);
        Event::listen(SupportTicketAssigned::class, SendSupportTicketAssignedEmail::class);
        Event::listen(SupportTicketStatusChanged::class, SendSupportTicketStatusChangedEmail::class);
    }
}
