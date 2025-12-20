<?php

namespace App\Banking\Notifications\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

final class TransactionPostedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $transactionPublicId,
        private readonly string $type,
        private readonly string $amount,
        private readonly string $currency,
        private readonly string $status,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تأكيد عملية مالية')
            ->greeting('مرحبًا 👋')
            ->line('تم ترحيل عملية مالية بنجاح.')
            ->line("Transaction: {$this->transactionPublicId}")
            ->line("Type: {$this->type}")
            ->line("Amount: {$this->amount} {$this->currency}")
            ->line("Status: {$this->status}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'transaction_public_id' => $this->transactionPublicId,
            'type' => $this->type,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
        ];
    }
}
