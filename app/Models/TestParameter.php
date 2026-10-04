<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestParameter extends Model
{
    protected $guarded = [];

    public function testType(): BelongsTo
    {
        return $this->belongsTo(TestType::class);
    }

    public function resultValues(): HasMany
    {
        return $this->hasMany(TestResultParameter::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
