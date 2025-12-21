<?php

namespace App\Banking\Transactions\Domain\Enums;

enum ScheduledStatusEnum: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case CANCELED = 'canceled';
}
