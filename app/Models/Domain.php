<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_platform_provided' => 'boolean',
            'catch_all_enabled' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function mailboxes()
    {
        return $this->hasMany(Mailbox::class); // if we associate explicit mailboxes directly, though it's optional
    }
}
