@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-header">
        <div>
            <h1>Supervisor Dashboard</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb"><li class="breadcrumb-item active">Dashboard</li></ol>
            </nav>
        </div>
        @permission('work_orders.create')
            <a href="{{ route('work-orders.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>New Work Request
            </a>
        @endpermission
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-danger"><i class="bi bi-hourglass-split"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $pendingAllocation }}</div><div class="kpi-label">Pending Allocation</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-primary"><i class="bi bi-person-check"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $allocated }}</div><div class="kpi-label">Tests In Execution</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-warning"><i class="bi bi-calendar-day"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $dueToday }}</div><div class="kpi-label">Due Today</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-danger"><i class="bi bi-exclamation-triangle"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $overdue }}</div><div class="kpi-label">Overdue</div></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Pending Allocation</span>
                    <a href="{{ route('allocation.index') }}" class="small">Open allocator</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Work No.</th><th>Test</th><th>Due</th><th></th></tr></thead>
                        <tbody>
                        @forelse($pendingAllocationList as $t)
                            <tr>
                                <td><a class="table-link" href="{{ route('work-orders.show', $t->work_order_id) }}">{{ $t->workOrder?->work_no }}</a></td>
                                <td>{{ $t->testType?->name }}</td>
                                <td class="small {{ $t->workOrder?->required_date?->isPast() ? 'text-danger' : 'text-muted' }}">{{ $t->workOrder?->required_date?->format('d M') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('allocation.index', ['view' => 'pending']) }}" class="btn btn-outline-primary btn-sm">Allocate</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">@include('partials.empty', ['icon' => 'bi-check2-circle', 'title' => 'All tests allocated', 'message' => 'Nothing is waiting for allocation right now.'])</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Pending Review Queue</span>
                    <a href="{{ route('review.index') }}" class="small">Open review</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Work No.</th><th>Test</th><th>Analyst</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse($pendingReview as $t)
                            <tr>
                                <td><a class="table-link" href="{{ route('review.show', $t) }}">{{ $t->workOrder?->work_no }}</a></td>
                                <td>{{ $t->testType?->name }}</td>
                                <td class="small">{{ $t->assignedAnalyst?->name ?? '—' }}</td>
                                <td>@include('partials.status-badge', ['status' => $t->status])</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">@include('partials.empty', ['icon' => 'bi-clipboard-check', 'title' => 'Review queue empty', 'message' => 'No results are waiting for review.'])</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Analyst Availability & Workload</span>
                    <span class="badge text-bg-success">{{ $availableAnalysts }} available</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Analyst</th><th>Designation</th><th>Open Tests</th><th>Availability</th></tr></thead>
                        <tbody>
                        @forelse($analystWorkload as $a)
                            <tr>
                                <td>
                                    <span class="avatar avatar-sm me-2">{{ strtoupper(substr($a->first_name, 0, 1)) }}</span>
                                    {{ $a->fullName() }}
                                </td>
                                <td class="text-muted small">{{ $a->designation?->name }}</td>
                                <td style="width:30%">
                                    @php($pct = min(100, round($a->open_work / 8 * 100)))
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:7px">
                                            <div class="progress-bar {{ $a->open_work > 5 ? 'bg-danger' : ($a->open_work > 2 ? 'bg-warning' : 'bg-success') }}" style="width: {{ max(4, $pct) }}%"></div>
                                        </div>
                                        <span class="small text-muted">{{ $a->open_work }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($a->open_work < 3)
                                        <span class="badge text-bg-success">Available</span>
                                    @elseif($a->open_work < 6)
                                        <span class="badge text-bg-warning">Busy</span>
                                    @else
                                        <span class="badge text-bg-danger">Overloaded</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">@include('partials.empty', ['icon' => 'bi-people', 'title' => 'No analysts found', 'message' => 'Link employees to user accounts to see workload.'])</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
