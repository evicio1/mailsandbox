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
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
