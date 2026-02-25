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
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancel_at_period_end' => 'boolean',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'current_plan_id', 'plan_id');
    }

    public function getInboxLimitAttribute(): int
    {
        if ($this->inbox_limit_override !== null) {
            return $this->inbox_limit_override;
        }
        return $this->plan ? $this->plan->inbox_limit : 1;
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

    public function domains()
    {
        return $this->hasMany(Domain::class);
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

    public function enforceInboxQuota(): void
    {
        $limit = $this->inbox_limit;
        $activeCount = $this->mailboxes()->active()->count();

        if ($activeCount > $limit) {
            $excess = $activeCount - $limit;
            
            // Disable the oldest active mailboxes first
            $mailboxesToDisable = $this->mailboxes()
                ->active()
                ->orderBy('created_at', 'asc')
                ->limit($excess)
                ->get();

            foreach ($mailboxesToDisable as $mailbox) {
                $mailbox->markAsDisabled();
            }

            \Illuminate\Support\Facades\Log::warning("Tenant {$this->id} exceeded inbox limit of {$limit}. Auto-disabled {$excess} mailboxes.");
        }
    }

    // ─── Metrics ─────────────────────────────────────────────────────────────

    public function totalMessages(): int
    {
        return $this->messages()->count();
    }

    public function totalStorageBytes(): int
    {
        return (int) ($this->mailboxes()
            ->withSum('messages', 'size_bytes')
            ->get()
            ->sum('messages_sum_size_bytes') ?? 0);
    }
}
