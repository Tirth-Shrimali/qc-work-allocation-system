<?php

namespace App\Services;

use App\Models\ReviewHistory;
use App\Models\User;
use App\Models\WorkOrderTest;
use App\Support\WorkStatus;

class ReviewService
{
    public function __construct(private StatusService $status)
    {
    }

    public function approve(WorkOrderTest $test, User $reviewer, ?string $remarks = null): WorkOrderTest
    {
        $from = $test->status;

        $result = $test->latestResult;
        if ($result) {
            $result->update([
                'reviewed_by_id' => $reviewer->id,
                'reviewed_at' => now(),
                'review_remarks' => $remarks,
            ]);
        }

        $this->status->transitionTest($test, WorkStatus::APPROVED, $remarks);

        ReviewHistory::create([
            'work_order_test_id' => $test->id,
            'reviewer_id' => $reviewer->id,
            'action' => 'APPROVE',
            'previous_status' => $from,
            'new_status' => WorkStatus::APPROVED,
            'comments' => $remarks,
        ]);

        $this->notifyAnalyst($test, 'work.approved', 'Result Approved',
            'Your result for "'.$test->testType?->name.'" was approved.', $remarks);

        return $test;
    }

    public function requestRework(WorkOrderTest $test, User $reviewer, string $reason): WorkOrderTest
    {
        $from = $test->status;

        if (! trim($reason)) {
            throw new \RuntimeException('A reason is required to request rework.');
        }

        $result = $test->latestResult;
        if ($result) {
            $result->update([
                'reviewed_by_id' => $reviewer->id,
                'reviewed_at' => now(),
                'rework_reason' => $reason,
            ]);
        }

        $this->status->transitionTest($test, WorkStatus::REWORK, $reason);

        ReviewHistory::create([
            'work_order_test_id' => $test->id,
            'reviewer_id' => $reviewer->id,
            'action' => 'REWORK',
            'previous_status' => $from,
            'new_status' => WorkStatus::REWORK,
            'reason' => $reason,
        ]);

        $this->notifyAnalyst($test, 'work.rework', 'Rework Requested',
            'Rework requested for "'.$test->testType?->name.'". Reason: '.$reason, $reason);

        return $test;
    }

    public function reject(WorkOrderTest $test, User $reviewer, string $reason): WorkOrderTest
    {
        $from = $test->status;

        if (! trim($reason)) {
            throw new \RuntimeException('A reason is required to reject a result.');
        }

        $result = $test->latestResult;
        if ($result) {
            $result->update([
                'reviewed_by_id' => $reviewer->id,
                'reviewed_at' => now(),
                'review_remarks' => $reason,
            ]);
        }

        $this->status->transitionTest($test, WorkStatus::REJECTED, $reason);

        ReviewHistory::create([
            'work_order_test_id' => $test->id,
            'reviewer_id' => $reviewer->id,
            'action' => 'REJECT',
            'previous_status' => $from,
            'new_status' => WorkStatus::REJECTED,
            'reason' => $reason,
        ]);

        $this->notifyAnalyst($test, 'work.rejected', 'Result Rejected',
            'Result for "'.$test->testType?->name.'" was rejected. Reason: '.$reason, $reason);

        return $test;
    }

    private function notifyAnalyst(WorkOrderTest $test, string $type, string $title, string $message, ?string $link = null): void
    {
        $userId = $test->assignedAnalyst?->user?->id;

        if ($userId) {
            NotificationService::send($userId, $type, $title, $message, route('my-work.show', $test));
        }
    }
}
