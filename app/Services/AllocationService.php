<?php

namespace App\Services;

use App\Models\AllocationHistory;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkAllocation;
use App\Models\WorkOrderTest;
use App\Support\WorkStatus;
use Illuminate\Support\Facades\DB;

class AllocationService
{
    public function __construct(private StatusService $status)
    {
    }

    /**
     * Allocate a test to an analyst (or reallocate if already allocated).
     */
    public function allocate(WorkOrderTest $test, Employee $analyst, User $actor, ?int $instrumentId = null, ?string $reason = null): WorkOrderTest
    {
        return DB::transaction(function () use ($test, $analyst, $actor, $instrumentId, $reason) {
            $previousId = $test->assigned_analyst_id;

            if ($previousId === $analyst->id && $test->assigned_instrument_id === $instrumentId) {
                return $test; // nothing to change
            }

            // Deactivate current allocation
            WorkAllocation::where('work_order_test_id', $test->id)
                ->where('status', 'ACTIVE')
                ->update(['status' => 'SUPERSEDED']);

            WorkAllocation::create([
                'work_order_test_id' => $test->id,
                'analyst_id' => $analyst->id,
                'allocated_by_id' => $actor->id,
                'instrument_id' => $instrumentId,
                'status' => 'ACTIVE',
                'remarks' => $reason,
            ]);

            AllocationHistory::create([
                'work_order_test_id' => $test->id,
                'previous_analyst_id' => $previousId,
                'new_analyst_id' => $analyst->id,
                'allocated_by_id' => $actor->id,
                'reason' => $reason,
                'status' => $test->status,
            ]);

            $test->assigned_analyst_id = $analyst->id;
            $test->assigned_instrument_id = $instrumentId;
            $test->save();

            $isFirstAllocation = $previousId === null
                && in_array($test->status, [WorkStatus::NEW, WorkStatus::ALLOCATED], true);

            if ($isFirstAllocation && $test->status === WorkStatus::NEW) {
                $this->status->transitionTest($test, WorkStatus::ALLOCATED, $reason);
            }

            AuditLogger::log('Allocations', $previousId ? 'reallocate' : 'allocate', $test->id, [
                'previous_analyst_id' => $previousId,
            ], [
                'analyst_id' => $analyst->id,
                'instrument_id' => $instrumentId,
                'reason' => $reason,
            ]);

            $link = route('work-orders.show', $test->work_order_id);

            if ($previousId && $previousId !== $analyst->id) {
                $oldEmployee = Employee::find($previousId);
                $oldUser = $oldEmployee?->user;
                if ($oldUser) {
                    NotificationService::send($oldUser->id, 'work.reassigned', 'Work Reassigned',
                        'Test #'.$test->id.' ('.$test->testType?->name.') has been reassigned away from you.', $link);
                }
            }

            NotificationService::send(
                $analyst->user?->id ?? [],
                'work.allocated',
                'New Test Allocated',
                'You have been assigned test "'.$test->testType?->name.'" for work order '.$test->workOrder?->work_no.'.',
                $link
            );

            $this->status->syncWorkOrder($test->work_order_id);

            return $test->fresh(['assignedAnalyst', 'testType']);
        });
    }

    /**
     * Candidate analysts for a test: skill match, workload, availability.
     *
     * @return array<int, array<string, mixed>>
     */
    public function candidates(WorkOrderTest $test): array
    {
        $requiredSkillId = $test->testType?->required_skill_id;

        $openStatuses = WorkStatus::EXECUTION;

        $workloads = DB::table('work_order_tests')
            ->select('assigned_analyst_id', DB::raw('COUNT(*) as open_count'))
            ->whereIn('status', $openStatuses)
            ->whereNotNull('assigned_analyst_id')
            ->groupBy('assigned_analyst_id')
            ->pluck('open_count', 'assigned_analyst_id');

        $candidates = Employee::query()
            ->active()
            ->whereHas('user', fn ($q) => $q->where('status', 'active')
                ->whereHas('roles', fn ($r) => $r->where('code', 'analyst')))
            ->with(['designation', 'skills' => fn ($q) => $q->wherePivot('authorization_status', 'active')])
            ->withCount(['assignedTests as open_work' => fn ($q) => $q->whereIn('status', $openStatuses)])
            ->get()
            ->values();

        $rows = $candidates->map(function (Employee $e) use ($requiredSkillId) {
            $skillMatch = false;
            $skillLevel = null;

            if ($requiredSkillId) {
                $pivot = $e->skills->firstWhere('id', $requiredSkillId);
                $skillMatch = (bool) $pivot;
                $skillLevel = $pivot?->pivot?->skill_level;
            }

            $load = (int) $e->open_work;

            return [
                'employee' => $e,
                'skill_match' => $skillMatch,
                'skill_level' => $skillLevel,
                'workload' => $load,
                'available' => $load < 5,
            ];
        });

        // Skill match first, then lightest workload.
        return $rows
            ->sortBy([
                ['skill_match', 'desc'],
                ['workload', 'asc'],
            ])
            ->values()
            ->all();
    }
}
