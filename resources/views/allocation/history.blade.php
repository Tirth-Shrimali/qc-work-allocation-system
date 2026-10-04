@extends('layouts.app')

@section('title', 'Allocation History')

@section('content')
    <div class="page-header">
        <div>
            <h1>Allocation History</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('allocation.index') }}">Allocation</a></li>
                    <li class="breadcrumb-item active">History</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('allocation.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Allocation
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr><th>When</th><th>Work Order</th><th>Test</th><th>From</th><th>To</th><th>Allocated By</th><th>Reason</th></tr>
                </thead>
                <tbody>
                @forelse($history as $h)
                    <tr>
                        <td class="small text-muted">{{ $h->created_at?->format('d M Y H:i') }}</td>
                        <td><a class="table-link" href="{{ route('work-orders.show', $h->test?->work_order_id) }}">{{ $h->test?->workOrder?->work_no }}</a></td>
                        <td class="small">{{ $h->test?->testType?->name }}</td>
                        <td class="small">{{ $h->previousAnalyst?->name ?? '— (new allocation)' }}</td>
                        <td class="small"><strong>{{ $h->newAnalyst?->name }}</strong></td>
                        <td class="small">{{ $h->allocatedBy?->name }}</td>
                        <td class="small text-muted">{{ $h->reason ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">@include('partials.empty', ['icon' => 'bi-clock-history', 'title' => 'No allocation history', 'message' => 'Allocations and reallocations will be recorded here.'])</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3">{{ $history->links() }}</div>
@endsection
