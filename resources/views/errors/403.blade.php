<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forbidden — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="auth-page">
    <div class="auth-card text-center">
        <span class="brand-icon mx-auto mb-3" style="width:56px;height:56px;font-size:1.5rem"><i class="bi bi-shield-lock"></i></span>
        <h1>403 — Access Denied</h1>
        <p class="subtitle">You do not have permission to access this page. If you believe this is a mistake, contact your administrator.</p>
        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-primary w-100">Back to Dashboard</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary w-100">Go to Login</a>
        @endauth
    </div>
</div>
</body>
</html>
