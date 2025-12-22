<?php

namespace App\Banking\Accounts\Application\DTOs;

final class PreviewInterestData
{
    public function __construct(
        public readonly int $days,
        public readonly ?string $market,
    ) {
    }
}
