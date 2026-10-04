@extends('layouts.app')

@section('title', 'Review: '.$test->testType?->name)

@section('content')
    <div class="page-header">
        <div>
            <h1>Review — {{ $test->testType?->name }}
                @include('partials.status-badge', ['status' => $test->status])
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('review.index') }}">Review</a></li>
                    <li class="breadcrumb-item active">{{ $test->workOrder?->work_no }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('review.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Queue
        </a>
    </div>

    @error('action')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header">Work & Sample Details</div>
                <div class="card-body small">
                    <div class="row g-3">
                        <div class="col-6 col-md-4"><span class="text-muted d-block">Work No.</span>
                            <a class="table-link" href="{{ route('work-orders.show', $test->work_order_id) }}">{{ $test->workOrder?->work_no }}</a>
                        </div>
                        <div class="col-6 col-md-4"><span class="text-muted d-block">Sample ID</span>{{ $test->workOrder?->sample_id }}</div>
                        <div class="col-6 col-md-4"><span class="text-muted d-block">Batch</span>{{ $test->workOrder?->batch_no }}</div>
                        <div class="col-6 col-md-4"><span class="text-muted d-block">Product</span>{{ $test->workOrder?->product?->product_name ?? '—' }}</div>
                        <div class="col-6 col-md-4"><span class="text-muted d-block">Department</span>{{ $test->workOrder?->department?->name }}</div>
                        <div class="col-6 col-md-4"><span class="text-muted d-block">Requested By</span>{{ $test->workOrder?->requestedBy?->name }}</div>
                        <div class="col-6 col-md-4"><span class="text-muted d-block">Required Date</span>{{ $test->workOrder?->required_date?->format('d M Y') }}</div>
                        <div class="col-6 col-md-4"><span class="text-muted d-block">Analyst</span><strong>{{ $test->assignedAnalyst?->name ?? '—' }}</strong></div>
                        <div class="col-6 col-md-4"><span class="text-muted d-block">Specification</span>{{ $test->specification ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Submitted Result</span>
                    @if($result)
                        <span class="badge text-bg-{{ $result->result_status === 'PASS' ? 'success' : 'danger' }}">{{ $result->result_status }}</span>
                    @endif
                </div>
                <div class="card-body">
                    @if(!$result)
                        <p class="small text-muted mb-0">No result recorded.</p>
                    @else
                        <div class="row g-3 small mb-3">
                            <div class="col-md-4"><span class="text-muted d-block">Result</span><strong class="fs-5">{{ $result->result_value }} {{ $result->unit }}</strong></div>
                            <div class="col-md-4"><span class="text-muted d-block">Specification</span>{{ $result->specification_reference ?? '—' }}</div>
                            <div class="col-md-4"><span class="text-muted d-block">Instrument</span>{{ $result->instrument?->instrument_id_code ?? '—' }}</div>
                            <div class="col-md-4"><span class="text-muted d-block">Method</span>{{ $result->method?->name ?? '—' }}</div>
                            <div class="col-md-4"><span class="text-muted d-block">Start</span>{{ $result->start_time?->format('d M Y H:i') ?? '—' }}</div>
                            <div class="col-md-4"><span class="text-muted d-block">End</span>{{ $result->end_time?->format('d M Y H:i') ?? '—' }}</div>
                            <div class="col-12"><span class="text-muted d-block">Remarks</span>{{ $result->remarks ?? '—' }}</div>
                        </div>

                        @if($result->parameters->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead><tr><th>Parameter</th><th>Value</th></tr></thead>
                                    <tbody>
                                    @foreach($result->parameters as $p)
                                        <tr><td>{{ $p->parameter_name }}</td><td>{{ $p->value }}</td></tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if($result->rework_reason)
                            <div class="alert alert-warning small mt-3 mb-0">
                                <strong>Previous rework reason:</strong> {{ $result->rework_reason }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Review history --}}
            <div class="card">
                <div class="card-header">Review Timeline</div>
                <div class="card-body">
                    @if($test->reviewHistory->isEmpty())
                        <p class="small text-muted mb-0">No review actions recorded yet.</p>
                    @else
                        <div class="timeline">
                            @foreach($test->reviewHistory as $rh)
                                <div class="timeline-item">
                                    <strong class="small text-uppercase">{{ $rh->action }}</strong>
                                    <small>· {{ $rh->reviewer?->name }} · {{ $rh->created_at?->format('d M Y H:i') }}</small>
                                    @if($rh->reason)<p class="text-muted">Reason: {{ $rh->reason }}</p>@endif
                                    @if($rh->comments)<p class="text-muted">{{ $rh->comments }}</p>@endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            {{-- Actions --}}
            @if($test->status === 'UNDER_REVIEW')
                <div class="card mb-3 border-success">
                    <div class="card-header text-success">Review Decision</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('review.act', $test) }}">
                            @csrf
                            <input type="hidden" name="action" value="approve">
                            <label class="form-label small" for="approveRemarks">Remarks (optional)</label>
                            <textarea id="approveRemarks" name="reason" class="form-control form-control-sm mb-2" rows="2" placeholder="Approved with remarks…"></textarea>
                            <button type="submit" class="btn btn-success w-100 mb-3" data-confirm="Approve this result?">
                                <i class="bi bi-check-circle me-1"></i>Approve
                            </button>
                        </form>

                        <form method="POST" action="{{ route('review.act', $test) }}">
                            @csrf
                            <input type="hidden" name="action" value="rework">
                            <label class="form-label small" for="reworkReason">Rework reason <span class="required">*</span></label>
                            <textarea id="reworkReason" name="reason" class="form-control form-control-sm mb-2 @error('reason') is-invalid @enderror" rows="2" required
                                      placeholder="Describe what must be corrected…"></textarea>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <button type="submit" class="btn btn-warning w-100 mb-3">
                                <i class="bi bi-arrow-repeat me-1"></i>Request Revision
                            </button>
                        </form>

                        <form method="POST" action="{{ route('review.act', $test) }}">
                            @csrf
                            <input type="hidden" name="action" value="reject">
                            <label class="form-label small" for="rejectReason">Rejection reason <span class="required">*</span></label>
                            <textarea id="rejectReason" name="reason" class="form-control form-control-sm mb-2" rows="2" required
                                      placeholder="Explain the rejection…"></textarea>
                            <button type="submit" class="btn btn-outline-danger w-100" data-confirm="Reject this result?">
                                <i class="bi bi-x-circle me-1"></i>Reject
                            </button>
                        </form>
                    </div>
                </div>
            @elseif($test->status === 'SUBMITTED')
                <div class="alert alert-info small">
                    This test was submitted and is awaiting the start of review.
                </div>
            @else
                <div class="alert alert-secondary small">
                    Status: <strong>{{ \App\Support\WorkStatus::label($test->status) }}</strong>. No actions available.
                </div>
            @endif

            {{-- Attachments --}}
            <div class="card mb-3">
                <div class="card-header">Attachments</div>
                <div class="card-body small">
                    @forelse($test->workOrder->attachments as $a)
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <a href="{{ route('attachments.download', $a) }}" class="text-decoration-none">
                                <i class="bi bi-paperclip me-1"></i>{{ $a->file_name }}
                            </a>
                            <span class="text-muted">{{ number_format($a->file_size / 1024, 1) }} KB</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No attachments.</p>
                    @endforelse
                </div>
            </div>

            {{-- Comments --}}
            <div class="card">
                <div class="card-header">Comments</div>
                <div class="card-body">
                    <div id="commentList" class="mb-3">
                        @forelse($test->workOrder->comments as $c)
                            <div class="comment-entry mb-2 pb-2 border-bottom">
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
