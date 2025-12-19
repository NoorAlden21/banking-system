<?php

namespace App\Banking\Transactions\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class IdempotencyKeyModel extends Model
{
    protected $table = 'idempotency_keys';

    protected $fillable = [
        'initiator_user_id',
        'action',
        'idempotency_key',
        'request_hash',
        'response_code',
        'response_body',
        'transaction_public_id',
        'locked_at',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
    ];
}
