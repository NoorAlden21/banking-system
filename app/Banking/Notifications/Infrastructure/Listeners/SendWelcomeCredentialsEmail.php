<?php

namespace App\Banking\Notifications\Infrastructure\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Banking\Accounts\Domain\Events\CustomerOnboarded;
use App\Banking\Notifications\Infrastructure\Notifications\WelcomeCredentialsNotification;

final class SendWelcomeCredentialsEmail implements ShouldQueue
{
    public function handle(CustomerOnboarded $event): void
    {
        $user = User::find($event->userId);

        if (!$user) {
            return;
        }

        $user->notify(new WelcomeCredentialsNotification(
            publicId: $event->userPublicId,
            email: $event->email,
            plainPassword: $event->plainPassword,
        ));
    }
}
