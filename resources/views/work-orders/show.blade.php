@extends('layouts.app')

@section('title', $order->work_no)

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $order->work_no }}
                @include('partials.status-badge', ['status' => $order->status])
                @if($isOverdue)<span class="badge text-bg-danger ms-1">Overdue</span>@endif
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('work-orders.index') }}">Work Requests</a></li>
                    <li class="breadcrumb-item active">{{ $order->work_no }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @permission('allocations.view')
                <a href="{{ route('allocation.index', ['q' => $order->work_no, 'view' => 'all']) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-person-check me-1"></i>Allocation
                </a>
            @endpermission
            <a href="{{ route('work-orders.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            {{-- Sample info --}}
            <div class="card mb-3">
                <div class="card-header">Sample Information</div>
                <div class="card-body">
                    <div class="row g-3 small">
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Sample ID</span><strong>{{ $order->sample_id }}</strong></div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Sample Type</span>{{ $order->sampleType?->name ?? '—' }}</div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Product</span>{{ $order->product?->product_name ?? '—' }}</div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Material</span>{{ $order->material?->material_name ?? '—' }}</div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Batch No.</span><strong>{{ $order->batch_no }}</strong></div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">AR No.</span>{{ $order->ar_no ?? '—' }}</div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Quantity</span>{{ $order->quantity ?? '—' }}</div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Storage</span>{{ $order->storage_condition ?? '—' }}</div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Received</span>{{ $order->received_date?->format('d M Y') ?? '—' }}</div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Sampling</span>{{ $order->sampling_date?->format('d M Y') ?? '—' }}</div>
                        <div class="col-6 col-md-3"><span class="text-muted d-block">Department</span>{{ $order->department?->name }}</div>
                    </div>
                    @if($order->remarks)
                        <hr class="my-3">
                        <div class="small"><span class="text-muted">Remarks:</span> {{ $order->remarks }}</div>
                    @endif
                </div>
            </div>

            {{-- Tests --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Tests ({{ $order->tests->count() }})</span>
                    <span class="small text-muted">One work order · individually allocated</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>#</th><th>Test</th><th>Method</th><th>Analyst</th><th>Spec.</th><th>Status</th><th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($order->tests as $i => $test)
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <strong class="small">{{ $test->testType?->name }}</strong>
                                    <div class="small text-muted">{{ $test->testType?->test_code }} · {{ $test->priority }} · {{ $test->estimated_duration }}h</div>
                                </td>
                                <td class="small">{{ $test->testMethod?->name ?? '—' }}</td>
                                <td class="small">
                                    {{ $test->assignedAnalyst?->name ?? 'Unassigned' }}
                                    @if($test->assignedInstrument)
                                        <div class="text-muted"><i class="bi bi-cpu"></i> {{ $test->assignedInstrument?->instrument_id_code }}</div>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ \Illuminate\Support\Str::limit($test->specification ?? '—', 30) }}</td>
                                <td>@include('partials.status-badge', ['status' => $test->status])</td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        @if(request()->user()->hasPermission('allocations.view') && in_array($test->status, ['NEW', 'ALLOCATED', 'REWORK']))
                                            <a href="{{ route('allocation.index', ['q' => $order->work_no, 'view' => 'all']) }}" class="btn btn-outline-primary" title="Allocate">
                                                <i class="bi bi-person-plus"></i>
                                            </a>
                                        @endif
                                        @if(request()->user()->hasRole('reviewer') || request()->user()->hasPermission('review.view'))
                                            @if(in_array($test->status, ['SUBMITTED', 'UNDER_REVIEW']))
                                                <a href="{{ route('review.show', $test) }}" class="btn btn-outline-success" title="Review">
                                                    <i class="bi bi-clipboard-check"></i>
                                                </a>
                                            @endif
                                        @endif
                                        @if($test->assigned_analyst_id === request()->user()->employee_id)
                                            <a href="{{ route('my-work.show', $test) }}" class="btn btn-outline-secondary" title="Open in My Work">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Comments --}}
            <div class="card mb-3">
                <div class="card-header">Activity & Comments</div>
                <div class="card-body">
                    <div id="commentList" class="mb-3">
                        @forelse($order->comments as $c)
                            <div class="comment-entry mb-3 pb-3 border-bottom">
                                <strong>{{ $c->user?->name ?? 'System' }}</strong>
                                <small class="text-muted ms-2">{{ $c->created_at->diffForHumans() }}
                                    @if($c->action_type && $c->action_type !== 'comment')
                                        · <span class="badge text-bg-light">{{ $c->action_type }}</span>
                                    @endif
                                </small>
                                <p class="mb-0 small">{{ $c->comment }}</p>
                            </div>
                        @empty
                            <p class="empty-comment small text-muted mb-3">No comments yet. Start the discussion below.</p>
                        @endforelse
                    </div>
                    <form method="POST" action="{{ route('work-comments.store', $order) }}">
                        @csrf
                        <div class="mb-2">
                            <textarea name="comment" class="form-control @error('comment') is-invalid @enderror" rows="2"
                                      placeholder="Add a comment…" required>{{ old('comment') }}</textarea>
                            @error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" data-ajax-comment class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-send me-1"></i>Post Comment
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Key facts --}}
            <div class="card mb-3">
                <div class="card-header">Request Details</div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-6">Request Date</dt><dd class="col-6">{{ $order->request_date->format('d M Y') }}</dd>
                        <dt class="col-6">Required Date</dt>
                        <dd class="col-6 {{ $isOverdue ? 'text-danger fw-bold' : '' }}">{{ $order->required_date->format('d M Y') }}</dd>
                        <dt class="col-6">Priority</dt><dd class="col-6">@include('partials.priority-badge', ['priority' => $order->priority])</dd>
                        <dt class="col-6">Category</dt><dd class="col-6">{{ $order->workCategory?->name ?? '—' }}</dd>
                        <dt class="col-6">Requested By</dt><dd class="col-6">{{ $order->requestedBy?->name ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            {{-- Attachments --}}
            <div class="card mb-3">
                <div class="card-header">Attachments</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('attachments.store', $order) }}" enctype="multipart/form-data" class="mb-3">
                        @csrf
                        <div class="mb-2">
                            <input type="file" name="file" class="form-control form-control-sm @error('file') is-invalid @enderror" required>
                            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">PDF, images, Excel, CSV, TXT, DOC, ZIP · max 10 MB</div>
                        </div>
                        <div class="mb-2">
                            <input type="text" name="description" class="form-control form-control-sm" placeholder="Description (optional)">
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-upload me-1"></i>Upload
                        </button>
                    </form>

                    @forelse($order->attachments as $a)
                        <div class="d-flex align-items-center justify-content-between py-2 border-top small">
                            <div class="me-2">
                                <i class="bi bi-paperclip me-1 text-muted"></i>
                                <a href="{{ route('attachments.download', $a) }}" class="text-decoration-none">{{ $a->file_name }}</a>
                                <div class="text-muted" style="font-size:.72rem">
                                    {{ number_format($a->file_size / 1024, 1) }} KB · {{ $a->uploadedBy?->name }} · {{ $a->created_at->diffForHumans() }}
                                </div>
                            </div>
                            @if($a->uploaded_by_id === auth()->id() || request()->user()->hasPermission('work_orders.edit'))
                                <form action="{{ route('attachments.destroy', $a) }}" method="POST" data-confirm="Delete this file?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-link btn-sm text-danger p-0" type="submit" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="small text-muted mb-0">No attachments uploaded yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Cancel --}}
            @permission('work_orders.edit')
                @if(!in_array($order->status, ['COMPLETED', 'CANCELLED']))
                    <div class="card border-danger">
                        <div class="card-header text-danger">Danger Zone</div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('work-orders.cancel', $order) }}" data-confirm="Cancel this entire work request?">
                                @csrf
                                <label class="form-label small" for="reason">Cancellation reason <span class="required">*</span></label>
                                <textarea id="reason" name="reason" class="form-control form-control-sm @error('reason') is-invalid @enderror" rows="2" required></textarea>
                                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <button type="submit" class="btn btn-outline-danger btn-sm mt-2 w-100">Cancel Work Request</button>
                            </form>
                        </div>
                    </div>
                @endif
            @endpermission
        </div>
    </div>
@endsection
