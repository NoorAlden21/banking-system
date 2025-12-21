<?php

namespace App\Banking\CustomerSupport\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

final class SupportTicketModel extends Model
{
    use SoftDeletes;

    protected $table = 'support_tickets';

    protected $fillable = [
        'public_id',
        'owner_user_id',
        'created_by_user_id',
        'assigned_to_user_id',
        'subject',
        'category',
        'priority',
        'status',
        'last_message_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(SupportMessageModel::class, 'ticket_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
