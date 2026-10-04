<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkComment extends Model
{
    protected $guarded = [];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(WorkOrderTest::class, 'work_order_test_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
