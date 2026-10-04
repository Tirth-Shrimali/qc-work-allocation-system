<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestResultParameter extends Model
{
    protected $guarded = [];

    public function result(): BelongsTo
    {
        return $this->belongsTo(TestResult::class, 'test_result_id');
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(TestParameter::class);
    }
}
