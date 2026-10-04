@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    <div class="page-header">
        <div>
            <h1>Reports</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </nav>
        </div>
        @permission('reports.export')
            <a href="{{ route('reports.export', ['report' => $report, 'from' => $from, 'to' => $to]) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-filetype-csv me-1"></i>Export CSV
            </a>
        @endpermission
    </div>

    <div class="tab-pills">
        @foreach([
            'overview' => 'Overview',
            'productivity' => 'Analyst Productivity',
            'workload' => 'Test Workload',
            'tests' => 'Test Status',
            'overdue' => 'Overdue',
            'rework' => 'Rework',
        ] as $key => $label)
            <a href="{{ route('reports.index', ['report' => $key, 'from' => $from, 'to' => $to]) }}" class="{{ $report === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <form class="filter-bar" method="GET" action="{{ route('reports.index') }}">
        <input type="hidden" name="report" value="{{ $report }}">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="from">From Date</label>
                <input type="date" id="from" name="from" class="form-control" value="{{ $from }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="to">To Date</label>
                <input type="date" id="to" name="to" class="form-control" value="{{ $to }}">
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Apply</button></div>
            <div class="col-md-4 text-md-end small text-muted">Period: {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</div>
        </div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-primary"><i class="bi bi-clipboard2-data"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $summary['total'] }}</div><div class="kpi-label">Work in Period</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-success"><i class="bi bi-check-circle"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $summary['completed'] }}</div><div class="kpi-label">Completed</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-danger"><i class="bi bi-exclamation-octagon"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $summary['overdue'] }}</div><div class="kpi-label">Overdue (open)</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-warning"><i class="bi bi-arrow-repeat"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $summary['rework'] }}</div><div class="kpi-label">Open Rework</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ $data['title'] }}</span>
            <span class="small text-muted">{{ count($data['rows']) }} row(s)</span>
        </div>
        @include($data['view'], ['data' => $data])
    </div>
@endsection
