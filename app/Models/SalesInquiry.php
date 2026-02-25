<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInquiry extends Model
{
    protected $guarded = [];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
