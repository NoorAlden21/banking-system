<?php

namespace App\Banking\Transactions\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class TransactionModel extends Model
{
    use SoftDeletes;

    protected $table = 'transactions';

    protected $fillable = [
        'public_id',
        'initiator_user_id',
        'type',
        'status',
        'source_account_id',
        'destination_account_id',
        'amount',
        'currency',
        'description',
        'meta',
        'posted_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'posted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->public_id)) {
                $m->public_id = (string) Str::uuid();
            }
        });
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntryModel::class, 'transaction_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'initiator_user_id');
    }
}
