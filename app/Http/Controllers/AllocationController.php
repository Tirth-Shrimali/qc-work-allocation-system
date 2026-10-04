<?php

namespace App\Http\Controllers;

use App\Models\AllocationHistory;
use App\Models\WorkOrderTest;
use App\Services\AllocationService;
use App\Support\WorkStatus;
use Illuminate\Http\Request;

class AllocationController extends Controller
{
    public function __construct(private AllocationService $service)
    {
    }

    public function index(Request $request)
    {
        $query = WorkOrderTest::query()
            ->with(['workOrder:id,work_no,sample_id,required_date,priority,status', 'testType:id,name,required_skill_id', 'testMethod:id,name', 'assignedAnalyst'])
            ->latest('id');

        $view = $request->input('view', 'pending');

        if ($view === 'pending') {
            $query->where('status', WorkStatus::NEW)->whereNull('assigned_analyst_id');
        } elseif ($view === 'allocated') {
            $query->whereIn('status', WorkStatus::EXECUTION)->whereNotNull('assigned_analyst_id');
        } elseif ($view === 'all') {
            // no extra constraint
        }

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('workOrder', fn ($w) => $w->where('work_no', 'like', "%{$search}%")
                    ->orWhere('sample_id', 'like', "%{$search}%"))
                    ->orWhereHas('testType', fn ($t) => $t->where('name', 'like', "%{$search}%"));
            });
        }

        $tests = $query->paginate(15)->withQueryString();

        return view('allocation.index', [
            'tests' => $tests,
            'view' => $view,
            'counts' => [
                'pending' => WorkOrderTest::where('status', WorkStatus::NEW)->whereNull('assigned_analyst_id')->count(),
                'allocated' => WorkOrderTest::whereIn('status', WorkStatus::EXECUTION)->whereNotNull('assigned_analyst_id')->count(),
            ],
        ]);
    }

    /**
     * AJAX: smart allocation candidates for a test.
     */
    public function candidates(WorkOrderTest $test)
    {
        $test->load('testType');

        return response()->json([
            'test' => [
                'id' => $test->id,
                'name' => $test->testType?->name,
                'required_skill_id' => $test->testType?->required_skill_id,
                'current_analyst_id' => $test->assigned_analyst_id,
            ],
            'candidates' => collect($this->service->candidates($test))->map(fn (array $row) => [
                'id' => $row['employee']->id,
                'name' => $row['employee']->fullName(),
                'code' => $row['employee']->employee_code,
                'designation' => $row['employee']->designation?->name,
                'skill_match' => $row['skill_match'],
                'skill_level' => $row['skill_level'],
                'workload' => $row['workload'],
                'available' => $row['available'],
            ]),
        ]);
    }

    public function allocate(Request $request, WorkOrderTest $test)
    {
        $data = $request->validate([
            'analyst_id' => ['required', 'exists:employees,id'],
            'instrument_id' => ['nullable', 'exists:instruments,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($test->assigned_analyst_id && ! $request->user()->hasPermission('allocations.reallocate')) {
            return back()->withErrors(['analyst_id' => 'You do not have permission to reallocate work.']);
        }

        $inExecution = in_array($test->status, [WorkStatus::ACCEPTED, WorkStatus::IN_PROGRESS, WorkStatus::SUBMITTED, WorkStatus::UNDER_REVIEW], true);

        if ($inExecution) {
            return back()->withErrors(['analyst_id' => 'This test is already in execution and cannot be reallocated now.']);
        }

        $analyst = \App\Models\Employee::findOrFail($data['analyst_id']);

        $this->service->allocate($test, $analyst, $request->user(), $data['instrument_id'] ?? null, $data['reason'] ?? null);

        return back()->with('success', "Test allocated to {$analyst->fullName()}.");
    }

    public function history()
    {
        $history = AllocationHistory::with(['test.workOrder', 'previousAnalyst', 'newAnalyst', 'allocatedBy'])
            ->latest('id')
            ->paginate(20);

        return view('allocation.history', compact('history'));
    }
}
