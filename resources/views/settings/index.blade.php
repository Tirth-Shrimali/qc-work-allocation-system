@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <div class="page-header">
        <div>
            <h1>System Settings</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Settings</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('settings.active-users') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-people me-1"></i>Active Users
        </a>
    </div>

    {{-- Real, database-backed summary widgets --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-primary"><i class="bi bi-people"></i></span>
                <div class="kpi-body">
                    <div class="kpi-value">{{ $activeUsers }}{{ $maxUsers > 0 ? ' / '.$maxUsers : '' }}</div>
                    <div class="kpi-label">Active Users{{ $maxUsers <= 0 ? ' (unlimited)' : '' }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-info"><i class="bi bi-kanban"></i></span>
                <div class="kpi-body">
                    <div class="kpi-value">{{ $activeProjects }}{{ $maxProjects > 0 ? ' / '.$maxProjects : '' }}</div>
                    <div class="kpi-label">Active Projects{{ $maxProjects <= 0 ? ' (unlimited)' : '' }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-warning"><i class="bi bi-patch-check"></i></span>
                <div class="kpi-body">
                    <div class="kpi-value">
                        <span class="badge text-bg-{{ ['active' => 'success', 'expiring' => 'warning', 'grace' => 'warning', 'expired' => 'danger', 'suspended' => 'danger'][$licenseState] ?? 'secondary' }}">
                            {{ $licenseStateLabel }}
                        </span>
                    </div>
                    <div class="kpi-label">{{ $remainingDays !== null ? ($remainingDays >= 0 ? $remainingDays.' days remaining' : abs($remainingDays).' days overdue') : 'No expiry configured' }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="kpi-card">
                <span class="kpi-icon bg-soft-success"><i class="bi bi-person-plus"></i></span>
                <div class="kpi-body">
                    <div class="kpi-value">{{ \App\Support\AppSettings::allowRegistration() ? 'Enabled' : 'Disabled' }}</div>
                    <div class="kpi-label">Registration {{ \App\Support\AppSettings::requireApproval() ? '(approval required)' : '(auto-active)' }}</div>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        @foreach ([
            'overview' => ['Overview', 'bi-eye'],
            'users' => ['User Settings', 'bi-person-plus'],
            'session' => ['Session Policy', 'bi-clock-history'],
            'usage' => ['Usage Limits', 'bi-speedometer2'],
            'license' => ['Application Validity', 'bi-patch-check'],
        ] as $key => [$label, $icon])
            <li class="nav-item">
                <a class="nav-link {{ $section === $key ? 'active' : '' }}" href="{{ $key === 'overview' ? route('settings.index') : route('settings.section', $key) }}">
                    <i class="bi {{ $icon }} me-1"></i>{{ $label }}
                </a>
            </li>
        @endforeach
    </ul>

    {{-- ================================ OVERVIEW ================================ --}}
    @if ($section === 'overview')
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">Enterprise Controls</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-6 small">Allow registration</dt>
                            <dd class="col-sm-6">
                                <span class="badge text-bg-{{ \App\Support\AppSettings::allowRegistration() ? 'success' : 'secondary' }}">
                                    {{ \App\Support\AppSettings::allowRegistration() ? 'Enabled' : 'Disabled' }}
                                </span>
                            </dd>
                            <dt class="col-sm-6 small">Require admin approval</dt>
                            <dd class="col-sm-6">
                                <span class="badge text-bg-{{ \App\Support\AppSettings::requireApproval() ? 'warning text-dark' : 'secondary' }}">
                                    {{ \App\Support\AppSettings::requireApproval() ? 'Yes' : 'No' }}
                                </span>
                            </dd>
                            <dt class="col-sm-6 small">Default new-user role</dt>
                            <dd class="col-sm-6">{{ \App\Support\AppSettings::defaultRoleCode() }}</dd>
                            <dt class="col-sm-6 small">Inactivity default / max</dt>
                            <dd class="col-sm-6">
                                {{ \App\Support\AppSettings::minutesLabel(\App\Support\AppSettings::sessionDefault()) }}
                                / {{ \App\Support\AppSettings::minutesLabel(\App\Support\AppSettings::sessionMax()) }}
                            </dd>
                            <dt class="col-sm-6 small">Max simultaneous users</dt>
                            <dd class="col-sm-6">{{ $maxUsers > 0 ? $maxUsers : 'Unlimited' }} ({{ $activeUsers }} active now)</dd>
                            <dt class="col-sm-6 small">Max active projects</dt>
                            <dd class="col-sm-6">{{ $maxProjects > 0 ? $maxProjects : 'Unlimited' }} ({{ $activeProjects }} active now)</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">Quick Actions</div>
                    <div class="card-body d-grid gap-2">
                        <a href="{{ route('settings.active-users') }}" class="btn btn-outline-primary text-start">
                            <i class="bi bi-people me-2"></i>View active users &amp; sessions
                        </a>
                        <a href="{{ route('settings.section', 'license') }}" class="btn btn-outline-warning text-start">
                            <i class="bi bi-patch-check me-2"></i>Manage application validity
                        </a>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary text-start">
                            <i class="bi bi-person-gear me-2"></i>Approve / manage user accounts
                        </a>
                        <a href="{{ route('masters.index', 'settings') }}" class="btn btn-outline-secondary text-start">
                            <i class="bi bi-sliders me-2"></i>Advanced: raw key/value settings
                        </a>
                    </div>
                </div>
            </div>
        </div>

    {{-- ================================ USERS ================================ --}}
    @elseif ($section === 'users')
        <div class="row">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">Registration Settings</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('settings.update', 'users') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Allow New User Registration</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="allow_registration" id="regOn" value="1"
                                           @checked(old('allow_registration', \App\Support\AppSettings::allowRegistration() ? '1' : '0') === '1')>
                                    <label class="btn btn-outline-success" for="regOn"><i class="bi bi-check-circle me-1"></i>Enabled</label>

                                    <input type="radio" class="btn-check" name="allow_registration" id="regOff" value="0"
                                           @checked(old('allow_registration', \App\Support\AppSettings::allowRegistration() ? '1' : '0') === '0')>
                                    <label class="btn btn-outline-danger" for="regOff"><i class="bi bi-x-circle me-1"></i>Disabled</label>
                                </div>
                                <div class="form-text">When disabled, the registration page and link are hidden and POST requests are rejected.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Require Admin Approval</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="require_approval" id="apYes" value="1"
                                           @checked(old('require_approval', \App\Support\AppSettings::requireApproval() ? '1' : '0') === '1')>
                                    <label class="btn btn-outline-primary" for="apYes"><i class="bi bi-person-check me-1"></i>Yes — accounts start as Pending</label>

                                    <input type="radio" class="btn-check" name="require_approval" id="apNo" value="0"
                                           @checked(old('require_approval', \App\Support\AppSettings::requireApproval() ? '1' : '0') === '0')>
                                    <label class="btn btn-outline-primary" for="apNo"><i class="bi bi-person me-1"></i>No — active immediately</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="default_role">Default New-User Role</label>
                                <select class="form-select" id="default_role" name="default_role">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->code }}"
                                            @selected(old('default_role', \App\Support\AppSettings::defaultRoleCode()) === $role->code)>
                                            {{ $role->name }} ({{ $role->code }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Registrants can never choose their own role.</div>
                            </div>

                            <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save Registration Settings</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">How approval works</div>
                    <div class="card-body small">
                        <ol class="mb-0 ps-3">
                            <li class="mb-2">A visitor opens <code>/register</code> and submits the form.</li>
                            <li class="mb-2">With approval on, the account is created as <span class="badge text-bg-warning text-dark">Pending</span> and cannot sign in.</li>
                            <li class="mb-2">Admins see the pending user under <a href="{{ route('users.index') }}">User Management</a> and approve or reject.</li>
                            <li class="mb-0">Approving activates the account and notifies the user.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

    {{-- ================================ SESSION ================================ --}}
    @elseif ($section === 'session')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">Inactivity Session Policy</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('settings.update', 'session') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Available Options</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @php
                                        $currentOptions = collect(explode(',', old('options', \App\Support\AppSettings::get('session.options', '30,60,120,240,480'))));
                                    @endphp
                                    @foreach (\App\Http\Controllers\SettingsController::SESSION_PRESETS as $preset)
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="options[]" value="{{ $preset }}"
                                                   id="opt{{ $preset }}" @checked($currentOptions->contains((string) $preset))>
                                            <label class="form-check-label" for="opt{{ $preset }}">{{ \App\Support\AppSettings::minutesLabel($preset) }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="form-text">Durations users can pick on the login screen ("Stay active for").</div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="session_default">Default Duration</label>
                                    <select class="form-select" id="session_default" name="default">
                                        @foreach (\App\Http\Controllers\SettingsController::SESSION_PRESETS as $preset)
                                            <option value="{{ $preset }}" @selected((int) old('default', \App\Support\AppSettings::sessionDefault()) === $preset)>
                                                {{ \App\Support\AppSettings::minutesLabel($preset) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="session_max">Maximum Allowed</label>
                                    <select class="form-select" id="session_max" name="max">
                                        @foreach (\App\Http\Controllers\SettingsController::SESSION_PRESETS as $preset)
                                            <option value="{{ $preset }}" @selected((int) old('max', \App\Support\AppSettings::sessionMax()) === $preset)>
                                                {{ \App\Support\AppSettings::minutesLabel($preset) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="alert alert-secondary small py-2">
                                <i class="bi bi-shield-lock me-1"></i>
                                Enforced server-side: no user can exceed the maximum, and sessions expire
                                after the chosen inactivity period regardless of browser settings.
                            </div>

                            <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save Session Policy</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">Current Policy</div>
                    <div class="card-body small">
                        <p class="mb-1"><strong>Options:</strong>
                            {{ collect(explode(',', \App\Support\AppSettings::get('session.options', '30,60,120,240,480')))->map(fn ($m) => \App\Support\AppSettings::minutesLabel((int) $m))->implode(', ') }}
                        </p>
                        <p class="mb-1"><strong>Default:</strong> {{ \App\Support\AppSettings::minutesLabel(\App\Support\AppSettings::sessionDefault()) }}</p>
                        <p class="mb-0"><strong>Maximum:</strong> {{ \App\Support\AppSettings::minutesLabel(\App\Support\AppSettings::sessionMax()) }}</p>
                    </div>
                </div>
            </div>
        </div>

    {{-- ================================ USAGE ================================ --}}
    @elseif ($section === 'usage')
        <div class="row">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">Usage Limits</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('settings.update', 'usage') }}">
                            @csrf
                            <div class="mb-4">
                                <label class="form-label">Maximum Simultaneous Users</label>
                                <div class="input-group mb-2" style="max-width: 320px;">
                                    <input type="number" class="form-control" id="max_users_input" name="max_active_users"
                                           min="0" max="100000" value="{{ old('max_active_users', $maxUsers) }}" required>
                                    <span class="input-group-text">users</span>
                                </div>
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    @foreach (\App\Http\Controllers\SettingsController::USER_LIMIT_PRESETS as $preset)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('max_users_input').value = {{ $preset }}">{{ $preset }}</button>
                                    @endforeach
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('max_users_input').value = 0">Custom / 0</button>
                                </div>
                                <div class="form-text">0 = unlimited. Active = users with a live session; logins beyond the limit are blocked with a friendly message.</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Maximum Active Projects (Work Requests)</label>
                                <div class="input-group mb-2" style="max-width: 320px;">
                                    <input type="number" class="form-control" id="max_projects_input" name="max_active_projects"
                                           min="0" max="100000" value="{{ old('max_active_projects', $maxProjects) }}" required>
                                    <span class="input-group-text">projects</span>
                                </div>
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    @foreach (\App\Http\Controllers\SettingsController::PROJECT_LIMIT_PRESETS as $preset)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('max_projects_input').value = {{ $preset }}">{{ $preset }}</button>
                                    @endforeach
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('max_projects_input').value = 0">Custom / 0</button>
                                </div>
                                <div class="form-text">0 = unlimited. Active = work orders not completed/cancelled. Existing orders keep working.</div>
                            </div>

                            <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save Usage Limits</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">Live Utilisation</div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>Active users:</strong>
                            {{ $activeUsers }} / {{ $maxUsers > 0 ? $maxUsers : '∞' }}
                            ({{ $maxUsers > 0 ? round($activeUsers / max(1, $maxUsers) * 100) : 0 }}% usage)
                        </p>
                            <div class="progress mb-3" role="progressbar">
                                @php
                                    $userPct = $maxUsers > 0 ? min(100, round($activeUsers / max(1, $maxUsers) * 100)) : 0;
                                @endphp
                                <div class="progress-bar {{ $userPct >= 100 ? 'bg-danger' : ($userPct >= 70 ? 'bg-warning' : 'bg-success') }}"
                                     style="width: {{ $userPct }}%">{{ $userPct }}%</div>
                            </div>
                        <p class="mb-2">
                            <strong>Active projects:</strong>
                            {{ $activeProjects }} / {{ $maxProjects > 0 ? $maxProjects : '∞' }}
                        </p>
                        <div class="progress" role="progressbar">
                            @php
                                $projPct = $maxProjects > 0 ? min(100, round($activeProjects / max(1, $maxProjects) * 100)) : 0;
                            @endphp
                            <div class="progress-bar {{ $projPct >= 100 ? 'bg-danger' : ($projPct >= 70 ? 'bg-warning' : 'bg-info') }}"
                                 style="width: {{ $projPct }}%">{{ $projPct }}%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    {{-- ================================ LICENSE ================================ --}}
    @elseif ($section === 'license')
        @php
            $stateBadge = ['active' => 'success', 'expiring' => 'warning', 'grace' => 'warning', 'expired' => 'danger', 'suspended' => 'danger', 'unconfigured' => 'secondary'][$licenseState] ?? 'secondary';
        @endphp
        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Application Status</span>
                        <span class="badge text-bg-{{ $stateBadge }}">{{ $licenseStateLabel }}</span>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5 small">Activated</dt>
                            <dd class="col-sm-7">{{ $activation?->format('d M Y') ?? 'Not configured' }}</dd>
                            <dt class="col-sm-5 small">Expires</dt>
                            <dd class="col-sm-7">{{ $expiry?->format('d M Y') ?? 'No expiry configured' }}</dd>
                            <dt class="col-sm-5 small">Remaining</dt>
                            <dd class="col-sm-7">
                                @if ($remainingDays !== null)
                                    {{ $remainingDays >= 0 ? $remainingDays.' day(s)' : abs($remainingDays).' day(s) ago' }}
                                @else
                                    —
                                @endif
                            </dd>
                            <dt class="col-sm-5 small">Grace period</dt>
                            <dd class="col-sm-7">{{ \App\Support\AppSettings::graceDays() }} day(s)</dd>
                            <dt class="col-sm-5 small">Expiry behaviour</dt>
                            <dd class="col-sm-7">
                                {{ ['block' => 'Block Application', 'readonly' => 'Read-Only Mode', 'restrict_login' => 'Restrict Login'][\App\Support\AppSettings::licenseBehavior()] ?? 'Block Application' }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">Extend / Renew Validity</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('settings.license.extend') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Extension Mode</label>
                                <select class="form-select" name="mode" id="extendMode" onchange="toggleExtendMode()">
                                    <option value="days" @selected(old('mode') === 'days')>Extend by days</option>
                                    <option value="date" @selected(old('mode') === 'date')>Set a new expiry date</option>
                                </select>
                            </div>
                            <div class="mb-3" id="daysGroup">
                                <label class="form-label">Extend By</label>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ([30, 60, 90, 180, 365] as $d)
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('daysInput').value = {{ $d }}">+{{ $d }} days</button>
                                    @endforeach
                                </div>
                                <input type="number" class="form-control mt-2" name="days" id="daysInput" min="1" max="3650"
                                       placeholder="e.g. 90" value="{{ old('days') }}">
                            </div>
                            <div class="mb-3 d-none" id="dateGroup">
                                <label class="form-label" for="new_date">New Expiry Date</label>
                                <input type="date" class="form-control" name="new_date" id="new_date" value="{{ old('new_date') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="reason">Reason / Notes</label>
                                <textarea class="form-control" name="reason" id="reason" rows="2" required
                                          placeholder="e.g. Company requested project extension">{{ old('reason') }}</textarea>
                            </div>
                            <button class="btn btn-primary" type="submit"><i class="bi bi-calendar-plus me-1"></i>Apply Extension</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">Validity Configuration</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('settings.update', 'license') }}">
                            @csrf
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="activation_date">Activation Date</label>
                                    <input type="date" class="form-control" id="activation_date" name="activation_date"
                                           value="{{ old('activation_date', $activation?->toDateString()) }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="expiry_date">Expiry Date</label>
                                    <input type="date" class="form-control" id="expiry_date" name="expiry_date"
                                           value="{{ old('expiry_date', $expiry?->toDateString()) }}">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="grace_days">Grace Period (days)</label>
                                    <input type="number" class="form-control" id="grace_days" name="grace_days" min="0" max="365"
                                           value="{{ old('grace_days', \App\Support\AppSettings::graceDays()) }}">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">When License Expires</label>
                                    <select class="form-select" name="behavior">
                                        @foreach (['block' => 'Block Application', 'readonly' => 'Read-Only Mode', 'restrict_login' => 'Restrict Login'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('behavior', \App\Support\AppSettings::licenseBehavior()) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Warning Thresholds (days before expiry)</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @php
                                        $currentThresholds = collect(explode(',', old('warn_thresholds', \App\Support\AppSettings::get('license.warn_thresholds', '30,15,7,3,1'))));
                                    @endphp
                                    @foreach (\App\Http\Controllers\SettingsController::WARN_PRESETS as $threshold)
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="warn_thresholds[]" value="{{ $threshold }}"
                                                   id="warn{{ $threshold }}" @checked($currentThresholds->contains((string) $threshold))>
                                            <label class="form-check-label" for="warn{{ $threshold }}">{{ $threshold }} days</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Save Validity Settings</button>
                        </form>

                        <hr>

                        <form method="POST" action="{{ route('settings.license.toggle') }}"
                              data-confirm="{{ \App\Support\AppSettings::suspended() ? 'Reactivate the application?' : 'Suspend the application? Non-admin users will be blocked immediately.' }}">
                            @csrf
                            <button class="btn btn-sm {{ \App\Support\AppSettings::suspended() ? 'btn-success' : 'btn-outline-danger' }}" type="submit">
                                <i class="bi {{ \App\Support\AppSettings::suspended() ? 'bi-play-circle' : 'bi-pause-circle' }} me-1"></i>
                                {{ \App\Support\AppSettings::suspended() ? 'Reactivate Application' : 'Suspend Application' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">License History</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead><tr><th>When</th><th>Action</th><th>Expiry</th><th>By / Reason</th></tr></thead>
                                <tbody>
                                @forelse ($history as $entry)
                                    <tr>
                                        <td class="small text-muted">{{ $entry->created_at?->format('d M Y H:i') }}</td>
                                        <td><span class="badge text-bg-light text-capitalize">{{ $entry->action }}</span></td>
                                        <td class="small">
                                            {{ $entry->previous_expiry?->format('d M Y') ?? '—' }}
                                            <i class="bi bi-arrow-right mx-1"></i>
                                            <strong>{{ $entry->new_expiry?->format('d M Y') ?? '—' }}</strong>
                                        </td>
                                        <td class="small">
                                            {{ $entry->user?->name ?? 'System' }}
                                            @if ($entry->reason)<div class="text-muted">{{ \Illuminate\Support\Str::limit($entry->reason, 60) }}</div>@endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted small py-3">No licence changes recorded yet.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        function toggleExtendMode() {
            var mode = document.getElementById('extendMode').value;
            document.getElementById('daysGroup').classList.toggle('d-none', mode !== 'days');
            document.getElementById('dateGroup').classList.toggle('d-none', mode !== 'date');
        }
        document.addEventListener('DOMContentLoaded', toggleExtendMode);
    </script>
@endpush
