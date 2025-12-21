<?php

namespace App\Banking\Transactions\Domain\Enums;

enum ScheduledFrequencyEnum: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
}
