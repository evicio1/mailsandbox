<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $primaryKey = 'plan_id';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $guarded = [];

    public function isPremium(): bool
    {
        return $this->plan_id === 'premium';
    }
}
