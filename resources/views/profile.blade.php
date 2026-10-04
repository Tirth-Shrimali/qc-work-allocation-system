@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    <div class="page-header">
        <div>
            <h1>My Profile</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Profile</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Account Details</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label class="form-label" for="name">Full Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row small text-muted mb-3">
                            <div class="col-6">Username: <strong>{{ $user->username }}</strong></div>
                            <div class="col-6">Role: <strong>{{ $user->primaryRole()?->name ?? '—' }}</strong></div>
                            <div class="col-6 mt-1">Employee: <strong>{{ $user->employee?->employee_code ?? '—' }}</strong></div>
                            <div class="col-6 mt-1">Last login: <strong>{{ $user->last_login_at?->format('d M Y H:i') ?? '—' }}</strong></div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Profile</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Change Password</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label class="form-label" for="current_password">Current Password <span class="required">*</span></label>
                            <input type="password" id="current_password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">New Password <span class="required">*</span></label>
                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Minimum 8 characters.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password_confirmation">Confirm New Password <span class="required">*</span></label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-key me-1"></i>Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
