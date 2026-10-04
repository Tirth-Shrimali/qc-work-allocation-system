<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['joining_date' => 'date'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'employee_skills')
            ->withPivot(['skill_level', 'authorization_status', 'remarks']);
    }

    public function assignedTests(): HasMany
    {
        return $this->hasMany(WorkOrderTest::class, 'assigned_analyst_id');
    }

    public function testResults(): HasMany
    {
        return $this->hasMany(TestResult::class, 'analyst_id');
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAnalysts($query)
    {
        return $query->whereHas('designation', function ($q) {
            $q->where(function ($qq) {
                $qq->where('name', 'like', '%Analyst%')
                    ->orWhere('name', 'like', '%QC%')
                    ->orWhere('name', 'like', '%Executive%');
            });
        });
    }
}
