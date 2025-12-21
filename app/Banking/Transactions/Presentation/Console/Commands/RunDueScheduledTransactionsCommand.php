<?php

namespace App\Banking\Transactions\Presentation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

use App\Banking\Transactions\Domain\Contracts\ScheduledTransactionReadRepository;
use App\Banking\Transactions\Domain\Contracts\ScheduledTransactionRepository;
use App\Banking\Transactions\Infrastructure\Queue\Jobs\RunScheduledTransactionJob;

final class RunDueScheduledTransactionsCommand extends Command
{
    protected $signature = 'banking:scheduled:run-due {--limit=100 : Max due items to dispatch}';
    protected $description = 'Dispatch due scheduled transactions to queue';

    public function __construct(
        private readonly ScheduledTransactionReadRepository $reads,
        private readonly ScheduledTransactionRepository $writes,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $now = now();

        $staleMinutes = (int) Config::get('banking.scheduled.lock_stale_minutes', 15);
        $staleBefore = $now->copy()->subMinutes($staleMinutes);

        $due = $this->reads->listDue($now, $limit, $staleBefore);

        if (count($due) === 0) {
            $this->info('No due scheduled transactions.');
            return self::SUCCESS;
        }

        $dispatched = 0;
        foreach ($due as $item) {
            $locked = $this->writes->acquireLock($item->id, $now, $staleBefore);
            if (!$locked) {
                continue;
            }

            RunScheduledTransactionJob::dispatch($item->publicId)
                ->onQueue('scheduled-transactions');

            $dispatched++;
        }

        $this->info("DueFound=" . count($due) . " Dispatched={$dispatched}");
        return self::SUCCESS;
    }
}
