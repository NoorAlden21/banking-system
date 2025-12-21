<?php

namespace App\Banking\CustomerSupport\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

final class SupportMessageModel extends Model
{
    protected $table = 'support_messages';

    protected $fillable = [
        'ticket_id',
        'sender_user_id',
        'body',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'bool',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicketModel::class, 'ticket_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
