<?php

namespace App\Banking\Transactions\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class ScheduledTransactionModel extends Model
{
    use SoftDeletes;

    protected $table = 'scheduled_transactions';

    protected $fillable = [
        'public_id',
        'owner_user_id',
        'created_by_user_id',
        'type',
        'source_account_id',
        'destination_account_id',
        'amount',
        'currency',
        'description',
        'frequency',
        'interval',
        'day_of_week',
        'day_of_month',
        'run_time',
        'start_at',
        'end_at',
        'status',
        'next_run_at',
        'last_run_at',
        'last_transaction_public_id',
        'last_status',
        'last_error',
        'runs_count',
        'locked_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'locked_at' => 'datetime',
        'runs_count' => 'integer',
        'interval' => 'integer',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (!$m->public_id) {
                $m->public_id = (string) Str::uuid();
            }
        });
    }
}
