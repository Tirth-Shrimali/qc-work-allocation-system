@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
    <div class="page-header">
        <div>
            <h1>Audit Trail</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Audit Trail</li>
                </ol>
            </nav>
        </div>
    </div>

    <form class="filter-bar" method="GET" action="{{ route('audit.index') }}">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="q">Search</label>
                <input type="search" id="q" name="q" class="form-control" placeholder="Module / action / record" value="{{ request('q') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="module">Module</label>
                <select id="module" name="module" class="form-select">
                    <option value="">All modules</option>
                    @foreach($modules as $m)
                        <option value="{{ $m }}" @selected(request('module') === $m)>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="action">Action</label>
                <select id="action" name="action" class="form-select">
                    <option value="">All actions</option>
                    @foreach($actions as $a)
                        <option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="from">From</label>
                <input type="date" id="from" name="from" class="form-control" value="{{ request('from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="to">To</label>
                <input type="date" id="to" name="to" class="form-control" value="{{ request('to') }}">
            </div>
            <div class="col-md-1 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i></button></div>
        </div>
        <div class="mt-2 small text-muted">{{ $logs->total() }} log entry(s)</div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>When</th><th>User</th><th>Module</th><th>Action</th><th>Record</th><th>Details</th><th>IP</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="small text-muted white-space-nowrap">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                        <td class="small">{{ $log->user?->name ?? 'Guest' }}</td>
                        <td class="small"><span class="badge text-bg-light border">{{ $log->module }}</span></td>
                        <td class="small"><span class="badge text-bg-{{ str_contains($log->action, 'delete') || str_contains($log->action, 'deactivate') ? 'danger' : (str_contains($log->action, 'create') ? 'success' : 'primary') }}">{{ $log->action }}</span></td>
                        <td class="small text-muted">{{ $log->record_id ?? '—' }}</td>
                        <td class="small" style="max-width:340px">
                            @if($log->new_values)
                                <span class="text-muted">{{ \Illuminate\Support\Str::limit(json_encode($log->new_values), 120) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="small text-muted">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">@include('partials.empty', ['icon' => 'bi-clock-history', 'title' => 'No audit entries.', 'message' => 'System actions will be recorded here.'])</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3">{{ $logs->links() }}</div>
@endsection
