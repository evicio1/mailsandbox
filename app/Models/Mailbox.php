<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Mailbox extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeDisabled(Builder $query): Builder
    {
        return $query->where('status', 'disabled');
    }

    // ─── Lifecycle Helpers ───────────────────────────────────────────────────

    public function markAsDisabled(): void
    {
        $this->update(['status' => 'disabled']);
    }

    public function markAsDeleted(): void
    {
        $this->update(['status' => 'deleted']);
        $this->delete();
    }
}
