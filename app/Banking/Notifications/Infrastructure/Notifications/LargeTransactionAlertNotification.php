<?php

namespace App\Banking\Notifications\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

final class LargeTransactionAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $transactionPublicId,
        private readonly string $type,
        private readonly string $amount,
        private readonly string $currency,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🚨 Large Transaction Alert')
            ->greeting('تنبيه')
            ->line('تم رصد عملية مالية كبيرة.')
            ->line("Transaction: {$this->transactionPublicId}")
            ->line("Type: {$this->type}")
            ->line("Amount: {$this->amount} {$this->currency}");
    }
}
