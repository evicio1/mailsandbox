<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\NoteFactory;

class Note extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return NoteFactory::new();
    }
    
    protected $guarded = [];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
