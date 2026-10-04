<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkOrderTest extends Model
{
    protected $guarded = [];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function testType(): BelongsTo
    {
        return $this->belongsTo(TestType::class);
    }

    public function testMethod(): BelongsTo
    {
        return $this->belongsTo(TestMethod::class);
    }

    public function assignedAnalyst(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_analyst_id');
    }

    public function assignedInstrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class, 'assigned_instrument_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(WorkAllocation::class);
    }

    public function activeAllocation(): HasOne
    {
        return $this->hasOne(WorkAllocation::class)->where('status', 'ACTIVE')->latest();
    }

    public function allocationHistory(): HasMany
    {
        return $this->hasMany(AllocationHistory::class)->latest();
    }

    public function results(): HasMany
    {
        return $this->hasMany(TestResult::class)->latest();
    }

    public function latestResult(): HasOne
    {
        return $this->hasOne(TestResult::class)->latest();
    }

    public function reviewHistory(): HasMany
    {
        return $this->hasMany(ReviewHistory::class)->latest();
    }
}
