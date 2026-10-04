@extends('layouts.app')

@section('title', 'User Management')

@section('content')
    <div class="page-header">
        <div>
            <h1>User Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Users</li>
                </ol>
            </nav>
        </div>
        @permission('users.create')
            <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Add User</a>
        @endpermission
    </div>

    <form class="filter-bar" method="GET" action="{{ route('users.index') }}">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="q">Search</label>
                <input type="search" id="q" name="q" class="form-control" placeholder="Name / username / email" value="{{ request('q') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="role">Role</label>
                <select id="role" name="role" class="form-select">
                    <option value="">All roles</option>
                    @foreach($roles as $r)
                        <option value="{{ $r->code }}" @selected(request('role') === $r->code)>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Search</button></div>
            <div class="col-md-3 text-md-end"><span class="small text-muted">{{ $users->total() }} user(s)</span></div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>User</th><th>Username</th><th>Role</th><th>Employee</th><th>Status</th><th>Last Login</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @forelse($users as $u)
                    <tr>
                        <td>
                            <span class="avatar avatar-sm me-2">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                            <strong class="small">{{ $u->name }}</strong>
                            <div class="small text-muted ms-4">{{ $u->email }}</div>
                        </td>
                        <td class="small">{{ $u->username }}</td>
                        <td><span class="badge text-bg-primary">{{ $u->primaryRole()?->name ?? '—' }}</span></td>
                        <td class="small text-muted">{{ $u->employee?->employee_code ?? '—' }}</td>
                        <td><span class="badge text-bg-{{ $u->status === 'active' ? 'success' : ($u->status === 'pending' ? 'warning text-dark' : 'secondary') }}">{{ ucfirst($u->status) }}</span></td>
                        <td class="small text-muted">{{ $u->last_login_at?->format('d M Y H:i') ?? 'Never' }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('users.edit', $u) }}" class="btn btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                @permission('users.edit')
                                    @if($u->status === 'pending')
                                        <form action="{{ route('users.approve', $u) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-outline-success" type="submit" title="Approve">
                                                <i class="bi bi-check2-circle"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('users.reject', $u) }}" method="POST" class="d-inline"
                                              data-confirm="Reject this registration? They will not be able to sign in.">
                                            @csrf
                                            <button class="btn btn-outline-danger" type="submit" title="Reject">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    @elseif($u->id !== auth()->id())
                                        <form action="{{ route('users.toggle', $u) }}" method="POST"
                                              data-confirm="{{ $u->status === 'active' ? 'Deactivate this user?' : 'Reactivate this user?' }}">
                                            @csrf
                                            <button class="btn btn-outline-{{ $u->status === 'active' ? 'danger' : 'success' }}" type="submit"
                                                    title="{{ $u->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                                <i class="bi {{ $u->status === 'active' ? 'bi-person-x' : 'bi-person-check' }}"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endpermission
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">@include('partials.empty', ['icon' => 'bi-person-gear', 'title' => 'No users found.', 'message' => 'Create user accounts with roles.'])</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3">{{ $users->links() }}</div>
@endsection
