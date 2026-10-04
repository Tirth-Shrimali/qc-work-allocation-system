<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — {{ config('app.name', 'QC Work Allocation') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-brand">
            <span class="brand-icon"><i class="bi bi-shield-check"></i></span>
            <div>
                <strong>PharmaQC</strong>
                <small>QC Work Allocation System</small>
            </div>
        </div>

        <h1>Welcome back</h1>
        <p class="subtitle">Sign in to manage samples, allocations and reviews.</p>

        @if (session('status'))
            <div class="alert alert-warning py-2 small mb-3" role="alert">
                <i class="bi bi-clock-history me-1"></i>{{ session('status') }}
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success py-2 small mb-3" role="alert">
                <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger py-2 small mb-3" role="alert">
                <i class="bi bi-exclamation-triangle me-1"></i>{{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control @error('username') is-invalid @enderror"
                           id="username" name="username" value="{{ old('username') }}"
                           placeholder="e.g. admin" required autofocus autocomplete="username">
                    @error('username')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Your password" required autocomplete="current-password">
                </div>
            </div>

            <div class="mb-3">
                <label for="session_timeout" class="form-label small mb-1">Stay active for</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                    <select class="form-select" id="session_timeout" name="session_timeout">
                        @foreach ($timeoutChoices as [$value, $label, $isDefault])
                            <option value="{{ $value }}" @selected((int) old('session_timeout', $isDefault ? $value : 0) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-text small">You will be signed out after this period of inactivity.</div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-3">
                <label class="d-flex align-items-center gap-2 small mb-0">
                    <input type="checkbox" name="remember" value="1" class="form-check-input mt-0">
                    Remember me
                </label>
                <span class="small text-muted">QC Department Access</span>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-1"></i>Sign in
            </button>
        </form>

        @if ($allowRegistration ?? false)
            <div class="text-center small text-muted mt-3">
                New here? <a href="{{ route('register') }}">Create an account</a>
            </div>
        @endif

        <div class="demo-credentials">
            <strong>Demo accounts</strong> (password: <code>password</code>)<br>
            <code>admin</code> Super Admin · <code>qc_manager</code> QC Admin · <code>hod_qc</code> HOD ·
            <code>supervisor_qc</code> Supervisor · <code>analyst_raj</code> Analyst · <code>reviewer_qc</code> Reviewer ·
            <code>management</code> Management
        </div>
    </div>
</div>
</body>
</html>
