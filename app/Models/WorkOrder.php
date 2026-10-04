<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkOrder extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'required_date' => 'date',
            'received_date' => 'date',
            'sampling_date' => 'date',
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function workCategory(): BelongsTo
    {
        return $this->belongsTo(WorkCategory::class);
    }

    public function sampleType(): BelongsTo
    {
        return $this->belongsTo(SampleType::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function priorityRow(): BelongsTo
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    public function tests(): HasMany
    {
        return $this->hasMany(WorkOrderTest::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(WorkComment::class)->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(WorkAttachment::class)->latest();
    }

    public function isOverdue(): bool
    {
        return ! in_array($this->status, ['COMPLETED', 'CANCELLED'])
            && $this->required_date
            && $this->required_date->isPast();
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->whereDate('required_date', '<', now()->toDateString());
    }
}
