<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'QC Work Allocation') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app-shell">
    {{-- Sidebar --}}
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-brand">
            <span class="brand-icon"><i class="bi bi-shield-check"></i></span>
            <span class="brand-text">
                <strong>PharmaQC</strong>
                <small>Work Allocation</small>
            </span>
            <button class="btn btn-sm btn-link sidebar-close d-lg-none" id="sidebarClose" type="button" aria-label="Close sidebar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nav-link-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i><span>Dashboard</span>
            </a>

            <div class="nav-section">QC Work</div>

            @permission('work_orders.view')
                <a href="{{ route('work-orders.index') }}" class="nav-link-custom {{ request()->routeIs('work-orders.*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard2-data"></i><span>Work Requests</span>
                </a>
            @endpermission

            @permission('allocations.view')
                <a href="{{ route('allocation.index') }}" class="nav-link-custom {{ request()->routeIs('allocation.*') ? 'active' : '' }}">
                    <i class="bi bi-person-check"></i><span>Allocation</span>
                </a>
            @endpermission

            @auth
                @if(auth()->user()->employee_id)
                    <a href="{{ route('my-work.index') }}" class="nav-link-custom {{ request()->routeIs('my-work.*') ? 'active' : '' }}">
                        <i class="bi bi-journal-check"></i><span>My Work</span>
                    </a>
                @endif
            @endauth

            @permission('review.view')
                <a href="{{ route('review.index') }}" class="nav-link-custom {{ request()->routeIs('review.*') && !request()->filled('view') || request()->routeIs('review.*') && request('view') !== 'rework' ? 'active' : '' }}">
                    <i class="bi bi-clipboard-check"></i><span>Review</span>
                </a>
                <a href="{{ route('review.index', ['view' => 'rework']) }}" class="nav-link-custom {{ request()->routeIs('review.*') && request('view') === 'rework' ? 'active' : '' }}">
                    <i class="bi bi-arrow-repeat"></i><span>Rework</span>
                </a>
            @endpermission

            <div class="nav-section">Masters</div>

            @permission('employees.manage')
                <a href="{{ route('employees.index') }}" class="nav-link-custom {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i><span>Employees</span>
                </a>
            @endpermission

            @permission('masters.view')
                <a href="{{ route('masters.index', 'departments') }}" class="nav-link-custom {{ request()->fullUrlIs('*masters/departments*') ? 'active' : '' }}">
                    <i class="bi bi-diagram-3"></i><span>Departments</span>
                </a>
                <a href="{{ route('masters.index', 'products') }}" class="nav-link-custom {{ request()->fullUrlIs('*masters/products*') ? 'active' : '' }}">
                    <i class="bi bi-box-seam"></i><span>Products</span>
                </a>
                <a href="{{ route('masters.index', 'materials') }}" class="nav-link-custom {{ request()->fullUrlIs('*masters/materials*') ? 'active' : '' }}">
                    <i class="bi bi-droplet"></i><span>Materials</span>
                </a>
                <a href="{{ route('masters.index', 'sample-types') }}" class="nav-link-custom {{ request()->fullUrlIs('*masters/sample-types*') ? 'active' : '' }}">
                    <i class="bi bi-vial"></i><span>Sample Types</span>
                </a>
                <a href="{{ route('masters.index', 'test-types') }}" class="nav-link-custom {{ request()->fullUrlIs('*masters/test-types*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard2-pulse"></i><span>Test Types</span>
                </a>
                <a href="{{ route('masters.index', 'test-methods') }}" class="nav-link-custom {{ request()->fullUrlIs('*masters/test-methods*') ? 'active' : '' }}">
                    <i class="bi bi-list-check"></i><span>Test Methods</span>
                </a>
                <a href="{{ route('masters.index', 'instruments') }}" class="nav-link-custom {{ request()->fullUrlIs('*masters/instruments*') || request()->fullUrlIs('*instrument-types*') ? 'active' : '' }}">
                    <i class="bi bi-cpu"></i><span>Instruments</span>
                </a>
                <a href="{{ route('masters.index', 'skills') }}" class="nav-link-custom {{ request()->fullUrlIs('*masters/skills*') ? 'active' : '' }}">
                    <i class="bi bi-award"></i><span>Skills</span>
                </a>
            @endpermission

            <div class="nav-section">Insights</div>

            @permission('reports.view')
                <a href="{{ route('reports.index') }}" class="nav-link-custom {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart-line"></i><span>Reports</span>
                </a>
            @endpermission

            @auth
                <a href="{{ route('notifications.index') }}" class="nav-link-custom {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    <i class="bi bi-bell"></i><span>Notifications</span>
                </a>
            @endauth

            @permission('system.audit')
                <a href="{{ route('audit.index') }}" class="nav-link-custom {{ request()->routeIs('audit.*') ? 'active' : '' }}">
                    <i class="bi bi-clock-history"></i><span>Audit Trail</span>
                </a>
            @endpermission

            @permission('users.view')
                <a href="{{ route('users.index') }}" class="nav-link-custom {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="bi bi-person-gear"></i><span>User Management</span>
                </a>
            @endpermission

            @permission('system.settings')
                <a href="{{ route('masters.index', 'settings') }}" class="nav-link-custom {{ request()->fullUrlIs('*masters/settings*') ? 'active' : '' }}">
                    <i class="bi bi-gear"></i><span>Settings</span>
                </a>
            @endpermission
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <div class="sidebar-user-meta">
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ auth()->user()->primaryRole()?->name ?? 'User' }}</small>
                </div>
            </div>
        </div>
    </aside>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    {{-- Main --}}
    <div class="app-main">
        <header class="app-topbar">
            <button class="btn btn-light btn-sm sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>

            <div class="topbar-search d-none d-md-flex">
                <i class="bi bi-search"></i>
                <input type="search" id="globalSearch" placeholder="Search work no, sample id…" aria-label="Quick search">
            </div>

            <div class="topbar-actions">
                <div class="dropdown">
                    <button class="btn btn-light btn-sm position-relative" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        @if(($unreadCount ?? 0) > 0)
                            <span class="badge rounded-pill text-bg-danger badge-dot">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notif-dropdown">
                        <div class="notif-header d-flex justify-content-between align-items-center">
                            <strong>Notifications</strong>
                            @if(($unreadCount ?? 0) > 0)
                                <form action="{{ route('notifications.read-all') }}" method="POST" class="m-0">
                                    @csrf
                                    <button class="btn btn-link btn-sm p-0" type="submit">Mark all read</button>
                                </form>
                            @endif
                        </div>
                        @php
                            $recent = auth()->user()->notificationsUnread()->take(6)->get();
                        @endphp
                        @forelse($recent as $n)
                            <a class="dropdown-item notif-item" href="{{ route('notifications.read', $n) }}">
                                <strong>{{ $n->title }}</strong>
                                <span>{{ $n->message }}</span>
                                <small>{{ $n->created_at->diffForHumans() }}</small>
                            </a>
                        @empty
                            <div class="notif-empty">
                                <i class="bi bi-bell-slash"></i>
                                <p>No unread notifications</p>
                            </div>
                        @endforelse
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-center small" href="{{ route('notifications.index') }}">View all notifications</a>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="btn btn-light btn-sm d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar avatar-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="d-none d-sm-inline small fw-semibold">{{ auth()->user()->name }}</span>
                        <i class="bi bi-chevron-down small"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">{{ auth()->user()->primaryRole()?->name ?? 'Account' }}</h6></li>
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="app-content">
            @include('partials.flash')
            @yield('content')
        </main>

        <footer class="app-footer">
            <span>{{ config('app.name') }} &copy; {{ date('Y') }}</span>
            <span>Pharmaceutical QC Work Allocation System</span>
        </footer>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
