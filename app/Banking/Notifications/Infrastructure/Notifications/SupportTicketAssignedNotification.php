<?php

namespace App\Banking\Notifications\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

final class SupportTicketAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $ticketPublicId)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ticket Assignment Update')
            ->greeting('Hello 👋')
            ->line("Ticket ID: {$this->ticketPublicId}")
            ->line('This ticket has been assigned/updated.');
    }
}
