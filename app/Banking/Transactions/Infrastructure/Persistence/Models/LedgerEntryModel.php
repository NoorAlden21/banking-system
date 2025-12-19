<?php

namespace App\Banking\Transactions\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class LedgerEntryModel extends Model
{
    protected $table = 'ledger_entries';

    protected $fillable = [
        'public_id',
        'transaction_id',
        'account_id',
        'direction',
        'amount',
        'currency',
        'balance_before',
        'balance_after',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->public_id)) {
                $m->public_id = (string) Str::uuid();
            }
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TransactionModel::class, 'transaction_id');
    }
}
