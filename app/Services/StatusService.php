<?php

namespace App\Services;

use App\Models\WorkOrder;
use App\Models\WorkOrderTest;
use App\Support\WorkStatus;
use RuntimeException;

class StatusService
{
    /**
     * Move a single work-order test to a new status, validating the transition.
     */
    public function transitionTest(WorkOrderTest $test, string $to, string $reason = null): WorkOrderTest
    {
        $from = $test->status;

        if ($from === $to) {
            return $test;
        }

        if (! WorkStatus::canTransition($from, $to)) {
            throw new RuntimeException(sprintf(
                'Invalid status transition from %s to %s.',
                $from,
                $to
            ));
        }

        $test->status = $to;

        if ($to === WorkStatus::SUBMITTED) {
            $test->review_status = 'PENDING';
        } elseif ($to === WorkStatus::UNDER_REVIEW) {
            $test->review_status = 'IN_REVIEW';
        } elseif ($to === WorkStatus::APPROVED) {
            $test->review_status = 'APPROVED';
        } elseif ($to === WorkStatus::REWORK) {
            $test->review_status = 'REWORK';
        } elseif ($to === WorkStatus::REJECTED) {
            $test->review_status = 'REJECTED';
        }

        $test->save();

        AuditLogger::log('Work Tests', 'status_change', $test->id, ['status' => $from], ['status' => $to, 'reason' => $reason]);

        $this->syncWorkOrder($test->work_order_id);

        return $test;
    }

    /**
     * Recompute the parent work order status from its tests.
     */
    public function syncWorkOrder(int $workOrderId): WorkOrder
    {
        $order = WorkOrder::with('tests')->findOrFail($workOrderId);

        $statuses = $order->tests->pluck('status');

        if ($statuses->isEmpty()) {
            $order->status = WorkStatus::NEW;
        } elseif ($statuses->every(fn ($s) => $s === WorkStatus::CANCELLED)) {
            $order->status = WorkStatus::CANCELLED;
        } elseif ($statuses->every(fn ($s) => in_array($s, [WorkStatus::COMPLETED, WorkStatus::CANCELLED], true))) {
            $order->status = WorkStatus::COMPLETED;
        } elseif ($statuses->contains(fn ($s) => $s === WorkStatus::UNDER_REVIEW)) {
            $order->status = WorkStatus::UNDER_REVIEW;
        } elseif ($statuses->contains(fn ($s) => $s === WorkStatus::REWORK)) {
            $order->status = WorkStatus::REWORK;
        } elseif ($statuses->contains(fn ($s) => $s === WorkStatus::IN_PROGRESS || $s === WorkStatus::ON_HOLD)) {
            $order->status = WorkStatus::IN_PROGRESS;
        } elseif ($statuses->contains(fn ($s) => $s === WorkStatus::SUBMITTED)) {
            $order->status = WorkStatus::SUBMITTED;
        } elseif ($statuses->contains(fn ($s) => $s === WorkStatus::APPROVED)) {
            $order->status = WorkStatus::APPROVED;
        } elseif ($statuses->contains(fn ($s) => $s === WorkStatus::ALLOCATED)) {
            $order->status = WorkStatus::ALLOCATED;
        } elseif ($statuses->contains(fn ($s) => $s === WorkStatus::ACCEPTED)) {
            $order->status = WorkStatus::ACCEPTED;
        } else {
            $order->status = WorkStatus::NEW;
        }

        $order->save();

        return $order;
    }
}
