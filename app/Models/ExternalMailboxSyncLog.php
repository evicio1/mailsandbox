<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalMailboxSyncLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'emails_found' => 'integer',
            'emails_imported' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function externalMailbox()
    {
        return $this->belongsTo(ExternalMailbox::class);
    }
}
