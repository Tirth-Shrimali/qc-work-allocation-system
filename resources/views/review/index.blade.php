@extends('layouts.app')

@section('title', 'Review')

@section('content')
    <div class="page-header">
        <div>
            <h1>Review & Approval</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Review</li>
                </ol>
            </nav>
        </div>
    </div>

    @php($currentView = request('view', 'pending'))

    <div class="tab-pills">
        <a href="{{ route('review.index') }}" class="{{ $currentView === 'pending' ? 'active' : '' }}">
            Pending <span class="count-pill {{ $currentView === 'pending' ? 'active' : '' }}">{{ $counts['pending'] }}</span>
        </a>
        <a href="{{ route('review.index', ['view' => 'in_review']) }}" class="{{ $currentView === 'in_review' ? 'active' : '' }}">
            In Review <span class="count-pill {{ $currentView === 'in_review' ? 'active' : '' }}">{{ $counts['in_review'] }}</span>
        </a>
        <a href="{{ route('review.index', ['view' => 'rework']) }}" class="{{ $currentView === 'rework' ? 'active' : '' }}">
            Rework <span class="count-pill {{ $currentView === 'rework' ? 'active' : '' }}">{{ $counts['rework'] }}</span>
        </a>
        <a href="{{ route('review.index', ['view' => 'history']) }}" class="{{ $currentView === 'history' ? 'active' : '' }}">History</a>
    </div>

    <form class="filter-bar" method="GET">
        <input type="hidden" name="view" value="{{ $currentView }}">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="q">Search</label>
                <input type="search" id="q" name="q" class="form-control" placeholder="Work no / sample id" value="{{ request('q') }}">
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Search</button></div>
            <div class="col-md-6 text-md-end"><span class="small text-muted">{{ $tests->total() }} test(s)</span></div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr><th>Work Order</th><th>Test</th><th>Analyst</th><th>Submitted</th><th>Priority</th><th>Status</th><th class="text-end">Action</th></tr>
                </thead>
                <tbody>
                @forelse($tests as $test)
                    <tr>
                        <td>
                            <a class="table-link" href="{{ route('review.show', $test) }}">{{ $test->workOrder?->work_no }}</a>
                            <div class="small text-muted">{{ $test->workOrder?->sample_id }}</div>
                        </td>
                        <td class="small"><strong>{{ $test->testType?->name }}</strong></td>
                        <td class="small">{{ $test->assignedAnalyst?->name ?? '—' }}</td>
                        <td class="small text-muted">{{ $test->updated_at->format('d M Y H:i') }}</td>
                        <td>@include('partials.priority-badge', ['priority' => $test->priority])</td>
                        <td>@include('partials.status-badge', ['status' => $test->status])</td>
                        <td class="text-end">
                            <a href="{{ route('review.show', $test) }}" class="btn btn-sm {{ $test->status === 'SUBMITTED' ? 'btn-primary' : 'btn-outline-primary' }}">
                                {{ $test->status === 'SUBMITTED' ? 'Start Review' : ($test->status === 'UNDER_REVIEW' ? 'Continue' : 'View') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            @include('partials.empty', [
                                'icon' => 'bi-clipboard-check',
                                'title' => $currentView === 'pending' ? 'Review queue is empty.' : 'Nothing here.',
                                'message' => 'Submitted results will appear here for review.',
                            ])
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3">{{ $tests->links() }}</div>
@endsection
