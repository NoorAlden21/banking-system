<?php

namespace App\Banking\Transactions\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class TransactionApprovalModel extends Model
{
    protected $table = 'transaction_approvals';

    protected $fillable = [
        'transaction_id',
        'status',
        'requested_by_user_id',
        'decided_by_user_id',
        'reason',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];
}
