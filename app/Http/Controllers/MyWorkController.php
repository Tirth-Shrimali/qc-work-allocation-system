<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use App\Models\TestResult;
use App\Models\WorkOrderTest;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\StatusService;
use App\Support\WorkStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MyWorkController extends Controller
{
    public function __construct(private StatusService $status)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $employeeId = $user->employee_id;

        abort_if(! $employeeId, 403, 'No employee record is linked to your account.');

        $query = WorkOrderTest::where('assigned_analyst_id', $employeeId)
            ->with(['workOrder:id,work_no,sample_id,required_date,priority,status', 'testType:id,name', 'testMethod:id,name'])
            ->latest('id');

        if ($view = $request->input('view')) {
            if ($view === 'todo') {
                $query->whereIn('status', [WorkStatus::ALLOCATED, WorkStatus::REWORK]);
            } elseif ($view === 'active') {
                $query->whereIn('status', [WorkStatus::ACCEPTED, WorkStatus::IN_PROGRESS, WorkStatus::ON_HOLD]);
            } elseif ($view === 'review') {
                $query->whereIn('status', [WorkStatus::SUBMITTED, WorkStatus::UNDER_REVIEW]);
            } elseif ($view === 'done') {
                $query->whereIn('status', [WorkStatus::APPROVED, WorkStatus::COMPLETED]);
            } elseif ($view === 'overdue') {
                $query->whereIn('status', WorkStatus::OPEN)
                    ->whereHas('workOrder', fn ($q) => $q->overdue());
            }
        }

        if ($search = $request->input('q')) {
            $query->whereHas('workOrder', fn ($q) => $q->where('work_no', 'like', "%{$search}%")
                ->orWhere('sample_id', 'like', "%{$search}%"));
        }

        $tests = $query->paginate(12)->withQueryString();

        $counts = [
            'todo' => WorkOrderTest::where('assigned_analyst_id', $employeeId)->whereIn('status', [WorkStatus::ALLOCATED, WorkStatus::REWORK])->count(),
            'active' => WorkOrderTest::where('assigned_analyst_id', $employeeId)->whereIn('status', [WorkStatus::ACCEPTED, WorkStatus::IN_PROGRESS, WorkStatus::ON_HOLD])->count(),
            'review' => WorkOrderTest::where('assigned_analyst_id', $employeeId)->whereIn('status', [WorkStatus::SUBMITTED, WorkStatus::UNDER_REVIEW])->count(),
            'done' => WorkOrderTest::where('assigned_analyst_id', $employeeId)->whereIn('status', [WorkStatus::APPROVED, WorkStatus::COMPLETED])->count(),
        ];

        return view('my-work.index', compact('tests', 'counts'));
    }

    public function show(Request $request, WorkOrderTest $workOrderTest)
    {
        $this->authorizeTest($request, $workOrderTest);

        $workOrderTest->load([
            'workOrder.department', 'workOrder.product', 'workOrder.material', 'workOrder.sampleType',
            'testType.parameters', 'testMethod', 'assignedInstrument',
            'results.parameters', 'latestResult', 'reviewHistory.reviewer', 'allocationHistory.newAnalyst',
        ]);

        return view('my-work.show', [
            'test' => $workOrderTest,
            'result' => $workOrderTest->latestResult,
            'instruments' => Instrument::active()->orderBy('name')->get(),
            'parameters' => $workOrderTest->testType->parameters->where('status', 'active'),
        ]);
    }

    /**
     * Workflow actions: accept, start, hold, resume, submit, complete.
     */
    public function action(Request $request, WorkOrderTest $workOrderTest)
    {
        $this->authorizeTest($request, $workOrderTest);

        $data = $request->validate([
            'action' => ['required', 'in:accept,start,hold,resume,submit,complete'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'result_status' => ['nullable', 'in:PASS,FAIL,OOS,OOT,PENDING'],
        ]);

        $map = [
            'accept' => WorkStatus::ACCEPTED,
            'start' => WorkStatus::IN_PROGRESS,
            'hold' => WorkStatus::ON_HOLD,
            'resume' => WorkStatus::IN_PROGRESS,
            'submit' => WorkStatus::SUBMITTED,
            'complete' => WorkStatus::COMPLETED,
        ];

        $to = $map[$data['action']];

        // Submitting requires an entered result.
        if ($data['action'] === 'submit') {
            $hasResult = TestResult::where('work_order_test_id', $workOrderTest->id)->exists();

            if (! $hasResult) {
                return back()->withErrors(['result' => 'Enter and save the test result before submitting for review.']);
            }
        }

        if ($data['action'] === 'complete' && $workOrderTest->status !== WorkStatus::APPROVED) {
            return back()->withErrors(['action' => 'Only approved tests can be marked as completed.']);
        }

        // On hold requires a comment.
        if ($data['action'] === 'hold' && ! trim((string) ($data['comment'] ?? ''))) {
            return back()->withErrors(['comment' => 'A comment is required when putting work on hold.']);
        }

        try {
            $this->status->transitionTest($workOrderTest, $to, $data['comment'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }

        if (! empty($data['comment'])) {
            $workOrderTest->workOrder->comments()->create([
                'work_order_test_id' => $workOrderTest->id,
                'user_id' => $request->user()->id,
                'comment' => $data['comment'],
                'action_type' => $data['action'],
            ]);
        }

        if ($data['action'] === 'submit') {
            if ($result = $workOrderTest->latestResult) {
                $result->update(['result_status' => $data['result_status'] ?? $result->result_status]);
                $workOrderTest->update(['result_summary' => $result->result_value.' '.$result->unit]);
            }

            NotificationService::toRoles(
                ['qc_supervisor', 'qc_admin', 'reviewer'],
                'work.submitted',
                'Submitted for Review: '.$workOrderTest->workOrder?->work_no,
                'Test "'.$workOrderTest->testType?->name.'" submitted for review.',
                route('review.show', $workOrderTest)
            );
        }

        if ($data['action'] === 'complete') {
            $this->status->syncWorkOrder($workOrderTest->work_order_id);
        }

        AuditLogger::log('Execution', $data['action'], $workOrderTest->id);

        return back()->with('success', 'Action "'.ucfirst($data['action']).'" completed successfully.');
    }

    /**
     * Create or update the result for a test (parameter-driven when configured).
     */
    public function saveResult(Request $request, WorkOrderTest $workOrderTest)
    {
        $this->authorizeTest($request, $workOrderTest);

        $data = $request->validate([
            'instrument_id' => ['nullable', 'exists:instruments,id'],
            'test_method_id' => ['nullable', 'exists:test_methods,id'],
            'result_value' => ['required_without:parameters', 'nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'specification_reference' => ['nullable', 'string', 'max:255'],
            'result_status' => ['required', 'in:PASS,FAIL,OOS,OOT,PENDING'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'parameters' => ['nullable', 'array'],
            'parameters.*.value' => ['nullable', 'string', 'max:255'],
        ]);

        if (! in_array($workOrderTest->status, WorkStatus::EXECUTION, true)) {
            return back()->withErrors(['result' => 'Results can only be entered while the test is allocated or in progress.']);
        }

        DB::transaction(function () use ($data, $request, $workOrderTest) {
            $result = TestResult::firstOrNew(['work_order_test_id' => $workOrderTest->id]);
            $isNew = ! $result->exists;

            $result->fill([
                'analyst_id' => $request->user()->employee_id,
                'instrument_id' => $data['instrument_id'] ?? null,
                'test_method_id' => $data['test_method_id'] ?? $workOrderTest->test_method_id,
                'result_value' => $data['result_value'] ?? '',
                'unit' => $data['unit'] ?? null,
                'specification_reference' => $data['specification_reference'] ?? null,
                'result_status' => $data['result_status'],
                'start_time' => $data['start_time'] ?? $result->start_time ?? now(),
                'end_time' => $data['end_time'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'entered_at' => now(),
            ]);

            $result->save();

            if (! empty($data['parameters']) && is_array($data['parameters'])) {
                $params = $workOrderTest->testType->parameters->keyBy('id');

                foreach ($data['parameters'] as $pid => $value) {
                    $param = $params->get((int) $pid);

                    if (! $param) {
                        continue;
                    }

                    $result->parameters()->updateOrCreate(
                        ['test_parameter_id' => $param->id],
                        [
                            'parameter_name' => $param->parameter_name,
                            'value' => (string) ($value['value'] ?? ''),
                            'is_pass' => true,
                        ]
                    );
                }

                // Aggregate value: join parameter values for the headline field.
                $values = $result->parameters->pluck('value')->filter()->implode(', ');
                $result->update(['result_value' => $values !== '' ? $values : $result->result_value]);
            }

            AuditLogger::log('Results', $isNew ? 'enter' : 'update', $workOrderTest->id, null, [
                'result' => $result->result_value,
                'status' => $result->result_status,
            ]);
        });

        return back()->with('success', 'Test result saved.');
    }

    private function authorizeTest(Request $request, WorkOrderTest $test): void
    {
        $user = $request->user();

        if ($user->hasAnyRole(['super_admin', 'qc_admin', 'qc_hod', 'qc_supervisor', 'reviewer'])) {
            return;
        }

        if ($test->assigned_analyst_id !== $user->employee_id) {
            abort(403, 'This test is not assigned to you.');
        }
    }
}
