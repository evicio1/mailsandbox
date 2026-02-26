<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalMailbox extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'last_sync_at' => 'datetime',
            'port' => 'integer',
            'last_seen_uid' => 'integer',
            'error_count' => 'integer',
            'sync_lock_until' => 'datetime',
            'is_sync_enabled' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function syncLogs()
    {
        return $this->hasMany(ExternalMailboxSyncLog::class);
    }
}
