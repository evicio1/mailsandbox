<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;

class Tenant extends Model
{
    use Billable;

    protected $guarded = [];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function mailboxes()
    {
        return $this->hasMany(Mailbox::class);
    }
}
