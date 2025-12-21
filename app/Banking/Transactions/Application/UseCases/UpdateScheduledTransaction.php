<?php

namespace App\Banking\Transactions\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

use App\Banking\Transactions\Application\DTOs\UpdateScheduledTransactionData;
use App\Banking\Transactions\Domain\Contracts\ScheduledTransactionRepository;
use App\Banking\Transactions\Domain\Contracts\ScheduledTransactionReadRepository;
use App\Banking\Transactions\Domain\Services\ScheduledNextRunCalculator;

final class UpdateScheduledTransaction
{
    public function __construct(
        private readonly ScheduledTransactionRepository $repo,
        private readonly ScheduledTransactionReadRepository $read,
        private readonly ScheduledNextRunCalculator $calc,
    ) {
    }

    public function handle(int $actorUserId, bool $canOperateAny, string $publicId, UpdateScheduledTransactionData $data): array
    {
        return DB::transaction(function () use ($actorUserId, $canOperateAny, $publicId, $data) {

            $detail = $this->read->findDetail(
                actorUserId: $actorUserId,
                canViewAll: $canOperateAny,
                scope: $canOperateAny ? 'all' : 'mine',
                publicId: $publicId
            );

            if (!$detail) throw new \RuntimeException('غير موجود');

            if (($detail['status'] ?? null) === 'canceled') {
                throw new \RuntimeException('لا يمكن تعديل جدولة ملغاة');
            }

            $payload = array_filter([
                'amount' => $data->amount,
                'description' => $data->description,
                'frequency' => $data->frequency,
                'interval' => $data->interval,
                'day_of_week' => $data->dayOfWeek,
                'day_of_month' => $data->dayOfMonth,
                'run_time' => $data->runTime,
                'start_at' => $data->startAt,
                'end_at' => $data->endAt,
                'status' => $data->status,
            ], fn ($v) => $v !== null);

            $ruleChanged = isset($payload['frequency']) || isset($payload['interval']) || isset($payload['day_of_week'])
                || isset($payload['day_of_month']) || isset($payload['run_time']) || isset($payload['start_at']);

            if ($ruleChanged) {
                $now = CarbonImmutable::now('UTC');
                $startAt = isset($payload['start_at'])
                    ? CarbonImmutable::parse($payload['start_at'])
                    : (!empty($detail['start_at']) ? CarbonImmutable::parse($detail['start_at']) : null);

                $frequency = (string) ($payload['frequency'] ?? $detail['frequency']);
                $interval  = (int) ($payload['interval'] ?? $detail['interval']);
                $dow       = array_key_exists('day_of_week', $payload) ? $payload['day_of_week'] : ($detail['day_of_week'] ?? null);
                $dom       = array_key_exists('day_of_month', $payload) ? $payload['day_of_month'] : ($detail['day_of_month'] ?? null);
                $runTime   = (string) ($payload['run_time'] ?? $detail['run_time']);

                $next = $this->calc->initial($now, $startAt, $frequency, $interval, $dow, $dom, $runTime);
                $payload['next_run_at'] = $next->toDateTimeString();
            }

            $this->repo->updateByPublicId($publicId, $payload);

            return [
                'message' => 'تم تحديث الجدولة',
                'scheduled_public_id' => $publicId,
            ];
        });
    }
}
