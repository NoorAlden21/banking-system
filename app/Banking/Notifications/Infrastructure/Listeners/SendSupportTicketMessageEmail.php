<?php

namespace App\Banking\Notifications\Infrastructure\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Banking\CustomerSupport\Domain\Events\SupportMessageAdded;
use App\Banking\Notifications\Infrastructure\Notifications\SupportTicketMessageNotification;

final class SendSupportTicketMessageEmail implements ShouldQueue
{
    public function handle(SupportMessageAdded $e): void
    {
        if ($e->isInternal) {
            $staff = User::permission('support.tickets.view-all')->get();
            foreach ($staff as $u) {
                $u->notify(new SupportTicketMessageNotification(
                    ticketPublicId: $e->ticketPublicId,
                    direction: 'internal_note',
                ));
            }
            return;
        }

        // if sender is staff -> notify owner
        if ($e->senderUserId !== $e->ownerUserId) {
            $owner = User::find($e->ownerUserId);
            if ($owner) {
                $owner->notify(new SupportTicketMessageNotification(
                    ticketPublicId: $e->ticketPublicId,
                    direction: 'staff_replied',
                ));
            }
            return;
        }

        // sender is owner -> notify assigned staff OR all staff
        if ($e->assignedToUserId) {
            $assignee = User::find($e->assignedToUserId);
            if ($assignee) {
                $assignee->notify(new SupportTicketMessageNotification(
                    ticketPublicId: $e->ticketPublicId,
                    direction: 'customer_replied',
                ));
            }
            return;
        }

        $staff = User::permission('support.tickets.view-all')->get();
        foreach ($staff as $u) {
            $u->notify(new SupportTicketMessageNotification(
                ticketPublicId: $e->ticketPublicId,
                direction: 'customer_replied',
            ));
        }
    }
}
