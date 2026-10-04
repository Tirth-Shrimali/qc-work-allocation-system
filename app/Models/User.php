<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'access_start_date' => 'date',
            'access_end_date' => 'date',
            'password' => 'hashed',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    public function notificationsUnread(): HasMany
    {
        return $this->hasMany(AppNotification::class)->where('is_read', false)->latest();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Cached permission codes for this user.
     */
    public function permissionCodes(): array
    {
        return $this->roles()->with('permissions')->get()
            ->flatMap->permissions
            ->pluck('code')
            ->unique()
            ->values()
            ->all();
    }

    public function hasPermission(string $code): bool
    {
        if ($this->roles()->where('code', 'super_admin')->exists()) {
            return true;
        }

        return $this->roles()->with('permissions')->get()
            ->flatMap->permissions
            ->pluck('code')
            ->contains($code);
    }

    public function hasRole(string $code): bool
    {
        return $this->roles()->where('code', $code)->exists();
    }

    public function hasAnyRole(array $codes): bool
    {
        return $this->roles()->whereIn('code', $codes)->exists();
    }

    public function primaryRole(): ?Role
    {
        return $this->roles()->first();
    }

    public function isAdminish(): bool
    {
        return $this->hasAnyRole(['super_admin', 'qc_admin']);
    }
}
