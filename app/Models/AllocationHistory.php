<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllocationHistory extends Model
{
    protected $guarded = [];

    public function test(): BelongsTo
    {
        return $this->belongsTo(WorkOrderTest::class, 'work_order_test_id');
    }

    public function previousAnalyst(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'previous_analyst_id');
    }

    public function newAnalyst(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'new_analyst_id');
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by_id');
    }
}
