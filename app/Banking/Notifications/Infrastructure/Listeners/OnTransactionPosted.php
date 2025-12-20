<?php

namespace App\Banking\Notifications\Infrastructure\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;

use App\Models\User;
use App\Banking\Transactions\Domain\Events\TransactionPosted;
use App\Banking\Transactions\Infrastructure\Persistence\Models\TransactionModel;
use App\Banking\Accounts\Infrastructure\Persistence\Models\AccountModel;
use App\Banking\Notifications\Infrastructure\Notifications\TransactionPostedNotification;

final class OnTransactionPosted implements ShouldQueue
{
    public function handle(TransactionPosted $event): void
    {
        $tx = TransactionModel::query()
            ->where('public_id', $event->transactionPublicId)
            ->first();

        if (!$tx) return;

        $accountIds = array_filter([$tx->source_account_id, $tx->destination_account_id]);
        if (!$accountIds) return;

        $users = AccountModel::query()
            ->whereIn('id', $accountIds)
            ->with('user:id,email,name')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->values();

        foreach ($users as $user) {
            $user->notify(new TransactionPostedNotification(
                transactionPublicId: (string) $tx->public_id,
                type: (string) $tx->type,
                amount: (string) $tx->amount,
                currency: (string) $tx->currency,
                status: (string) $tx->status,
            ));
        }
    }
}
