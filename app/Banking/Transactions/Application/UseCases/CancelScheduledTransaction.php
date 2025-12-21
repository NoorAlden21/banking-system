<?php

namespace App\Banking\Transactions\Application\UseCases;

use Illuminate\Support\Facades\DB;

use App\Banking\Transactions\Domain\Contracts\ScheduledTransactionRepository;
use App\Banking\Transactions\Domain\Contracts\ScheduledTransactionReadRepository;

final class CancelScheduledTransaction
{
    public function __construct(
        private readonly ScheduledTransactionRepository $repo,
        private readonly ScheduledTransactionReadRepository $read,
    ) {
    }

    public function handle(int $actorUserId, bool $canOperateAny, string $publicId): array
    {
        return DB::transaction(function () use ($actorUserId, $canOperateAny, $publicId) {

            $detail = $this->read->findDetail(
                actorUserId: $actorUserId,
                canViewAll: $canOperateAny,
                scope: $canOperateAny ? 'all' : 'mine',
                publicId: $publicId
            );

            if (!$detail) throw new \RuntimeException('غير موجود');

            $this->repo->cancelByPublicId($publicId);

            return [
                'message' => 'تم إلغاء الجدولة',
                'scheduled_public_id' => $publicId,
            ];
        });
    }
}
