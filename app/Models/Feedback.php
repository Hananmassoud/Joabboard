<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'type',
        'rating',
        'subject',
        'message',
        'page_url',
        'ip_address',
        'user_agent',
        'read_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'read_at' => 'datetime',
    ];

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }
}

