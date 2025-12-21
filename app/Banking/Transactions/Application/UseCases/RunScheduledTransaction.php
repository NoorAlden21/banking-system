<?php

namespace App\Banking\Transactions\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

use App\Banking\Transactions\Application\Facades\BankingFacade;
use App\Banking\Transactions\Application\DTOs\TransferData;

use App\Banking\Transactions\Domain\Contracts\ScheduledTransactionRepository;
use App\Banking\Transactions\Domain\Services\ScheduledNextRunCalculator;

use App\Banking\Transactions\Infrastructure\Persistence\Repositories\IdempotencyStore;

final class RunScheduledTransaction
{
    public function __construct(
        private readonly ScheduledTransactionRepository $repo,
        private readonly ScheduledNextRunCalculator $calc,
        private readonly BankingFacade $banking,
        private readonly IdempotencyStore $idem,
    ) {
    }

    public function handle(string $scheduledPublicId): void
    {
        $retryMinutes = (int) config('banking.scheduled.retry_minutes', 60);

        DB::transaction(function () use ($scheduledPublicId, $retryMinutes) {

            $sched = $this->repo->findForRunByPublicId($scheduledPublicId);
            if (!$sched) return;

            $now = CarbonImmutable::now();
            $dueAt = CarbonImmutable::parse($sched->nextRunAt);

            // تأكد أنه فعلاً due وما هو canceled/paused
            // (claimDueBatch أصلاً عمل filter + lock، بس نضاعف الأمان)
            if ($dueAt->greaterThan($now)) {
                $this->repo->releaseLock($sched->id);
                return;
            }

            if ($sched->endAt && CarbonImmutable::parse($sched->endAt)->lessThan($now)) {
                // انتهى
                $this->repo->markRunFailure($sched->id, 'schedule ended', $now->addYears(100)->toDateTimeString());
                return;
            }

            // Idempotency key ثابت لهذه “الدورة”
            $action = 'transfer';
            $idemKey = 'sched:' . $sched->publicId . ':' . $dueAt->format('YmdHis');
            $hash = hash('sha256', json_encode([
                $sched->sourceAccountPublicId,
                $sched->destinationAccountPublicId,
                $sched->amount,
                $sched->description
            ], JSON_UNESCAPED_UNICODE));

            $idemRow = $this->idem->start($sched->ownerUserId, $action, $idemKey, $hash);

            // لو حصل replay، نعتبرها تمت ونحرّك next_run_at
            if ($idemRow->response_code && $idemRow->response_body) {
                $next = $this->calc->next(
                    base: $dueAt,
                    frequency: $sched->frequency,
                    interval: $sched->interval,
                    dayOfWeek: $sched->dayOfWeek,
                    dayOfMonth: $sched->dayOfMonth,
                    runTime: $sched->runTime,
                );

                $this->repo->markRunSuccess(
                    scheduledId: $sched->id,
                    lastRunAt: $now->toDateTimeString(),
                    nextRunAt: $next->toDateTimeString(),
                    lastTxPublicId: $idemRow->transaction_public_id ? (string) $idemRow->transaction_public_id : null,
                    lastStatus: 'replayed',
                );
                return;
            }

            try {
                $dto = new TransferData(
                    sourceAccountPublicId: $sched->sourceAccountPublicId,
                    destinationAccountPublicId: $sched->destinationAccountPublicId,
                    amount: $sched->amount,
                    description: $sched->description
                );

                // ownerUserId هو initiator
                // canOperateAny = false لأن ده “عميل”
                $outcome = $this->banking->transfer($sched->ownerUserId, $dto, false);

                $payload = [
                    'message' => $outcome->message,
                    'transaction_public_id' => $outcome->transactionPublicId,
                    'status' => $outcome->status,
                ] + $outcome->data;

                $code = ($outcome->status === 'pending_approval') ? 202 : 201;

                $this->idem->storeResponse(
                    $idemRow,
                    $code,
                    json_encode($payload, JSON_UNESCAPED_UNICODE),
                    $outcome->transactionPublicId
                );

                $next = $this->calc->next(
                    base: $dueAt,
                    frequency: $sched->frequency,
                    interval: $sched->interval,
                    dayOfWeek: $sched->dayOfWeek,
                    dayOfMonth: $sched->dayOfMonth,
                    runTime: $sched->runTime,
                );

                $this->repo->markRunSuccess(
                    scheduledId: $sched->id,
                    lastRunAt: $now->toDateTimeString(),
                    nextRunAt: $next->toDateTimeString(),
                    lastTxPublicId: $outcome->transactionPublicId ?: null,
                    lastStatus: $outcome->status,
                );
            } catch (\Throwable $e) {
                $nextRetry = $now->addMinutes($retryMinutes);
                $this->repo->markRunFailure($sched->id, $e->getMessage(), $nextRetry->toDateTimeString());
            }
        });
    }
}
