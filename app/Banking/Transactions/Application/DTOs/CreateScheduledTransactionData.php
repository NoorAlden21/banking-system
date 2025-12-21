<?php

namespace App\Banking\Transactions\Application\DTOs;

final class CreateScheduledTransactionData
{
    public function __construct(
        public readonly int $ownerUserId,
        public readonly int $createdByUserId,

        public readonly string $sourceAccountPublicId,
        public readonly string $destinationAccountPublicId,
        public readonly string $amount,
        public readonly ?string $description,

        public readonly string $frequency, // daily|weekly|monthly
        public readonly int $interval,
        public readonly ?int $dayOfWeek,
        public readonly ?int $dayOfMonth,
        public readonly string $runTime,

        public readonly ?string $startAt,
        public readonly ?string $endAt,
    ) {
    }
}
