<?php

namespace App\Banking\Transactions\Infrastructure\Queue\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RunScheduledTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $scheduledPublicId)
    {
        $this->onQueue('scheduled-transactions');
    }

    public function handle(): void
    {
        // هنا لاحقًا: UseCase RunScheduledTransaction
        // مؤقتًا: nothing
    }
}
