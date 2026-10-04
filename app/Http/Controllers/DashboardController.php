<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Employee;
use App\Models\WorkOrder;
use App\Models\WorkOrderTest;
use App\Support\WorkStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $roleCodes = $user->roles->pluck('code')->all();

        if (in_array('analyst', $roleCodes, true) && ! $user->hasAnyRole(['super_admin', 'qc_admin', 'qc_hod', 'qc_supervisor', 'reviewer', 'management'])) {
            return $this->analystDashboard($user);
        }

        if ($user->hasAnyRole(['super_admin', 'qc_admin', 'qc_hod', 'management'])) {
            return $this->managementDashboard($user);
        }

        if ($user->hasAnyRole(['qc_supervisor', 'reviewer'])) {
            return $this->supervisorDashboard($user);
        }

        return $this->managementDashboard($user);
    }

    private function managementDashboard($user)
    {
        $all = WorkOrder::query();

        $total = (clone $all)->count();
        $open = (clone $all)->whereIn('status', WorkStatus::OPEN)->count();
        $completed = (clone $all)->where('status', WorkStatus::COMPLETED)->count();
        $pendingReview = WorkOrderTest::whereIn('status', [WorkStatus::SUBMITTED, WorkStatus::UNDER_REVIEW])->count();
        $overdue = (clone $all)->overdue()->count();
        $newRequests = (clone $all)->where('status', WorkStatus::NEW)->count();
        $inProgress = (clone $all)->whereIn('status', [WorkStatus::IN_PROGRESS, WorkStatus::ON_HOLD])->count();
        $rework = WorkOrderTest::where('status', WorkStatus::REWORK)->count();

        $byPriority = (clone $all)->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->selectRaw('priority, COUNT(*) as c')->groupBy('priority')->pluck('c', 'priority');

        $byDepartment = (clone $all)->whereNotIn('work_orders.status', ['COMPLETED', 'CANCELLED'])
            ->join('departments', 'departments.id', '=', 'work_orders.department_id')
            ->selectRaw('departments.name as name, COUNT(*) as c')
            ->groupBy('departments.name')->pluck('c', 'name');

        $trend = (clone $all)
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')->orderBy('d')->pluck('c', 'd');

        $trendData = collect(range(29, 0))->map(function ($i) use ($trend) {
            $day = now()->subDays($i)->toDateString();

            return ['date' => $day, 'count' => (int) ($trend[$day] ?? 0)];
        });

        $analystWorkload = Employee::query()
            ->active()
            ->withCount(['assignedTests as open_work' => fn ($q) => $q->whereIn('status', WorkStatus::EXECUTION)])
            ->orderByDesc('open_work')
            ->limit(8)
            ->get()
            ->filter(fn ($e) => $e->open_work > 0);

        return view('dashboards.management', compact(
            'total', 'open', 'completed', 'pendingReview', 'overdue', 'newRequests',
            'inProgress', 'rework', 'byPriority', 'byDepartment', 'trendData', 'analystWorkload'
        ));
    }

    private function supervisorDashboard($user)
    {
        $pendingAllocation = WorkOrderTest::where('status', WorkStatus::NEW)
            ->whereNull('assigned_analyst_id')
            ->with(['workOrder', 'testType'])
            ->count();

        $pendingAllocationList = WorkOrderTest::where('status', WorkStatus::NEW)
            ->whereNull('assigned_analyst_id')
            ->with(['workOrder:id,work_no,sample_id,required_date,priority', 'testType:id,name'])
            ->orderBy('created_at')
            ->limit(8)
            ->get();

        $allocated = WorkOrderTest::whereIn('status', WorkStatus::EXECUTION)->count();
        $pendingReview = WorkOrderTest::whereIn('status', [WorkStatus::SUBMITTED, WorkStatus::UNDER_REVIEW])
            ->with('workOrder:id,work_no')
            ->orderBy('updated_at')
            ->limit(8)
            ->get();

        $overdue = WorkOrder::overdue()->count();
        $dueToday = WorkOrder::whereDate('required_date', now()->toDateString())
            ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->count();

        $analystWorkload = Employee::query()
            ->active()
            ->with(['designation:id,name'])
            ->withCount(['assignedTests as open_work' => fn ($q) => $q->whereIn('status', WorkStatus::EXECUTION)])
            ->orderByDesc('open_work')
            ->get()
            ->filter(fn ($e) => $e->user()->exists());

        $availableAnalysts = $analystWorkload->filter(fn ($e) => $e->open_work < 3)->count();

        return view('dashboards.supervisor', compact(
            'pendingAllocation', 'pendingAllocationList', 'allocated', 'pendingReview',
            'overdue', 'dueToday', 'analystWorkload', 'availableAnalysts'
        ));
    }

    private function analystDashboard($user)
    {
        $employee = $user->employee;

        $base = $employee
            ? WorkOrderTest::where('assigned_analyst_id', $employee->id)
            : WorkOrderTest::whereRaw('1 = 0');

        $mine = (clone $base)->count();
        $new = (clone $base)->where('status', WorkStatus::ALLOCATED)->count();
        $inProgress = (clone $base)->whereIn('status', [WorkStatus::ACCEPTED, WorkStatus::IN_PROGRESS])->count();
        $onHold = (clone $base)->where('status', WorkStatus::ON_HOLD)->count();
        $submitted = (clone $base)->whereIn('status', [WorkStatus::SUBMITTED, WorkStatus::UNDER_REVIEW])->count();
        $rework = (clone $base)->where('status', WorkStatus::REWORK)->count();
        $completed = (clone $base)->where('status', WorkStatus::APPROVED)->count() + (clone $base)->where('status', WorkStatus::COMPLETED)->count();
        $overdue = (clone $base)
            ->whereIn('status', WorkStatus::OPEN)
            ->whereHas('workOrder', fn ($q) => $q->overdue())
            ->count();

        $upcoming = (clone $base)
            ->whereIn('status', WorkStatus::EXECUTION)
            ->with(['workOrder:id,work_no,sample_id,required_date,priority', 'testType:id,name'])
            ->orderBy('created_at')
            ->limit(8)
            ->get();

        return view('dashboards.analyst', compact(
            'mine', 'new', 'inProgress', 'onHold', 'submitted', 'rework', 'completed', 'overdue', 'upcoming'
        ));
    }
}
