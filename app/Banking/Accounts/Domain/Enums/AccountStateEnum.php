<?php

namespace App\Banking\Accounts\Domain\Enums;

enum AccountStateEnum: string
{
    case ACTIVE = 'active';
    case FROZEN = 'frozen';
    case SUSPENDED = 'suspended';
    case CLOSED = 'closed';
}
