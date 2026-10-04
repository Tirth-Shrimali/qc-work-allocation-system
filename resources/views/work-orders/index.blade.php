@extends('layouts.app')

@section('title', 'Work Requests')

@section('content')
    <div class="page-header">
        <div>
            <h1>Work Requests</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Work Requests</li>
                </ol>
            </nav>
        </div>
        @permission('work_orders.create')
            <a href="{{ route('work-orders.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>New Work Request
            </a>
        @endpermission
    </div>

    <form class="filter-bar" method="GET" action="{{ route('work-orders.index') }}">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label" for="q">Search</label>
                <input type="search" id="q" name="q" class="form-control" placeholder="Work no / sample / batch / AR no" value="{{ $filters['q'] ?? '' }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ \App\Support\WorkStatus::label($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="priority">Priority</label>
                <select id="priority" name="priority" class="form-select">
                    <option value="">All priorities</option>
                    @foreach(['Critical', 'High', 'Normal', 'Low'] as $p)
                        <option value="{{ $p }}" @selected(($filters['priority'] ?? '') === $p)>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="department_id">Department</label>
                <select id="department_id" name="department_id" class="form-select">
                    <option value="">All departments</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" @selected(($filters['department_id'] ?? '') == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label" for="from">From</label>
                <input type="date" id="from" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label" for="to">To</label>
                <input type="date" id="to" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
            </div>
            <div class="col-12 col-md-1 d-grid">
                <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i></button>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <span class="small text-muted">{{ $orders->total() }} work request(s) found</span>
            <a href="{{ route('work-orders.index') }}" class="small">Reset filters</a>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Work ID</th>
                    <th>Sample / Product</th>
                    <th>Tests</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>
                        <a href="{{ route('work-orders.index', array_merge($filters, ['sort' => 'required_date'])) }}" class="text-decoration-none">
                            Deadline <i class="bi bi-arrow-down-up"></i>
                        </a>
                    </th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <a class="table-link" href="{{ route('work-orders.show', $order) }}">{{ $order->work_no }}</a>
                            <div class="small text-muted">{{ $order->request_date->format('d M Y') }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold small">{{ $order->sample_id }}</div>
                            <div class="small text-muted">{{ $order->product?->product_name ?? $order->material?->material_name ?? $order->sampleType?->name }}</div>
                        </td>
                        <td>
                            <span class="count-pill">{{ $order->tests->count() }}</span>
                            <span class="small text-muted">{{ \Illuminate\Support\Str::limit($order->tests->pluck('testType.name')->filter()->implode(', '), 40) }}</span>
                        </td>
                        <td>@include('partials.priority-badge', ['priority' => $order->priority])</td>
                        <td>@include('partials.status-badge', ['status' => $order->status])</td>
                        <td class="small {{ $order->isOverdue() ? 'text-danger fw-semibold' : 'text-muted' }}">
                            {{ $order->required_date->format('d M Y') }}
                            @if($order->isOverdue())
                                <i class="bi bi-exclamation-triangle-fill ms-1" title="Overdue"></i>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('work-orders.show', $order) }}" class="btn btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                                @permission('allocations.view')
                                    <a href="{{ route('allocation.index', ['q' => $order->work_no]) }}" class="btn btn-outline-secondary" title="Allocation"><i class="bi bi-person-check"></i></a>
                                @endpermission
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            @include('partials.empty', [
                                'icon' => 'bi-clipboard2-data',
                                'title' => 'No work found.',
                                'message' => 'No work requests match your search and filters.',
                                'cta' => request()->user()->hasPermission('work_orders.create') ? ['label' => 'Create Work', 'url' => route('work-orders.create')] : null,
                            ])
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $orders->links() }}
    </div>
@endsection
