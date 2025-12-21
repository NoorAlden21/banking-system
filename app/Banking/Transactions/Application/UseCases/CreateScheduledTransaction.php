<?php

namespace App\Banking\Transactions\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

use App\Banking\Transactions\Application\DTOs\CreateScheduledTransactionData;
use App\Banking\Transactions\Domain\Contracts\ScheduledTransactionRepository;
use App\Banking\Transactions\Domain\Services\ScheduledNextRunCalculator;

use App\Banking\Accounts\Infrastructure\Persistence\Models\AccountModel;

final class CreateScheduledTransaction
{
    public function __construct(
        private readonly ScheduledTransactionRepository $repo,
        private readonly ScheduledNextRunCalculator $calc,
    ) {
    }

    public function handle(int $actorUserId, bool $canOperateAny, CreateScheduledTransactionData $data): array
    {
        return DB::transaction(function () use ($actorUserId, $canOperateAny, $data) {

            if (!$canOperateAny && $data->ownerUserId !== $actorUserId) {
                throw new \RuntimeException('غير مسموح إنشاء جدولة لمستخدم آخر');
            }

            $src = AccountModel::query()->where('public_id', $data->sourceAccountPublicId)->first();
            $dst = AccountModel::query()->where('public_id', $data->destinationAccountPublicId)->first();

            if (!$src || !$dst) throw new \RuntimeException('حساب مصدر/وجهة غير موجود');

            if ((string) $src->type === 'group' || (string) $dst->type === 'group') {
                throw new \RuntimeException('لا يمكن استخدام حساب group في جدولة');
            }

            if ((string) $src->state !== 'active' || (string) $dst->state !== 'active') {
                throw new \RuntimeException('الحساب يجب أن يكون active');
            }

            if (!$canOperateAny && (int) $src->user_id !== $data->ownerUserId) {
                throw new \RuntimeException('لا تملك حساب المصدر');
            }

            $now = CarbonImmutable::now('UTC');
            $startAt = $data->startAt ? CarbonImmutable::parse($data->startAt) : null;

            $nextRunAt = $this->calc->initial(
                now: $now,
                startAt: $startAt,
                frequency: $data->frequency,
                interval: $data->interval,
                dayOfWeek: $data->dayOfWeek,
                dayOfMonth: $data->dayOfMonth,
                runTime: $data->runTime,
            );

            $record = $this->repo->create([
                'owner_user_id' => $data->ownerUserId,
                'created_by_user_id' => $data->createdByUserId,
                'type' => 'transfer',
                'source_account_id' => (int) $src->id,
                'destination_account_id' => (int) $dst->id,
                'amount' => $data->amount,
                'currency' => config('banking.currency', 'USD'),
                'description' => $data->description,

                'frequency' => $data->frequency,
                'interval' => $data->interval,
                'day_of_week' => $data->dayOfWeek,
                'day_of_month' => $data->dayOfMonth,
                'run_time' => $data->runTime,

                'start_at' => $data->startAt,
                'end_at' => $data->endAt,

                'status' => 'active',
                'next_run_at' => $nextRunAt->toDateTimeString(),
            ]);

            return [
                'message' => 'تم إنشاء جدولة التحويل بنجاح',
                'scheduled_public_id' => $record->publicId,
                'next_run_at' => $nextRunAt->toIso8601String(),
            ];
        });
    }
}
