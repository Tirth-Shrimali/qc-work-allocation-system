@extends('layouts.app')

@section('title', ($user ? 'Edit User' : 'New User'))

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $user ? 'Edit User' : 'New User' }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">{{ $user ? $user->username : 'New' }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>

    <div class="row">
        <div class="col-lg-7 col-xl-6">
            <div class="card">
                <div class="card-header">{{ $user ? 'Edit' : 'Create' }} User</div>
                <div class="card-body">
                    <form method="POST" action="{{ $user ? route('users.update', $user) : route('users.store') }}">
                        @csrf
                        @if($user) @method('PUT') @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Full Name <span class="required">*</span></label>
                                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $user?->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="username">Username <span class="required">*</span></label>
                                <input type="text" id="username" name="username" class="form-control @error('username') is-invalid @enderror"
                                       value="{{ old('username', $user?->username) }}" autocomplete="off" required>
                                @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email <span class="required">*</span></label>
                                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $user?->email) }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password">
                                    Password @if(!$user)<span class="required">*</span>@endif
                                </label>
                                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                       autocomplete="new-password" {{ $user ? '' : 'required' }} placeholder="{{ $user ? 'Leave blank to keep current' : 'Min 8 characters' }}>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">Confirm Password</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="role_id">Role <span class="required">*</span></label>
                                <select id="role_id" name="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
                                    <option value="">Select role…</option>
                                    @foreach($roles as $r)
                                        <option value="{{ $r->id }}" @selected(old('role_id', $user?->roles->first()?->id) == $r->id)>{{ $r->name }}</option>
                                    @endforeach
                                </select>
                                @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="employee_id">Linked Employee</label>
                                <select id="employee_id" name="employee_id" class="form-select @error('employee_id') is-invalid @enderror">
                                    <option value="">None (admin account)</option>
                                    @foreach($employees as $e)
                                        <option value="{{ $e->id }}" @selected(old('employee_id', $user?->employee_id) == $e->id)>{{ $e->employee_code }} — {{ $e->name }}</option>
                                    @endforeach
                                </select>
                                @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Analysts must be linked to an employee to receive work.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="status">Status <span class="required">*</span></label>
                                <select id="status" name="status" class="form-select" {{ $user?->id === auth()->id() ? 'disabled' : '' }}>
                                    <option value="active" @selected(old('status', $user?->status ?? 'active') === 'active')>Active</option>
                                    <option value="inactive" @selected(old('status', $user?->status) === 'inactive')>Inactive</option>
                                </select>
                                @if($user?->id === auth()->id())
                                    <div class="form-text">You cannot deactivate your own account.</div>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $user ? 'Save Changes' : 'Create User' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
