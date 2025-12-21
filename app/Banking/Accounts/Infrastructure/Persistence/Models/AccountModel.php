<?php

namespace App\Banking\Accounts\Infrastructure\Persistence\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AccountModel extends Model
{
    use SoftDeletes;

    protected $table = 'accounts';

    protected $fillable = [
        'public_id',
        'user_id',
        'parent_id',
        'type',
        'state',
        'balance',
        'daily_limit',
        'monthly_limit',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
        'balance' => 'decimal:2',
        'daily_limit' => 'decimal:2',
        'monthly_limit' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->public_id)) {
                $model->public_id = (string) Str::uuid();
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
