<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketReply extends Model
{
    protected $table = 'support_ticket_replies';

    protected $fillable = [
        'thread_id',
        'user_id',
        'is_admin_reply',
        'message',
        'reply_status',
    ];

    protected $casts = [
        'thread_id' => 'integer',
        'user_id' => 'integer',
        'is_admin_reply' => 'boolean',
        'reply_status' => 'integer',
    ];

    public function appUser()
    {
        return $this->belongsTo(AppUser::class, 'user_id', 'id');
    }

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'thread_id', 'id');
    }
}
