<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\TagFactory;

class Tag extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return TagFactory::new();
    }
    
    protected $guarded = [];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function messages()
    {
        return $this->belongsToMany(Message::class);
    }
}
