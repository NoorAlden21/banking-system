<?php

namespace App\Banking\Notifications\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

final class WelcomeCredentialsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $publicId,
        private readonly string $email,
        private readonly string $plainPassword,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to SW3 Banking')
            ->greeting('مرحبًا 👋')
            ->line('تم إنشاء حسابك في النظام البنكي.')
            ->line("Customer Public ID: {$this->publicId}")
            ->line("Email: {$this->email}")
            ->line("Temporary Password: {$this->plainPassword}")
            ->line('يرجى تغيير كلمة المرور بعد أول تسجيل دخول.');
    }
}
