@extends('layouts.app')

@section('title', 'Active Users')

@section('content')
    <div class="page-header">
        <div>
            <h1>Active Users</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                    <li class="breadcrumb-item active">Active Users</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('settings.section', 'usage') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-sliders me-1"></i>Configure Limit
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-primary"><i class="bi bi-people"></i></span>
                <div class="kpi-body">
                    <div class="kpi-value">{{ $activeCount }}{{ $maxUsers > 0 ? ' / '.$maxUsers : '' }}</div>
                    <div class="kpi-label">Active Users</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-success"><i class="bi bi-universal-access"></i></span>
                <div class="kpi-body">
                    <div class="kpi-value">{{ $maxUsers > 0 ? max(0, $maxUsers - $activeCount) : '∞' }}</div>
                    <div class="kpi-label">Available Slots</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-warning"><i class="bi bi-percent"></i></span>
                <div class="kpi-body">
                    <div class="kpi-value">{{ $maxUsers > 0 ? round($activeCount / max(1, $maxUsers) * 100) : 0 }}%</div>
                    <div class="kpi-label">Usage{{ $maxUsers > 0 && $activeCount >= $maxUsers ? ' — Full Capacity' : '' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-info"><i class="bi bi-arrow-repeat"></i></span>
                <div class="kpi-body">
                    <div class="kpi-value">{{ $sessions->count() }}</div>
                    <div class="kpi-label">Live Sessions</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Sessions with a live slot</span>
            <span class="small text-muted">Stale sessions are released automatically</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Login Time</th>
                        <th>Last Activity</th>
                        <th>Timeout</th>
                        <th>Session Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($sessions as $session)
                    @php
                        $idleMinutes = $session->last_activity_at ? (int) $session->last_activity_at->diffInMinutes(now()) : 0;
                        $timeout = $session->timeout_minutes ?? \App\Support\AppSettings::sessionDefault();
                        $isRecent = $idleMinutes <= 2;
                    @endphp
                    <tr>
                        <td>
                            <span class="avatar avatar-sm me-2">{{ strtoupper(substr($session->user?->name ?? '?', 0, 1)) }}</span>
                            <strong class="small">{{ $session->user?->name ?? 'Deleted user' }}</strong>
                        </td>
                        <td class="small">{{ $session->user?->username ?? '—' }}</td>
                        <td><span class="badge text-bg-primary">{{ $session->user?->primaryRole()?->name ?? '—' }}</span></td>
                        <td class="small text-muted">{{ $session->login_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="small text-muted">{{ $session->last_activity_at?->format('d M Y H:i') ?? '—' }} ({{ $idleMinutes }}m ago)</td>
                        <td class="small">{{ \App\Support\AppSettings::minutesLabel((int) $timeout) }}</td>
                        <td>
                            <span class="badge text-bg-{{ $isRecent ? 'success' : 'info' }}">
                                {{ $isRecent ? 'Active' : 'Idle' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            @include('partials.empty', ['icon' => 'bi-people', 'title' => 'No active sessions.', 'message' => 'Users appear here after they sign in.'])
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
