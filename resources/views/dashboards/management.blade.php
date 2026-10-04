@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-header">
        <div>
            <h1>Management Dashboard</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb"><li class="breadcrumb-item active">Dashboard</li></ol>
            </nav>
        </div>
        <div class="text-muted small">Real-time overview · {{ now()->format('d M Y, H:i') }}</div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-2">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-primary"><i class="bi bi-clipboard2-data"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $total }}</div><div class="kpi-label">Total Work</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-info"><i class="bi bi-plus-circle"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $newRequests }}</div><div class="kpi-label">New</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-warning"><i class="bi bi-gear"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $inProgress }}</div><div class="kpi-label">In Progress</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-primary"><i class="bi bi-clipboard-check"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $pendingReview }}</div><div class="kpi-label">Pending Review</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-success"><i class="bi bi-check-circle"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $completed }}</div><div class="kpi-label">Completed</div></div>
            </div>
        </div>
        <div class="col-6 col-xl-2">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-danger"><i class="bi bi-exclamation-octagon"></i></span>
                <div class="kpi-body"><div class="kpi-value">{{ $overdue }}</div><div class="kpi-label">Overdue</div></div>
            </div>
        </div>
    </div>

    @if (!empty($adminWidgets))
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <a href="{{ route('settings.active-users') }}" class="text-decoration-none">
                    <div class="kpi-card h-100">
                        <span class="kpi-icon bg-soft-primary"><i class="bi bi-people"></i></span>
                        <div class="kpi-body">
                            <div class="kpi-value">{{ $adminWidgets['activeUsers'] }}{{ $adminWidgets['maxUsers'] > 0 ? ' / '.$adminWidgets['maxUsers'] : '' }}</div>
                            <div class="kpi-label">Active Users{{ $adminWidgets['maxUsers'] > 0 && $adminWidgets['activeUsers'] >= $adminWidgets['maxUsers'] ? ' — Full Capacity' : '' }}</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-xl-3">
                <a href="{{ route('settings.section', 'usage') }}" class="text-decoration-none">
                    <div class="kpi-card h-100">
                        <span class="kpi-icon bg-soft-info"><i class="bi bi-kanban"></i></span>
                        <div class="kpi-body">
                            <div class="kpi-value">{{ $adminWidgets['activeProjects'] }}{{ $adminWidgets['maxProjects'] > 0 ? ' / '.$adminWidgets['maxProjects'] : '' }}</div>
                            <div class="kpi-label">Active Projects</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-xl-3">
                <a href="{{ route('settings.section', 'license') }}" class="text-decoration-none">
                    <div class="kpi-card h-100">
                        <span class="kpi-icon bg-soft-warning"><i class="bi bi-patch-check"></i></span>
                        <div class="kpi-body">
                            <div class="kpi-value">
                                <span class="badge text-bg-{{ ['active' => 'success', 'expiring' => 'warning', 'grace' => 'warning', 'expired' => 'danger', 'suspended' => 'danger'][$adminWidgets['licenseState']] ?? 'secondary' }}">
                                    {{ $adminWidgets['licenseStateLabel'] }}
                                </span>
                            </div>
                            <div class="kpi-label">
                                {{ $adminWidgets['remainingDays'] !== null ? ($adminWidgets['remainingDays'] >= 0 ? $adminWidgets['remainingDays'].' Days Remaining' : abs($adminWidgets['remainingDays']).' Days Overdue') : 'No Expiry Set' }}
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-xl-3">
                <a href="{{ route('settings.section', 'users') }}" class="text-decoration-none">
                    <div class="kpi-card h-100">
                        <span class="kpi-icon bg-soft-success"><i class="bi bi-person-plus"></i></span>
                        <div class="kpi-body">
                            <div class="kpi-value">{{ $adminWidgets['registrationEnabled'] ? 'Enabled' : 'Disabled' }}</div>
                            <div class="kpi-label">Registration{{ $adminWidgets['registrationApproval'] ? ' · Approval On' : '' }}</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Work Trends (last 30 days)</span>
                    <span class="badge text-bg-light">{{ $rework }} rework open</span>
                </div>
                <div class="card-body">
                    <div class="chart-box"><canvas id="trendChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">Priority Distribution (Open)</div>
                <div class="card-body">
                    <div class="chart-box"><canvas id="priorityChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Department Workload (Open)</div>
                <div class="card-body">
                    <div class="chart-box"><canvas id="deptChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <span>Analyst Workload</span>
                    @permission('reports.view')
                        <a href="{{ route('reports.index', ['report' => 'productivity']) }}" class="small">Full report</a>
                    @endpermission
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Analyst</th><th>Designation</th><th class="text-end">Open Tests</th></tr></thead>
                        <tbody>
                        @forelse($analystWorkload as $a)
                            <tr>
                                <td>{{ $a->fullName() }}</td>
                                <td class="text-muted small">{{ $a->designation?->name }}</td>
                                <td class="text-end">
                                    <span class="count-pill {{ $a->open_work > 5 ? 'bg-danger text-white' : '' }}">{{ $a->open_work }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3">@include('partials.empty', ['icon' => 'bi-people', 'title' => 'No active workload', 'message' => 'No analyst currently has open tests.'])</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var trend = @json($trendData);
        var priority = @json($byPriority);
        var dept = @json($byDepartment);

        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: trend.map(function (t) { return t.date.slice(5); }),
                datasets: [{
                    label: 'Work requests created',
                    data: trend.map(function (t) { return t.count; }),
                    borderColor: '#1d4e89',
                    backgroundColor: 'rgba(29,78,137,.12)',
                    fill: true,
                    tension: .35,
                    pointRadius: 2
                }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });

        new Chart(document.getElementById('priorityChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(priority),
                datasets: [{
                    data: Object.values(priority),
                    backgroundColor: ['#dc2626', '#d97706', '#1d4e89', '#0e7490', '#64748b'],
                    borderWidth: 0
                }]
            },
            options: { plugins: { legend: { position: 'bottom' } }, cutout: '62%' }
        });

        new Chart(document.getElementById('deptChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(dept),
                datasets: [{ label: 'Open work orders', data: Object.values(dept), backgroundColor: '#1d4e89', borderRadius: 6 }]
            },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
        });
    });
</script>
@endpush
