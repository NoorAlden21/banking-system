<?php

namespace App\Banking\Shared\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class AuditLogModel extends Model
{
    public $timestamps = false;

    protected $table = 'audit_logs';

    protected $fillable = [
        'public_id',
        'actor_user_id',
        'actor_role',
        'action',
        'subject_type',
        'subject_public_id',
        'ip',
        'user_agent',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];
}
