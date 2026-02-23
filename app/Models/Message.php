<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $guarded = [];

    protected $casts = [
        'to_raw' => 'array',
        'cc_raw' => 'array',
        'received_at' => 'datetime',
        'is_read' => 'boolean',
    ];

    public function mailbox()
    {
        return $this->belongsTo(Mailbox::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }
}
