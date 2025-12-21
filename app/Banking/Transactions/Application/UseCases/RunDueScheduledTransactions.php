<?php

namespace App\Banking\Transactions\Application\UseCases;

use App\Banking\Transactions\Domain\Contracts\ScheduledTransactionRepository;
use App\Banking\Transactions\Infrastructure\Queue\Jobs\RunScheduledTransactionJob;

final class RunDueScheduledTransactions
{
    public function __construct(private readonly ScheduledTransactionRepository $repo)
    {
    }

    public function handle(int $batch = 50): array
    {
        $publicIds = $this->repo->claimDueBatch($batch);

        foreach ($publicIds as $pid) {
            RunScheduledTransactionJob::dispatch($pid);
        }

        return [
            'message' => 'تم جدولة تنفيذ الدفعة',
            'count' => count($publicIds),
        ];
    }
}
