@extends('layouts.app')

@section('title', $employee->name)

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $employee->name }}
                <span class="badge text-bg-{{ $employee->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($employee->status) }}</span>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
                    <li class="breadcrumb-item active">{{ $employee->employee_code }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <span class="avatar" style="width:64px;height:64px;font-size:1.6rem">{{ strtoupper(substr($employee->first_name, 0, 1)) }}</span>
                    <h5 class="mt-2 mb-0">{{ $employee->name }}</h5>
                    <div class="small text-muted">{{ $employee->designation?->name }} · {{ $employee->department?->name }}</div>
                    <div class="mt-3 small text-start">
                        <dl class="row mb-0">
                            <dt class="col-5 text-muted">Code</dt><dd class="col-7">{{ $employee->employee_code }}</dd>
                            <dt class="col-5 text-muted">Email</dt><dd class="col-7">{{ $employee->email ?? '—' }}</dd>
                            <dt class="col-5 text-muted">Mobile</dt><dd class="col-7">{{ $employee->mobile ?? '—' }}</dd>
                            <dt class="col-5 text-muted">Joined</dt><dd class="col-7">{{ $employee->joining_date?->format('d M Y') ?? '—' }}</dd>
                            <dt class="col-5 text-muted">Login account</dt><dd class="col-7">{{ $employee->user?->username ?? 'Not linked' }}</dd>
                            <dt class="col-5 text-muted">Open work</dt><dd class="col-7"><span class="count-pill">{{ $openWork }}</span></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">Skills</div>
                <div class="card-body">
                    @forelse($employee->skills as $skill)
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">
                            <span>{{ $skill->name }}</span>
                            <span class="badge text-bg-{{ ['Beginner' => 'secondary', 'Intermediate' => 'info', 'Advanced' => 'success'][$skill->pivot->skill_level] ?? 'secondary' }}">
                                {{ $skill->pivot->skill_level }}
                            </span>
                        </div>
                    @empty
                        <p class="small text-muted mb-0">No skills mapped yet. Edit the employee to map skills.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">Recent Assigned Work</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>Work</th><th>Test</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse($employee->assignedTests->take(10) as $t)
                            <tr>
                                <td class="small">{{ $t->workOrder?->work_no }}</td>
                                <td class="small">{{ $t->testType?->name }}</td>
                                <td>@include('partials.status-badge', ['status' => $t->status])</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="small text-muted">No work assigned yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
