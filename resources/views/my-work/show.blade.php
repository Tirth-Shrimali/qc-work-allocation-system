@extends('layouts.app')

@section('title', 'Test: '.$test->testType?->name)

@section('content')
    @php
        $status = $test->status;
        $canExecute = in_array($status, ['ALLOCATED', 'ACCEPTED', 'IN_PROGRESS', 'ON_HOLD', 'REWORK']);
        $hasResult = (bool) $result;
    @endphp

    <div class="page-header">
        <div>
            <h1>{{ $test->testType?->name }}
                @include('partials.status-badge', ['status' => $status])
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('my-work.index') }}">My Work</a></li>
                    <li class="breadcrumb-item active">{{ $test->workOrder?->work_no }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('work-orders.show', $test->work_order_id) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-clipboard2-data me-1"></i>View Work Order
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            {{-- Work / sample details --}}
            <div class="card mb-3">
                <div class="card-header">Work Details</div>
                <div class="card-body small">
                    <div class="row g-3">
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Work No.</span><strong>{{ $test->workOrder?->work_no }}</strong></div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Sample ID</span>{{ $test->workOrder?->sample_id }}</div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Product / Material</span>{{ $test->workOrder?->product?->product_name ?? $test->workOrder?->material?->material_name ?? '—' }}</div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Batch No.</span>{{ $test->workOrder?->batch_no }}</div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Department</span>{{ $test->workOrder?->department?->name }}</div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Required Date</span>
                            <span class="{{ $test->workOrder?->required_date?->isPast() ? 'text-danger fw-semibold' : '' }}">{{ $test->workOrder?->required_date?->format('d M Y') }}</span>
                        </div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Method</span>{{ $test->testMethod?->name ?? '—' }}</div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Specification</span>{{ $test->specification ?? '—' }}</div>
                    </div>
                </div>
            </div>

            {{-- Result entry --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Result Entry</span>
                    @if($hasResult && $result)
                        <span class="badge text-bg-{{ $result->result_status === 'PASS' ? 'success' : 'danger' }}">{{ $result->result_status }}</span>
                    @endif
                </div>
                <div class="card-body">
                    @error('result')
                        <div class="alert alert-danger py-2 small">{{ $message }}</div>
                    @enderror

                    @if(!$canExecute && !$hasResult)
                        <p class="small text-muted mb-0">Results can be entered once the test is allocated and in progress.</p>
                    @else
                        <form method="POST" action="{{ route('my-work.result', $test) }}">
                            @csrf

                            @if($parameters->isNotEmpty())
                                <div class="row g-3 mb-3">
                                    @foreach($parameters as $p)
                                        <div class="col-md-6">
                                            <label class="form-label" for="param_{{ $p->id }}">
                                                {{ $p->parameter_name }}
                                                @if($p->unit)<span class="text-muted fw-normal">({{ $p->unit }})</span>@endif
                                                @if($p->is_required)<span class="required">*</span>@endif
                                            </label>
                                            <input type="{{ in_array($p->field_type, ['number', 'decimal']) ? 'text' : ($p->field_type === 'date' ? 'date' : 'text') }}"
                                                   id="param_{{ $p->id }}"
                                                   name="parameters[{{ $p->id }}][value]"
                                                   class="form-control"
                                                   value="{{ $result?->parameters->firstWhere('test_parameter_id', $p->id)?->value }}"
                                                   @required($p->is_required && $canExecute)
                                                   placeholder="{{ $p->field_type === 'decimal' ? 'e.g. 99.4' : 'Enter value' }}">
                                        </div>
                                    @endforeach
                                </div>
                                <hr>
                            @endif

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="result_value">Result / Summary</label>
                                    <input type="text" id="result_value" name="result_value" class="form-control @error('result_value') is-invalid @enderror"
                                           value="{{ old('result_value', $result?->result_value) }}" {{ $parameters->isEmpty() ? 'required' : '' }}
                                           placeholder="e.g. 99.4" @disabled(!$canExecute)>
                                    @error('result_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="unit">Unit</label>
                                    <input type="text" id="unit" name="unit" class="form-control" value="{{ old('unit', $result?->unit) }}" placeholder="%" @disabled(!$canExecute)>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="specification_reference">Specification</label>
                                    <input type="text" id="specification_reference" name="specification_reference" class="form-control"
                                           value="{{ old('specification_reference', $result?->specification_reference ?? $test->specification) }}" placeholder="98.0–102.0 %" @disabled(!$canExecute)>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="result_status">Result Status <span class="required">*</span></label>
                                    <select id="result_status" name="result_status" class="form-select @error('result_status') is-invalid @enderror" @disabled(!$canExecute) required>
                                        @foreach(['PASS', 'FAIL', 'OOS', 'OOT', 'PENDING'] as $s)
                                            <option value="{{ $s }}" @selected(old('result_status', $result?->result_status ?? 'PASS') === $s)>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                    @error('result_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="instrument_id">Instrument</label>
                                    <select id="instrument_id" name="instrument_id" class="form-select" @disabled(!$canExecute)>
                                        <option value="">Select…</option>
                                        @foreach($instruments as $ins)
                                            <option value="{{ $ins->id }}" @selected(old('instrument_id', $test->assigned_instrument_id) == $ins->id)>
                                                {{ $ins->instrument_id_code }} — {{ $ins->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="test_method_id">Method</label>
                                    <select id="test_method_id" name="test_method_id" class="form-select" @disabled(!$canExecute)>
                                        <option value="">Select…</option>
                                        @foreach($test->testType?->methods ?? [] as $m)
                                            <option value="{{ $m->id }}" @selected(old('test_method_id', $result?->test_method_id ?? $test->test_method_id) == $m->id)>{{ $m->method_code }} — {{ $m->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="start_time">Start Date/Time</label>
                                    <input type="datetime-local" id="start_time" name="start_time" class="form-control"
                                           value="{{ old('start_time', optional($result?->start_time)->format('Y-m-d\TH:i')) }}" @disabled(!$canExecute)>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="end_time">End Date/Time</label>
                                    <input type="datetime-local" id="end_time" name="end_time" class="form-control"
                                           value="{{ old('end_time', optional($result?->end_time)->format('Y-m-d\TH:i')) }}" @disabled(!$canExecute)>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="remarks">Remarks / Raw Data Reference</label>
                                    <textarea id="remarks" name="remarks" class="form-control" rows="2" @disabled(!$canExecute)>{{ old('remarks', $result?->remarks) }}</textarea>
                                </div>
                            </div>

                            @if($canExecute)
                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i>Save Result
                                    </button>
                                </div>
                            @endif
                        </form>
                    @endif
                </div>
            </div>

            {{-- Review timeline --}}
            @if($test->reviewHistory->isNotEmpty() || $test->results->count())
                <div class="card mb-3">
                    <div class="card-header">Review & Result History</div>
                    <div class="card-body">
                        <div class="timeline">
                            @foreach($test->reviewHistory as $rh)
                                <div class="timeline-item">
                                    <strong class="small">{{ $rh->action }}</strong>
                                    <small>· {{ $rh->reviewer?->name }} · {{ $rh->created_at?->format('d M Y H:i') }}</small>
                                    @if($rh->reason)<p class="text-muted">{{ $rh->reason }}</p>@endif
                                    @if($rh->comments)<p class="text-muted">{{ $rh->comments }}</p>@endif
                                </div>
                            @endforeach
                            @foreach($test->results as $res)
                                <div class="timeline-item">
                                    <strong class="small">Result entered</strong>
                                    <small>· {{ $res->analyst?->name }} · {{ $res->created_at?->format('d M Y H:i') }}</small>
                                    <p>{{ $res->result_value }} {{ $res->unit }} — <span class="badge text-bg-{{ $res->result_status === 'PASS' ? 'success' : 'danger' }}">{{ $res->result_status }}</span></p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            {{-- Workflow actions --}}
            <div class="card mb-3">
                <div class="card-header">Workflow Actions</div>
                <div class="card-body">
                    @error('action')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror

                    @php
                        $actions = [
                            'ALLOCATED' => [['accept', 'Accept Work', 'primary', 'bi-hand-thumbs-up']],
                            'ACCEPTED' => [['start', 'Start Work', 'primary', 'bi-play-fill'], ['hold', 'Put On Hold', 'secondary', 'bi-pause-fill']],
                            'IN_PROGRESS' => [['submit', 'Submit for Review', 'success', 'bi-send-check'], ['hold', 'Put On Hold', 'secondary', 'bi-pause-fill']],
                            'ON_HOLD' => [['resume', 'Resume Work', 'primary', 'bi-play-fill']],
                            'REWORK' => [['start', 'Start Rework', 'primary', 'bi-arrow-repeat']],
                            'APPROVED' => [['complete', 'Mark Completed', 'success', 'bi-check-circle']],
                        ];
                        $available = $actions[$status] ?? [];
                    @endphp

                    @if($available)
                        @foreach($available as [$action, $label, $color, $icon])
                            <form method="POST" action="{{ route('my-work.action', $test) }}" class="mb-2" data-confirm="{{ $action === 'submit' ? 'Submit this result for review?' : ($action === 'complete' ? 'Mark this test as completed?' : '') }}">
                                @csrf
                                <input type="hidden" name="action" value="{{ $action }}">
                                <button type="submit" class="btn btn-{{ $color }} w-100">
                                    <i class="bi {{ $icon }} me-1"></i>{{ $label }}
                                </button>
                            </form>
                        @endforeach

                        @if(in_array($status, ['ACCEPTED', 'IN_PROGRESS']))
                            <hr>
                            <label class="form-label small" for="holdComment">Comment (required for hold / optional otherwise)</label>
                            <form method="POST" action="{{ route('my-work.action', $test) }}">
                                @csrf
                                <input type="hidden" name="action" value="hold">
                                <textarea id="holdComment" name="comment" class="form-control form-control-sm mb-2" rows="2" placeholder="Reason for hold…"></textarea>
                                @error('comment')<div class="text-danger small mb-1">{{ $message }}</div>@enderror
                                <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="bi bi-pause-circle me-1"></i>Put On Hold with Comment
                                </button>
                            </form>
                        @endif
                    @else
                        <p class="small text-muted mb-0">
                            Status: <strong>{{ \App\Support\WorkStatus::label($status) }}</strong>.
                            @if($status === 'SUBMITTED')Waiting for the reviewer to open this submission.@endif
                            @if($status === 'UNDER_REVIEW')Under review by the reviewer.@endif
                            @if($status === 'COMPLETED')This test is closed.@endif
                        </p>
                    @endif
                </div>
            </div>

            {{-- Allocation info --}}
            <div class="card mb-3">
                <div class="card-header">Assignment</div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-5">Analyst</dt><dd class="col-7">{{ $test->assignedAnalyst?->name ?? '—' }}</dd>
                        <dt class="col-5">Instrument</dt><dd class="col-7">{{ $test->assignedInstrument?->instrument_id_code ?? '—' }}</dd>
                        <dt class="col-5">Estimated</dt><dd class="col-7">{{ $test->estimated_duration }} hours</dd>
                        <dt class="col-5">Priority</dt><dd class="col-7">@include('partials.priority-badge', ['priority' => $test->priority])</dd>
                    </dl>
                </div>
            </div>

            {{-- Comments --}}
            <div class="card">
                <div class="card-header">Comments</div>
                <div class="card-body">
                    <div id="commentList" class="mb-3">
                        @forelse($test->workOrder->comments as $c)
                            <div class="comment-entry mb-3 pb-2 border-bottom">
                                <strong class="small">{{ $c->user?->name }}</strong>
                                <small class="text-muted ms-2">{{ $c->created_at->diffForHumans() }}</small>
                                <p class="mb-0 small">{{ $c->comment }}</p>
                            </div>
                        @empty
                            <p class="empty-comment small text-muted mb-2">No comments yet.</p>
                        @endforelse
                    </div>
                    <form method="POST" action="{{ route('work-comments.store', $test->work_order_id) }}">
                        @csrf
                        <input type="hidden" name="work_order_test_id" value="{{ $test->id }}">
                        <textarea name="comment" class="form-control form-control-sm mb-2" rows="2" placeholder="Add a comment…" required></textarea>
                        <button type="submit" data-ajax-comment class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-send me-1"></i>Post Comment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
