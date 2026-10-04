@extends('layouts.app')

@section('title', 'New Work Request')

@section('content')
    <div class="page-header">
        <div>
            <h1>New Work Request</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('work-orders.index') }}">Work Requests</a></li>
                    <li class="breadcrumb-item active">New</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('work-orders.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <form method="POST" action="{{ route('work-orders.store') }}" id="workOrderForm">
        @csrf

        <fieldset class="fieldset-card">
            <legend>Basic Information</legend>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Work ID <span class="text-muted fw-normal">(auto)</span></label>
                    <input type="text" class="form-control" value="{{ $nextWorkNo }}" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="request_date">Request Date <span class="required">*</span></label>
                    <input type="date" id="request_date" name="request_date" class="form-control @error('request_date') is-invalid @enderror"
                           value="{{ old('request_date', now()->toDateString()) }}" required>
                    @error('request_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="department_id">Department <span class="required">*</span></label>
                    <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                        <option value="">Select department…</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" @selected(old('department_id') == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="work_category_id">Work Category</label>
                    <select id="work_category_id" name="work_category_id" class="form-select">
                        <option value="">Select…</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('work_category_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="priority">Priority <span class="required">*</span></label>
                    <select id="priority" name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                        @foreach(['Low', 'Normal', 'High', 'Critical'] as $p)
                            <option value="{{ $p }}" @selected(old('priority', 'Normal') === $p)>{{ $p }}</option>
                        @endforeach
                    </select>
                    @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="priority_id">Priority Master Link</label>
                    <select id="priority_id" name="priority_id" class="form-select">
                        <option value="">None</option>
                        @foreach($priorities as $pr)
                            <option value="{{ $pr->id }}" @selected(old('priority_id') == $pr->id)>{{ $pr->name }} (L{{ $pr->level }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="required_date">Required Date <span class="required">*</span></label>
                    <input type="date" id="required_date" name="required_date" class="form-control @error('required_date') is-invalid @enderror"
                           value="{{ old('required_date', now()->addDays(3)->toDateString()) }}" required>
                    @error('required_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </fieldset>

        <fieldset class="fieldset-card">
            <legend>Sample Information</legend>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Sample ID <span class="text-muted fw-normal">(auto)</span></label>
                    <input type="text" class="form-control" value="{{ $nextSampleId }}" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="sample_type_id">Sample Type <span class="required">*</span></label>
                    <select id="sample_type_id" name="sample_type_id" class="form-select @error('sample_type_id') is-invalid @enderror" required>
                        <option value="">Select…</option>
                        @foreach($sampleTypes as $s)
                            <option value="{{ $s->id }}" @selected(old('sample_type_id') == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    @error('sample_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="product_id">Product</label>
                    <select id="product_id" name="product_id" class="form-select @error('product_id') is-invalid @enderror">
                        <option value="">Select…</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" @selected(old('product_id') == $p->id)>{{ $p->product_code }} — {{ $p->product_name }}</option>
                        @endforeach
                    </select>
                    @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="material_id">Material</label>
                    <select id="material_id" name="material_id" class="form-select">
                        <option value="">Select…</option>
                        @foreach($materials as $m)
                            <option value="{{ $m->id }}" @selected(old('material_id') == $m->id)>{{ $m->material_code }} — {{ $m->material_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="batch_no">Batch No. <span class="required">*</span></label>
                    <input type="text" id="batch_no" name="batch_no" class="form-control @error('batch_no') is-invalid @enderror"
                           value="{{ old('batch_no') }}" placeholder="e.g. B-2026-0451" required>
                    @error('batch_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="ar_no">AR No.</label>
                    <input type="text" id="ar_no" name="ar_no" class="form-control" value="{{ old('ar_no') }}" placeholder="Analytical request no">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="quantity">Quantity</label>
                    <input type="text" id="quantity" name="quantity" class="form-control" value="{{ old('quantity') }}" placeholder="e.g. 500 g / 10 vials">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="storage_condition">Storage Condition</label>
                    <input type="text" id="storage_condition" name="storage_condition" class="form-control" value="{{ old('storage_condition') }}" placeholder="e.g. 2–8 °C, desiccated">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="received_date">Received Date</label>
                    <input type="date" id="received_date" name="received_date" class="form-control" value="{{ old('received_date', now()->toDateString()) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="sampling_date">Sampling Date</label>
                    <input type="date" id="sampling_date" name="sampling_date" class="form-control @error('sampling_date') is-invalid @enderror" value="{{ old('sampling_date') }}">
                    @error('sampling_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                </div>
            </div>
        </fieldset>

        <fieldset class="fieldset-card">
            <legend>Test Selection <span class="required">*</span></legend>
            @error('tests')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror
            <div class="table-responsive">
                <table class="table align-middle" id="testsTable">
                    <thead>
                    <tr>
                        <th style="width:24%">Test Type</th>
                        <th style="width:20%">Method</th>
                        <th style="width:22%">Specification</th>
                        <th style="width:12%">Priority</th>
                        <th style="width:12%">Est. Hours</th>
                        <th style="width:10%"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @php($rows = old('tests', [['test_type_id' => '', 'test_method_id' => '', 'specification' => '', 'priority' => old('priority', 'Normal'), 'estimated_duration' => '2']]))
                    @foreach($rows as $i => $row)
                        @include('work-orders.partials.test-row', ['i' => $i, 'row' => $row, 'testTypes' => $testTypes])
                    @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="addTestRow">
                <i class="bi bi-plus-lg me-1"></i>Add another test
            </button>
        </fieldset>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('work-orders.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i>Create Work Request
            </button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tbody = document.querySelector('#testsTable tbody');
        var index = tbody.querySelectorAll('tr').length;
        var methodsByType = @json($testTypes->mapWithKeys(fn ($t) => [$t->id => $t->methods->map(fn ($m) => ['id' => $m->id, 'name' => $m->method_code.' — '.$m->name])]));

        document.getElementById('addTestRow').addEventListener('click', function () {
            var template = document.getElementById('testRowTemplate');
            var html = template.innerHTML.replace(/__INDEX__/g, index);
            tbody.insertAdjacentHTML('beforeend', html);
            index++;
        });

        tbody.addEventListener('change', function (e) {
            if (!e.target.matches('select[data-test-type]')) return;
            var select = e.target;
            var row = select.closest('tr');
            var methodSelect = row.querySelector('select[name$="[test_method_id]"]');
            var opts = methodsByType[select.value] || [];
            methodSelect.innerHTML = '<option value="">Select method…</option>' +
                opts.map(function (m) { return '<option value="' + m.id + '">' + m.name + '</option>'; }).join('');
        });

        tbody.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-remove-row]');
            if (!btn) return;
            if (tbody.querySelectorAll('tr').length <= 1) {
                window.qcToast('error', 'At least one test row is required.');
                return;
            }
            btn.closest('tr').remove();
        });
    });
</script>
@endpush
