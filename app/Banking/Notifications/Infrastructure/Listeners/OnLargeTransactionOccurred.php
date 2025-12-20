<?php

namespace App\Banking\Notifications\Infrastructure\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;

use App\Banking\Transactions\Domain\Events\LargeTransactionOccurred;
use App\Banking\Notifications\Infrastructure\Notifications\LargeTransactionAlertNotification;

final class OnLargeTransactionOccurred implements ShouldQueue
{
    public function handle(LargeTransactionOccurred $event): void
    {
        $roles = (array) Config::get('banking.alerts.risk_roles', ['admin', 'manager']);

        $targets = User::role($roles)->get();

        foreach ($targets as $u) {
            $u->notify(new LargeTransactionAlertNotification(
                transactionPublicId: $event->transactionPublicId,
                type: $event->type,
                amount: $event->amount,
                currency: $event->currency,
            ));
        }
    }
}
