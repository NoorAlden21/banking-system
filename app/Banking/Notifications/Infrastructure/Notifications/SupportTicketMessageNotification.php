<?php

namespace App\Banking\Notifications\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

final class SupportTicketMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $ticketPublicId,
        private readonly string $direction, // customer_replied | staff_replied | internal_note
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = match ($this->direction) {
            'staff_replied' => 'Support replied to your ticket',
            'customer_replied' => 'Customer replied to a ticket',
            'internal_note' => 'Internal note added to a ticket',
            default => 'Ticket message update',
        };

        return (new MailMessage)
            ->subject($title)
            ->greeting('Hello 👋')
            ->line("Ticket ID: {$this->ticketPublicId}")
            ->line('There is a new message on this ticket.');
    }
}
