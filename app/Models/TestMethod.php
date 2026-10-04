<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestMethod extends Model
{
    protected $guarded = [];

    public function testType(): BelongsTo
    {
        return $this->belongsTo(TestType::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
