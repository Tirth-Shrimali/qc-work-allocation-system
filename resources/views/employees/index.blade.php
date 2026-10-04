@extends('layouts.app')

@section('title', 'Employees')

@section('content')
    <div class="page-header">
        <div>
            <h1>Employees</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Employees</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('employees.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i>Add Employee
        </a>
    </div>

    <form class="filter-bar" method="GET" action="{{ route('employees.index') }}">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="q">Search</label>
                <input type="search" id="q" name="q" class="form-control" placeholder="Code / name / email" value="{{ request('q') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="department_id">Department</label>
                <select id="department_id" name="department_id" class="form-select">
                    <option value="">All</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Search</button></div>
            <div class="col-md-3 text-md-end"><span class="small text-muted">{{ $employees->total() }} employee(s)</span></div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr><th>Code</th><th>Name</th><th>Department</th><th>Designation</th><th>Contact</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td><span class="fw-semibold">{{ $e->employee_code }}</span></td>
                        <td>
                            <span class="avatar avatar-sm me-2">{{ strtoupper(substr($e->first_name, 0, 1)) }}</span>
                            {{ $e->name }}
                        </td>
                        <td class="small">{{ $e->department?->name }}</td>
                        <td class="small text-muted">{{ $e->designation?->name }}</td>
                        <td class="small text-muted">
                            {{ $e->email ?? '—' }}
                            @if($e->mobile)<div>{{ $e->mobile }}</div>@endif
                        </td>
                        <td><span class="badge text-bg-{{ $e->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($e->status) }}</span></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('employees.show', $e) }}" class="btn btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('employees.edit', $e) }}" class="btn btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">@include('partials.empty', ['icon' => 'bi-people', 'title' => 'No employees found.', 'message' => 'Add employees and link them to user accounts.', 'cta' => ['label' => 'Add Employee', 'url' => route('employees.create')]])</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3">{{ $employees->links() }}</div>
@endsection
