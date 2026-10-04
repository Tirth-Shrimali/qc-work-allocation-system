<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'logout_at' => 'datetime',
            'is_active' => 'boolean',
            'timeout_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loginLog(): BelongsTo
    {
        return $this->belongsTo(LoginLog::class);
    }

    /** Session rows that currently hold an active-user slot. */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
