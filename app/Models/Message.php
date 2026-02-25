<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\MessageFactory;

class Message extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return MessageFactory::new();
    }
    
    protected $guarded = [];

    protected $casts = [
        'to_raw' => 'array',
        'cc_raw' => 'array',
        'bcc_raw' => 'array',
        'tls_info' => 'array',
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

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }
}
