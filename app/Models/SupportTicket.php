<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $table = 'support_tickets';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'thread_id',
        'thread_status',
        'module',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'thread_status' => 'integer',
        'module' => 'integer',
    ];

    public function appUser()
    {
        return $this->belongsTo(AppUser::class, 'user_id', 'id');
    }

    public function replies()
    {
        return $this->hasMany(SupportTicketReply::class, 'thread_id', 'id');
    }
}

