<?php

namespace App\Banking\Transactions\Domain\Entities;

final class ScheduledTransactionForRun
{
    public function __construct(
        public readonly int $id,
        public readonly string $publicId,
        public readonly int $ownerUserId,

        public readonly string $type,
        public readonly string $currency,
        public readonly string $amount,
        public readonly ?string $description,

        public readonly string $frequency,
        public readonly int $interval,
        public readonly ?int $dayOfWeek,
        public readonly ?int $dayOfMonth,
        public readonly string $runTime, // HH:MM:SS

        public readonly string $nextRunAt, // timestamp string
        public readonly ?string $endAt,

        public readonly string $sourceAccountPublicId,
        public readonly string $destinationAccountPublicId,
    ) {
    }
}
