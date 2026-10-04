<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\WorkOrder;
use App\Models\WorkOrderTest;
use App\Support\WorkStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $report = $request->input('report', 'overview');
        [$from, $to] = $this->range($request);

        $data = match ($report) {
            'productivity' => $this->productivity($from, $to),
            'workload' => $this->workload($from, $to),
            'overdue' => $this->overdue(),
            'rework' => $this->rework($from, $to),
            'tests' => $this->testTypeLoad($from, $to),
            default => $this->overview($from, $to),
        };

        return view('reports.index', [
            'report' => $report,
            'from' => $from,
            'to' => $to,
            'data' => $data,
            'summary' => $this->summary($from, $to),
        ]);
    }

    public function export(Request $request)
    {
        $report = $request->input('report', 'overview');
        [$from, $to] = $this->range($request);

        $data = match ($report) {
            'productivity' => $this->productivity($from, $to),
            'workload' => $this->workload($from, $to),
            'overdue' => $this->overdue(),
            'rework' => $this->rework($from, $to),
            'tests' => $this->testTypeLoad($from, $to),
            default => $this->overview($from, $to),
        };

        $rows = $data['rows'];
        $headers = $data['headers'];

        $filename = 'qc-report-'.$report.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows, $headers) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function range(Request $request): array
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        return [$from, $to];
    }

    private function summary(string $from, string $to): array
    {
        $base = fn () => WorkOrder::whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to);

        return [
            'total' => $base()->count(),
            'completed' => $base()->where('status', WorkStatus::COMPLETED)->count(),
            'overdue' => WorkOrder::overdue()->count(),
            'rework' => WorkOrderTest::where('status', WorkStatus::REWORK)
                ->whereHas('workOrder', fn ($q) => $q->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to))
                ->count(),
        ];
    }

    private function overview(string $from, string $to): array
    {
        $rows = WorkOrder::query()
            ->join('departments', 'departments.id', '=', 'work_orders.department_id')
            ->whereDate('work_orders.created_at', '>=', $from)
            ->whereDate('work_orders.created_at', '<=', $to)
            ->selectRaw('departments.name as department,
                COUNT(*) as total,
                SUM(CASE WHEN work_orders.status = "COMPLETED" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN work_orders.status IN ("NEW","ALLOCATED","ACCEPTED","IN_PROGRESS","ON_HOLD","SUBMITTED","UNDER_REVIEW","REWORK","APPROVED") THEN 1 ELSE 0 END) as open_work,
                SUM(CASE WHEN work_orders.status NOT IN ("COMPLETED","CANCELLED") AND work_orders.required_date < CURDATE() THEN 1 ELSE 0 END) as overdue')
            ->groupBy('departments.name')
            ->orderByDesc('total')
            ->get();

        return [
            'title' => 'Daily / Department Overview',
            'headers' => ['Department', 'Total', 'Completed', 'Open', 'Overdue'],
            'rows' => $rows->map(fn ($r) => [$r->department, $r->total, $r->completed, $r->open_work, $r->overdue])->all(),
            'view' => 'reports.partials.overview',
        ];
    }

    private function productivity(string $from, string $to): array
    {
        $rows = Employee::query()
            ->withCount(['testResults as results_entered' => fn ($q) => $q->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)])
            ->withCount(['assignedTests as assigned_total'])
            ->withCount(['assignedTests as completed' => fn ($q) => $q->where('status', WorkStatus::COMPLETED)])
            ->get()
            ->filter(fn ($e) => $e->results_entered > 0 || $e->assigned_total > 0)
            ->map(fn ($e) => [
                'name' => $e->fullName(),
                'department' => $e->department?->name,
                'assigned' => $e->assigned_total,
                'completed' => $e->completed,
                'results' => $e->results_entered,
            ]);

        return [
            'title' => 'Analyst Productivity',
            'headers' => ['Analyst', 'Department', 'Assigned', 'Completed', 'Results Entered'],
            'rows' => $rows->map(fn ($r) => [$r['name'], $r['department'], $r['assigned'], $r['completed'], $r['results']])->all(),
            'view' => 'reports.partials.productivity',
            'rowsData' => $rows->values(),
        ];
    }

    private function workload(string $from, string $to): array
    {
        $rows = WorkOrderTest::query()
            ->join('test_types', 'test_types.id', '=', 'work_order_tests.test_type_id')
            ->join('work_orders', 'work_orders.id', '=', 'work_order_tests.work_order_id')
            ->whereDate('work_orders.created_at', '>=', $from)
            ->whereDate('work_orders.created_at', '<=', $to)
            ->selectRaw('test_types.name as test_type,
                COUNT(*) as total,
                SUM(CASE WHEN work_order_tests.status = "COMPLETED" THEN 1 ELSE 0 END) as completed,
                AVG(CASE WHEN work_order_tests.status = "COMPLETED" THEN DATEDIFF(work_order_tests.updated_at, work_orders.created_at) END) as avg_days')
            ->groupBy('test_types.name')
            ->orderByDesc('total')
            ->get();

        return [
            'title' => 'Test Type Workload & Average Completion',
            'headers' => ['Test Type', 'Total Tests', 'Completed', 'Avg. Days to Complete'],
            'rows' => $rows->map(fn ($r) => [$r->test_type, $r->total, $r->completed, $r->avg_days !== null ? round($r->avg_days, 1) : '—'])->all(),
            'view' => 'reports.partials.workload',
            'rowsData' => $rows,
        ];
    }

    private function overdue(): array
    {
        $rows = WorkOrder::overdue()
            ->with(['department', 'tests.testType'])
            ->orderBy('required_date')
            ->get();

        return [
            'title' => 'Overdue Work Orders',
            'headers' => ['Work No.', 'Sample', 'Department', 'Priority', 'Required Date', 'Status'],
            'rows' => $rows->map(fn ($w) => [$w->work_no, $w->sample_id, $w->department?->name, $w->priority, $w->required_date->toDateString(), $w->status])->all(),
            'view' => 'reports.partials.overdue',
            'rowsData' => $rows,
        ];
    }

    private function rework(string $from, string $to): array
    {
        $rows = WorkOrderTest::query()
            ->where('status', WorkStatus::REWORK)
            ->orWhere('review_status', 'REWORK')
            ->with(['workOrder:id,work_no,sample_id', 'testType:id,name', 'assignedAnalyst'])
            ->whereHas('workOrder', fn ($q) => $q->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to))
            ->get();

        return [
            'title' => 'Rework Analysis',
            'headers' => ['Work No.', 'Test', 'Analyst', 'Status'],
            'rows' => $rows->map(fn ($t) => [$t->workOrder?->work_no, $t->testType?->name, $t->assignedAnalyst?->name, $t->status])->all(),
            'view' => 'reports.partials.rework',
            'rowsData' => $rows,
        ];
    }

    private function testTypeLoad(string $from, string $to): array
    {
        $rows = WorkOrderTest::query()
            ->join('test_types', 'test_types.id', '=', 'work_order_tests.test_type_id')
            ->join('work_orders', 'work_orders.id', '=', 'work_order_tests.work_order_id')
            ->whereDate('work_orders.created_at', '>=', $from)
            ->whereDate('work_orders.created_at', '<=', $to)
            ->selectRaw('test_types.name, COUNT(*) as c,
                SUM(CASE WHEN work_order_tests.status IN ("NEW") THEN 1 ELSE 0 END) as pending_alloc,
                SUM(CASE WHEN work_order_tests.status IN ("ALLOCATED","ACCEPTED","IN_PROGRESS","ON_HOLD","REWORK") THEN 1 ELSE 0 END) as in_execution,
                SUM(CASE WHEN work_order_tests.status IN ("SUBMITTED","UNDER_REVIEW") THEN 1 ELSE 0 END) as in_review,
                SUM(CASE WHEN work_order_tests.status IN ("APPROVED","COMPLETED") THEN 1 ELSE 0 END) as done')
            ->groupBy('test_types.name')
            ->orderByDesc('c')
            ->get();

        return [
            'title' => 'Test Type Status Distribution',
            'headers' => ['Test Type', 'Total', 'Pending Allocation', 'In Execution', 'In Review', 'Done'],
            'rows' => $rows->map(fn ($r) => [$r->name, $r->c, $r->pending_alloc, $r->in_execution, $r->in_review, $r->done])->all(),
            'view' => 'reports.partials.tests',
            'rowsData' => $rows,
        ];
    }
}
