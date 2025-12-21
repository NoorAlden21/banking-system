<?php

namespace App\Banking\Transactions\Application\DTOs;

final class UpdateScheduledTransactionData
{
    public function __construct(
        public readonly ?string $amount,
        public readonly ?string $description,

        public readonly ?string $frequency,
        public readonly ?int $interval,
        public readonly ?int $dayOfWeek,
        public readonly ?int $dayOfMonth,
        public readonly ?string $runTime,

        public readonly ?string $startAt,
        public readonly ?string $endAt,

        public readonly ?string $status, // active|paused|canceled
    ) {
    }
}
