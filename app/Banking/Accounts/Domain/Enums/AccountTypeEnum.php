<?php

namespace App\Banking\Accounts\Domain\Enums;

enum AccountTypeEnum: string
{
    case GROUP = 'group';
    case SAVINGS = 'savings';
    case CHECKING = 'checking';
    case LOAN = 'loan';
    case INVESTMENT = 'investment';
}
