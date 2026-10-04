<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseHistory extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'previous_activation' => 'date',
            'new_activation' => 'date',
            'previous_expiry' => 'date',
            'new_expiry' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
