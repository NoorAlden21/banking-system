<?php

namespace App\Banking\Notifications\Infrastructure\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Banking\CustomerSupport\Domain\Events\SupportTicketStatusChanged;
use App\Banking\Notifications\Infrastructure\Notifications\SupportTicketStatusChangedNotification;

final class SendSupportTicketStatusChangedEmail implements ShouldQueue
{
    public function handle(SupportTicketStatusChanged $e): void
    {
        $owner = User::find($e->ownerUserId);
        if ($owner) {
            $owner->notify(new SupportTicketStatusChangedNotification(
                ticketPublicId: $e->ticketPublicId,
                status: $e->newStatus,
            ));
        }
    }
}
