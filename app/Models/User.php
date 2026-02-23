<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at'           => 'datetime',
            'locked_until'            => 'datetime',
            'password'                => 'hashed',
            'is_super_admin'          => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // ─── MFA Helpers ─────────────────────────────────────────────────────────

    public function hasMfaEnabled(): bool
    {
        return ! is_null($this->two_factor_secret)
            && ! is_null($this->two_factor_confirmed_at);
    }

    public function hasMfaPending(): bool
    {
        return ! is_null($this->two_factor_secret)
            && is_null($this->two_factor_confirmed_at);
    }

    public function getRecoveryCodes(): array
    {
        if (! $this->two_factor_recovery_codes) {
            return [];
        }
        return json_decode(decrypt($this->two_factor_recovery_codes), true) ?? [];
    }

    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::upper(Str::random(5)) . '-' . Str::upper(Str::random(5));
        }
        return $codes;
    }

    // ─── Role Helpers ─────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isTenantAdmin(?int $tenantId = null): bool
    {
        $tid = $tenantId ?? $this->tenant_id;
        setPermissionsTeamId($tid);
        return $this->hasRole('TenantAdmin');
    }

    public function isDeveloper(?int $tenantId = null): bool
    {
        $tid = $tenantId ?? $this->tenant_id;
        setPermissionsTeamId($tid);
        return $this->hasRole('Developer');
    }

    // ─── Security Helpers ─────────────────────────────────────────────────────

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function incrementFailedLogin(): void
    {
        $this->increment('failed_login_count');
        if ($this->failed_login_count >= 5) {
            $this->update(['locked_until' => now()->addMinutes(15)]);
        }
    }

    public function clearFailedLogin(): void
    {
        $this->update([
            'failed_login_count' => 0,
            'locked_until'       => null,
            'last_login_at'      => now(),
        ]);
    }

    // ─── Activity Log ────────────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'email', 'tenant_id', 'is_super_admin']);
    }
}
