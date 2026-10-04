<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Validity Expired — {{ config('app.name', 'QC Work Allocation') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="auth-page">
    <div class="auth-card text-center">
        <div class="auth-brand justify-content-center">
            <span class="brand-icon"><i class="bi bi-shield-lock"></i></span>
            <div>
                <strong>PharmaQC</strong>
                <small>QC Work Allocation System</small>
            </div>
        </div>

        @if ($state === 'suspended')
            <h1>Application Suspended</h1>
            <p class="subtitle">
                This application has been suspended by the administrator.
                Please contact your system administrator for assistance.
            </p>
        @else
            <h1>Application Validity Expired</h1>
            <p class="subtitle">
                The licence period for this application has ended.
                Please contact your system administrator to extend the validity.
            </p>
        @endif

        <div class="card mt-3 text-start">
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-6 text-muted">Current status</dt>
                    <dd class="col-6">
                        <span class="badge text-bg-danger">
                            {{ $state === 'suspended' ? 'Suspended' : 'Expired' }}
                        </span>
                    </dd>
                    <dt class="col-6 text-muted">Expired on</dt>
                    <dd class="col-6">{{ $expiry?->format('d M Y') ?? '—' }}</dd>
                    <dt class="col-6 text-muted">Server time</dt>
                    <dd class="col-6">{{ now()->format('d M Y H:i') }}</dd>
                </dl>
            </div>
        </div>

        <div class="text-center small text-muted mt-3">
            Need help? Contact your system administrator.
        </div>
    </div>
</div>
</body>
</html>
