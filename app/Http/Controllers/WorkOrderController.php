<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Material;
use App\Models\Priority;
use App\Models\Product;
use App\Models\SampleType;
use App\Models\TestMethod;
use App\Models\TestType;
use App\Models\WorkCategory;
use App\Models\WorkOrder;
use App\Services\AuditLogger;
use App\Services\StatusService;
use App\Services\WorkOrderService;
use App\Support\WorkStatus;
use Illuminate\Http\Request;

class WorkOrderController extends Controller
{
    public function __construct(private WorkOrderService $service, private StatusService $status)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = WorkOrder::query()
            ->with(['department', 'sampleType', 'product', 'requestedBy', 'tests.testType'])
            ->latest('id');

        // Analysts only see work orders containing their assigned tests.
        if ($user->hasRole('analyst') && ! $user->hasAnyRole(['super_admin', 'qc_admin', 'qc_hod', 'qc_supervisor', 'reviewer', 'management'])) {
            $employeeId = $user->employee_id;
            $query->whereHas('tests', fn ($q) => $q->where('assigned_analyst_id', $employeeId));
        }

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('work_no', 'like', "%{$search}%")
                    ->orWhere('sample_id', 'like', "%{$search}%")
                    ->orWhere('batch_no', 'like', "%{$search}%")
                    ->orWhere('ar_no', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($department = $request->input('department_id')) {
            $query->where('department_id', $department);
        }

        if ($request->filled('from')) {
            $query->whereDate('request_date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('request_date', '<=', $request->input('to'));
        }

        if ($request->input('sort') === 'required_date') {
            $query->orderBy('required_date');
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('work-orders.index', [
            'orders' => $orders,
            'departments' => Department::active()->orderBy('name')->get(),
            'statuses' => WorkStatus::all(),
            'filters' => $request->only(['q', 'status', 'priority', 'department_id', 'from', 'to', 'sort']),
        ]);
    }

    public function create()
    {
        return view('work-orders.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'request_date' => ['required', 'date'],
            'department_id' => ['required', 'exists:departments,id'],
            'work_category_id' => ['nullable', 'exists:work_categories,id'],
            'sample_type_id' => ['required', 'exists:sample_types,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'material_id' => ['nullable', 'exists:materials,id'],
            'batch_no' => ['required', 'string', 'max:100'],
            'ar_no' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'string', 'max:100'],
            'received_date' => ['nullable', 'date'],
            'sampling_date' => ['nullable', 'date', 'before_or_equal:received_date'],
            'storage_condition' => ['nullable', 'string', 'max:150'],
            'required_date' => ['required', 'date', 'after_or_equal:request_date'],
            'priority_id' => ['nullable', 'exists:priorities,id'],
            'priority' => ['required', 'string', 'max:20'],
            'remarks' => ['nullable', 'string'],
            'tests' => ['required', 'array', 'min:1'],
            'tests.*.test_type_id' => ['required', 'exists:test_types,id'],
            'tests.*.test_method_id' => ['nullable', 'exists:test_methods,id'],
            'tests.*.specification' => ['nullable', 'string'],
            'tests.*.priority' => ['nullable', 'string', 'max:20'],
            'tests.*.estimated_duration' => ['nullable', 'numeric', 'min:0.25', 'max:999'],
        ], [
            'tests.required' => 'Select at least one test for this work request.',
            'tests.min' => 'Select at least one test for this work request.',
        ]);

        $order = $this->service->create($data, $data['tests'], $request->user());

        return redirect()->route('work-orders.show', $order)
            ->with('success', "Work request {$order->work_no} created with ".count($order->tests).' test(s).');
    }

    public function show(WorkOrder $workOrder)
    {
        $workOrder->load([
            'department', 'sampleType', 'product', 'material', 'workCategory',
            'requestedBy', 'priorityRow',
            'tests.testType', 'tests.testMethod', 'tests.assignedAnalyst',
            'tests.assignedInstrument', 'tests.latestResult',
            'comments.user', 'attachments.uploadedBy',
        ]);

        return view('work-orders.show', [
            'order' => $workOrder,
            'isOverdue' => $workOrder->isOverdue(),
        ]);
    }

    public function update(Request $request, WorkOrder $workOrder)
    {
        $data = $request->validate([
            'required_date' => ['required', 'date'],
            'priority' => ['required', 'string', 'max:20'],
            'remarks' => ['nullable', 'string'],
            'storage_condition' => ['nullable', 'string', 'max:150'],
        ]);

        $old = $workOrder->only(array_keys($data));
        $workOrder->update($data);

        AuditLogger::log('Work Orders', 'update', $workOrder->id, $old, $data);

        return back()->with('success', 'Work request updated.');
    }

    public function cancel(Request $request, WorkOrder $workOrder)
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $openTests = $workOrder->tests()->whereNotIn('status', [WorkStatus::COMPLETED, WorkStatus::CANCELLED])->get();

        foreach ($openTests as $test) {
            if (WorkStatus::canTransition($test->status, WorkStatus::CANCELLED)) {
                $this->status->transitionTest($test, WorkStatus::CANCELLED, $request->input('reason'));
            }
        }

        $workOrder->update(['status' => WorkStatus::CANCELLED]);

        AuditLogger::log('Work Orders', 'cancel', $workOrder->id, null, ['reason' => $request->input('reason')]);

        return back()->with('success', 'Work request cancelled.');
    }

    private function formData(): array
    {
        return [
            'departments' => Department::active()->orderBy('name')->get(),
            'categories' => WorkCategory::active()->orderBy('name')->get(),
            'sampleTypes' => SampleType::active()->orderBy('name')->get(),
            'products' => Product::active()->orderBy('product_name')->get(),
            'materials' => Material::active()->orderBy('material_name')->get(),
            'priorities' => Priority::active()->ordered()->get(),
            'testTypes' => TestType::active()->with('methods')->orderBy('name')->get(),
            'nextWorkNo' => $this->service->generateWorkNo(),
            'nextSampleId' => $this->service->generateSampleId(),
        ];
    }
}
