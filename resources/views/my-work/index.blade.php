@extends('layouts.app')

@section('title', 'My Work')

@section('content')
    <div class="page-header">
        <div>
            <h1>My Work</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">My Work</li>
                </ol>
            </nav>
        </div>
    </div>

    @php($currentView = request('view'))

    <div class="tab-pills">
        <a href="{{ route('my-work.index') }}" class="{{ !$currentView ? 'active' : '' }}">All <span class="count-pill {{ !$currentView ? 'active' : '' }}">{{ $counts['todo'] + $counts['active'] + $counts['review'] + $counts['done'] }}</span></a>
        <a href="{{ route('my-work.index', ['view' => 'todo']) }}" class="{{ $currentView === 'todo' ? 'active' : '' }}">To Do <span class="count-pill {{ $currentView === 'todo' ? 'active' : '' }}">{{ $counts['todo'] }}</span></a>
        <a href="{{ route('my-work.index', ['view' => 'active']) }}" class="{{ $currentView === 'active' ? 'active' : '' }}">In Progress <span class="count-pill {{ $currentView === 'active' ? 'active' : '' }}">{{ $counts['active'] }}</span></a>
        <a href="{{ route('my-work.index', ['view' => 'review']) }}" class="{{ $currentView === 'review' ? 'active' : '' }}">In Review <span class="count-pill {{ $currentView === 'review' ? 'active' : '' }}">{{ $counts['review'] }}</span></a>
        <a href="{{ route('my-work.index', ['view' => 'done']) }}" class="{{ $currentView === 'done' ? 'active' : '' }}">Completed <span class="count-pill {{ $currentView === 'done' ? 'active' : '' }}">{{ $counts['done'] }}</span></a>
        <a href="{{ route('my-work.index', ['view' => 'overdue']) }}" class="{{ $currentView === 'overdue' ? 'active' : '' }}">Overdue</a>
    </div>

    <form class="filter-bar" method="GET">
        @if($currentView)<input type="hidden" name="view" value="{{ $currentView }}">@endif
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="q">Search</label>
                <input type="search" id="q" name="q" class="form-control" placeholder="Work no / sample id" value="{{ request('q') }}">
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Search</button></div>
            <div class="col-md-6 text-md-end"><span class="small text-muted">{{ $tests->total() }} test(s)</span></div>
        </div>
    </form>

    <div class="row g-3">
        @forelse($tests as $test)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <a class="table-link" href="{{ route('my-work.show', $test) }}">{{ $test->workOrder?->work_no }}</a>
                                <div class="small text-muted">{{ $test->workOrder?->sample_id }}</div>
                            </div>
                            @include('partials.status-badge', ['status' => $test->status])
                        </div>
                        <h6 class="mb-1">{{ $test->testType?->name }}</h6>
                        <div class="small text-muted mb-2">
                            Method: {{ $test->testMethod?->name ?? '—' }} · Est. {{ $test->estimated_duration }}h
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            @include('partials.priority-badge', ['priority' => $test->priority])
                            <span class="small {{ $test->workOrder?->required_date?->isPast() && !in_array($test->status, ['COMPLETED', 'APPROVED']) ? 'text-danger fw-semibold' : 'text-muted' }}">
                                <i class="bi bi-calendar3 me-1"></i>{{ $test->workOrder?->required_date?->format('d M Y') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-end">
                        <a href="{{ route('my-work.show', $test) }}" class="btn btn-sm btn-primary">
                            Open <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                @include('partials.empty', [
                    'icon' => 'bi-journal-check',
                    'title' => 'No work found.',
                    'message' => 'Tests allocated to you will appear here.',
                ])
            </div>
        @endforelse
    </div>

    <div class="d-flex justify-content-center mt-3">{{ $tests->links() }}</div>
@endsection
