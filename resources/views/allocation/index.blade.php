@extends('layouts.app')

@section('title', 'Allocation')

@section('content')
    <div class="page-header">
        <div>
            <h1>Work Allocation</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Allocation</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('allocation.history') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-clock-history me-1"></i>Allocation History
        </a>
    </div>

    <div class="tab-pills">
        <a href="{{ route('allocation.index', ['view' => 'pending']) }}" class="{{ $view === 'pending' ? 'active' : '' }}">
            Pending Allocation <span class="count-pill {{ $view === 'pending' ? 'active' : '' }}">{{ $counts['pending'] }}</span>
        </a>
        <a href="{{ route('allocation.index', ['view' => 'allocated']) }}" class="{{ $view === 'allocated' ? 'active' : '' }}">
            Allocated <span class="count-pill {{ $view === 'allocated' ? 'active' : '' }}">{{ $counts['allocated'] }}</span>
        </a>
        <a href="{{ route('allocation.index', ['view' => 'all']) }}" class="{{ $view === 'all' ? 'active' : '' }}">All Tests</a>
    </div>

    <form class="filter-bar" method="GET">
        <input type="hidden" name="view" value="{{ $view }}">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="q">Search</label>
                <input type="search" id="q" name="q" class="form-control" placeholder="Work no / sample / test name" value="{{ request('q') }}">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Search</button>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="small text-muted">{{ $tests->total() }} test(s)</span>
                <a href="{{ route('allocation.index', ['view' => $view]) }}" class="small ms-2">Reset</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Work Order</th>
                    <th>Test</th>
                    <th>Required Skill</th>
                    <th>Analyst</th>
                    <th>Due</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($tests as $test)
                    <tr>
                        <td>
                            <a class="table-link" href="{{ route('work-orders.show', $test->work_order_id) }}">{{ $test->workOrder?->work_no }}</a>
                            <div class="small text-muted">{{ $test->workOrder?->sample_id }}</div>
                        </td>
                        <td>
                            <strong class="small">{{ $test->testType?->name }}</strong>
                            <div class="small text-muted">{{ $test->testType?->test_code }} · {{ $test->estimated_duration }}h</div>
                        </td>
                        <td class="small">
                            @if($test->testType?->requiredSkill)
                                <span class="badge text-bg-light border">{{ $test->testType->requiredSkill->name }}</span>
                            @else
                                <span class="text-muted">Any</span>
                            @endif
                        </td>
                        <td class="small">{{ $test->assignedAnalyst?->name ?? '—' }}</td>
                        <td class="small {{ $test->workOrder?->required_date?->isPast() ? 'text-danger fw-semibold' : 'text-muted' }}">
                            {{ $test->workOrder?->required_date?->format('d M Y') }}
                        </td>
                        <td>@include('partials.status-badge', ['status' => $test->status])</td>
                        <td class="text-end">
                            @permission('allocations.allocate')
                                <button class="btn btn-primary btn-sm" data-allocate-btn
                                        data-test-id="{{ $test->id }}"
                                        data-test-name="{{ $test->testType?->name }}"
                                        data-work-no="{{ $test->workOrder?->work_no }}"
                                        data-current="{{ $test->assignedAnalyst?->name }}"
                                        @if(in_array($test->status, ['ACCEPTED', 'IN_PROGRESS', 'SUBMITTED', 'UNDER_REVIEW'])) disabled title="In execution — cannot reallocate" @endif>
                                    {{ $test->assigned_analyst_id ? 'Reallocate' : 'Allocate' }}
                                </button>
                            @endpermission
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            @include('partials.empty', [
                                'icon' => $view === 'pending' ? 'bi-check2-circle' : 'bi-search',
                                'title' => $view === 'pending' ? 'Nothing pending allocation' : 'No tests found.',
                                'message' => $view === 'pending' ? 'All new tests have been allocated to analysts.' : 'No tests match the current filters.',
                            ])
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3">{{ $tests->links() }}</div>

    {{-- Allocation modal --}}
    <div class="modal fade" id="allocateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="" id="allocateForm">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-0">Smart Allocation</h5>
                            <small class="text-muted" id="allocTestInfo">—</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="allocLoading" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="small text-muted mt-2">Analysing analyst workload & skills…</p>
                        </div>
                        <div id="allocError" class="alert alert-danger d-none">Something went wrong. Please try again.</div>
                        <div id="allocResults" class="d-none">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                    <tr><th style="width:34px"></th><th>Analyst</th><th>Skill</th><th>Workload</th><th>Availability</th></tr>
                                    </thead>
                                    <tbody id="allocCandidates"></tbody>
                                </table>
                            </div>
                            <div class="form-text mt-2">
                                Recommendations are sorted by skill match and current workload — the supervisor makes the final call.
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label" for="instrument_id">Instrument (optional)</label>
                                <select id="instrument_id" name="instrument_id" class="form-select">
                                    <option value="">No instrument</option>
                                    @foreach(\App\Models\Instrument::active()->available()->orderBy('name')->get() as $ins)
                                        <option value="{{ $ins->id }}">{{ $ins->instrument_id_code }} — {{ $ins->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="reason">Reason / remarks</label>
                                <input type="text" id="reason" name="reason" class="form-control" placeholder="Optional, stored in history">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="allocateSubmit" disabled>
                            <i class="bi bi-person-check me-1"></i>Confirm Allocation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modalEl = document.getElementById('allocateModal');
        var modal = new bootstrap.Modal(modalEl);
        var form = document.getElementById('allocateForm');
        var loading = document.getElementById('allocLoading');
        var results = document.getElementById('allocResults');
        var errorBox = document.getElementById('allocError');
        var tbody = document.getElementById('allocCandidates');
        var submit = document.getElementById('allocateSubmit');

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-allocate-btn]');
            if (!btn || btn.disabled) return;

            var testId = btn.dataset.testId;
            form.action = '/allocation/' + testId;
            document.getElementById('allocTestInfo').textContent =
                btn.dataset.workNo + ' · ' + btn.dataset.testName +
                (btn.dataset.current ? ' · current: ' + btn.dataset.current : '');
            submit.disabled = true;
            loading.classList.remove('d-none');
            results.classList.add('d-none');
            errorBox.classList.add('d-none');
            tbody.innerHTML = '';
            modal.show();

            fetch('/allocation/' + testId + '/candidates', { headers: { 'Accept': 'application/json' } })
                .then(function (r) {
                    if (!r.ok) throw new Error('failed');
                    return r.json();
                })
                .then(function (data) {
                    loading.classList.add('d-none');
                    results.classList.remove('d-none');
                    data.candidates.forEach(function (c, idx) {
                        var row = document.createElement('tr');
                        var workloadBadge = c.workload < 3
                            ? '<span class="badge text-bg-success">Low (' + c.workload + ')</span>'
                            : (c.workload < 6 ? '<span class="badge text-bg-warning">Medium (' + c.workload + ')</span>'
                                : '<span class="badge text-bg-danger">High (' + c.workload + ')</span>');
                        row.innerHTML =
                            '<td><input type="radio" name="analyst_id" value="' + c.id + '" class="form-check-input" ' + (idx === 0 ? '' : '') + '></td>' +
                            '<td><strong class="small">' + c.name + '</strong><div class="small text-muted">' + c.code + ' · ' + (c.designation || '') + '</div></td>' +
                            '<td>' + (c.skill_match
                                ? '<span class="badge text-bg-primary">' + (c.skill_level || 'Match') + '</span>'
                                : '<span class="badge text-bg-light border text-muted">No skill match</span>') + '</td>' +
                            '<td>' + workloadBadge + '</td>' +
                            '<td>' + (c.available
                                ? '<span class="badge text-bg-success">Available</span>'
                                : '<span class="badge text-bg-secondary">Busy</span>') + '</td>';
                        tbody.appendChild(row);
                    });
                    if (data.candidates.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted small py-3">No active analysts found with user accounts.</td></tr>';
                    }
                })
                .catch(function () {
                    loading.classList.add('d-none');
                    errorBox.classList.remove('d-none');
                });
        });

        tbody.addEventListener('change', function (e) {
            if (e.target.name === 'analyst_id') submit.disabled = false;
        });
    });
</script>
@endpush
