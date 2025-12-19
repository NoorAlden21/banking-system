<?php

namespace App\Banking\Transactions\Domain\Enums;

enum TransactionStatusEnum: string
{
    case POSTED = 'posted';
    case PENDING_APPROVAL = 'pending_approval';
    case REJECTED = 'rejected';
    case FAILED = 'failed';
}
