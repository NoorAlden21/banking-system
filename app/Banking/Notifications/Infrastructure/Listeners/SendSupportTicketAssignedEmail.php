<?php

namespace App\Banking\Notifications\Infrastructure\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Banking\CustomerSupport\Domain\Events\SupportTicketAssigned;
use App\Banking\Notifications\Infrastructure\Notifications\SupportTicketAssignedNotification;

final class SendSupportTicketAssignedEmail implements ShouldQueue
{
    public function handle(SupportTicketAssigned $e): void
    {
        $assignee = User::find($e->assignedToUserId);
        if ($assignee) {
            $assignee->notify(new SupportTicketAssignedNotification(
                ticketPublicId: $e->ticketPublicId,
            ));
        }

        $owner = User::find($e->ownerUserId);
        if ($owner) {
            $owner->notify(new SupportTicketAssignedNotification(
                ticketPublicId: $e->ticketPublicId,
            ));
        }
    }
}
