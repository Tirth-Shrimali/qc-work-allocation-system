<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkAllocation extends Model
{
    protected $guarded = [];

    public function test(): BelongsTo
    {
        return $this->belongsTo(WorkOrderTest::class, 'work_order_test_id');
    }

    public function analyst(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'analyst_id');
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by_id');
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }
}
