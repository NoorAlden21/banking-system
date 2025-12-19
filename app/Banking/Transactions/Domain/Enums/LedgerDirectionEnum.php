<?php

namespace App\Banking\Transactions\Domain\Enums;

enum LedgerDirectionEnum: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';
}
