<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestType extends Model
{
    protected $guarded = [];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function requiredSkill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'required_skill_id');
    }

    public function methods(): HasMany
    {
        return $this->hasMany(TestMethod::class);
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(TestParameter::class)->orderBy('sequence');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
