<?php

namespace App\Banking\Accounts\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class AccountFeatureModel extends Model
{
    protected $table = 'account_features';

    protected $fillable = [
        'account_id',
        'feature_key',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
