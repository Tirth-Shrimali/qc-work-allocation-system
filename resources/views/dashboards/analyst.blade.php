@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-header">
        <div>
            <h1>My Dashboard</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb"><li class="breadcrumb-item active">Dashboard</li></ol>
            </nav>
        </div>
        <a href="{{ route('my-work.index') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-journal-check me-1"></i>Open My Work
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-info"><i class="bi bi-inbox"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $new }}</div><div class="kpi-label">New / Assigned</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-warning"><i class="bi bi-gear"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $inProgress }}</div><div class="kpi-label">In Progress</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-primary"><i class="bi bi-clipboard-check"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $submitted }}</div><div class="kpi-label">In Review</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-success"><i class="bi bi-check-circle"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $completed }}</div><div class="kpi-label">Approved / Done</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-secondary"><i class="bi bi-pause-circle"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $onHold }}</div><div class="kpi-label">On Hold</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-danger"><i class="bi bi-arrow-repeat"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $rework }}</div><div class="kpi-label">Rework</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-danger"><i class="bi bi-exclamation-octagon"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $overdue }}</div><div class="kpi-label">Overdue</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-primary"><i class="bi bi-journal-text"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $mine }}</div><div class="kpi-label">Total Assigned</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Upcoming Deadlines</span>
            <a href="{{ route('my-work.index', ['view' => 'active']) }}" class="small">View all</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr><th>Work No.</th><th>Test</th><th>Priority</th><th>Due</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                @forelse($upcoming as $t)
                    <tr>
                        <td><a class="table-link" href="{{ route('my-work.show', $t) }}">{{ $t->workOrder?->work_no }}</a></td>
                        <td>{{ $t->testType?->name }}</td>
                        <td>@include('partials.priority-badge', ['priority' => $t->priority])</td>
                        <td class="small {{ $t->workOrder?->required_date?->isPast() ? 'text-danger fw-semibold' : 'text-muted' }}">
                            {{ $t->workOrder?->required_date?->format('d M Y') }}
                        </td>
                        <td>@include('partials.status-badge', ['status' => $t->status])</td>
                        <td class="text-end">
                            <a href="{{ route('my-work.show', $t) }}" class="btn btn-outline-primary btn-sm">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">@include('partials.empty', ['icon' => 'bi-check2-circle', 'title' => 'No active work', 'message' => 'New assignments will appear here.', 'cta' => null])</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
