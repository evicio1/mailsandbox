<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use Billable;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    // ─── Boot ────────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            if (empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name) . '-' . Str::lower(Str::random(6));
            }
        });
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function mailboxes()
    {
        return $this->hasMany(Mailbox::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function messages()
    {
        return $this->hasManyThrough(Message::class, Mailbox::class);
    }

    // ─── Status Helpers ────────────────────────────────────────────────────

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    // ─── Metrics ─────────────────────────────────────────────────────────────

    public function totalMessages(): int
    {
        return $this->messages()->count();
    }

    public function totalStorageBytes(): int
    {
        return $this->mailboxes()
            ->withSum('messages', 'raw_size')
            ->get()
            ->sum('messages_sum_raw_size') ?? 0;
    }
}
