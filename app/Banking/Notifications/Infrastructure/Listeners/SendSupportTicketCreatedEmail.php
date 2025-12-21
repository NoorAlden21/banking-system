<?php

namespace App\Banking\Notifications\Infrastructure\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Banking\CustomerSupport\Domain\Events\SupportTicketCreated;
use App\Banking\Notifications\Infrastructure\Notifications\SupportTicketCreatedNotification;

final class SendSupportTicketCreatedEmail implements ShouldQueue
{
    public function handle(SupportTicketCreated $e): void
    {
        // notify staff (anyone who can view-all tickets)
        $staff = User::permission('support.tickets.view-all')->get();

        foreach ($staff as $u) {
            $u->notify(new SupportTicketCreatedNotification(
                ticketPublicId: $e->ticketPublicId,
                subject: $e->subject,
            ));
        }
    }
}
