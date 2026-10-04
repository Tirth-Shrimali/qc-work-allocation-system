@extends('layouts.app')

@section('title', ($employee ? 'Edit Employee' : 'New Employee'))

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $employee ? 'Edit Employee' : 'New Employee' }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
                    <li class="breadcrumb-item active">{{ $employee ? $employee->employee_code : 'New' }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8 col-xl-7">
            <div class="card">
                <div class="card-header">{{ $employee ? 'Edit' : 'Create' }} Employee</div>
                <div class="card-body">
                    <form method="POST" action="{{ $employee ? route('employees.update', $employee) : route('employees.store') }}">
                        @csrf
                        @if($employee) @method('PUT') @endif

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="employee_code">Employee Code <span class="required">*</span></label>
                                <input type="text" id="employee_code" name="employee_code" class="form-control @error('employee_code') is-invalid @enderror"
                                       value="{{ old('employee_code', $employee?->employee_code) }}" placeholder="EMP-001" required>
                                @error('employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
                                       value="{{ old('first_name', $employee?->first_name) }}" required>
                                @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                                       value="{{ old('last_name', $employee?->last_name) }}" required>
                                @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="department_id">Department <span class="required">*</span></label>
                                <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                                    <option value="">Select…</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d->id }}" @selected(old('department_id', $employee?->department_id) == $d->id)>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                                @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="designation_id">Designation <span class="required">*</span></label>
                                <select id="designation_id" name="designation_id" class="form-select @error('designation_id') is-invalid @enderror" required>
                                    <option value="">Select…</option>
                                    @foreach($designations as $d)
                                        <option value="{{ $d->id }}" @selected(old('designation_id', $employee?->designation_id) == $d->id)>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                                @error('designation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $employee?->email) }}">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mobile">Mobile</label>
                                <input type="text" id="mobile" name="mobile" class="form-control" value="{{ old('mobile', $employee?->mobile) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="joining_date">Joining Date</label>
                                <input type="date" id="joining_date" name="joining_date" class="form-control" value="{{ old('joining_date', $employee?->joining_date?->toDateString()) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="status">Status <span class="required">*</span></label>
                                <select id="status" name="status" class="form-select" required>
                                    <option value="active" @selected(old('status', $employee?->status ?? 'active') === 'active')>Active</option>
                                    <option value="inactive" @selected(old('status', $employee?->status) === 'inactive')>Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="remarks">Remarks</label>
                                <textarea id="remarks" name="remarks" class="form-control" rows="2">{{ old('remarks', $employee?->remarks) }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $employee ? 'Save Changes' : 'Create Employee' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-xl-5">
            <div class="card">
                <div class="card-header">Skill Mapping</div>
                <div class="card-body">
                    @if(!$employee)
                        <p class="small text-muted mb-0">Save the employee first, then map skills from the employee page.</p>
                    @else
                        <form method="POST" action="{{ route('employees.skills', $employee) }}">
                            @csrf @method('PUT')
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-2">
                                    <thead><tr><th>Skill</th><th>Level</th><th class="text-center">Mapped</th></tr></thead>
                                    <tbody>
                                    @foreach($allSkills as $skill)
                                        @php($existing = $employee->skills->firstWhere('id', $skill->id))
                                        <tr>
                                            <td class="small">{{ $skill->name }}</td>
                                            <td>
                                                <select name="skills[{{ $loop->index }}][skill_level]" class="form-select form-select-sm" data-level-for="{{ $loop->index }}">
                                                    @foreach(['Beginner', 'Intermediate', 'Advanced'] as $lvl)
                                                        <option value="{{ $lvl }}" @selected($existing?->pivot?->skill_level === $lvl)>{{ $lvl }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="skills[{{ $loop->index }}][skill_id]" value="{{ $skill->id }}">
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input" name="skills[{{ $loop->index }}][skill_id]"
                                                       value="{{ $skill->id }}" {{ $existing ? 'checked' : '' }}>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-save me-1"></i>Save Skills</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
