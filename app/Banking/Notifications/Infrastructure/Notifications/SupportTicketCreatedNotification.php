<?php

namespace App\Banking\Notifications\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

final class SupportTicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $ticketPublicId,
        private readonly string $subject,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Support Ticket')
            ->greeting('Hello 👋')
            ->line('A new support ticket has been created.')
            ->line("Ticket ID: {$this->ticketPublicId}")
            ->line("Subject: {$this->subject}");
    }
}
