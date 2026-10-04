@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="page-header">
        <div>
            <h1>Notifications</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Notifications</li>
                </ol>
            </nav>
        </div>
        @if($unread > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="btn btn-outline-primary btn-sm" type="submit"><i class="bi bi-check-all me-1"></i>Mark all read</button>
            </form>
        @endif
    </div>

    <div class="tab-pills">
        <a href="{{ route('notifications.index') }}" class="{{ !request('filter') ? 'active' : '' }}">All</a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="{{ request('filter') === 'unread' ? 'active' : '' }}">
            Unread <span class="count-pill {{ request('filter') === 'unread' ? 'active' : '' }}">{{ $unread }}</span>
        </a>
    </div>

    <div class="card">
        <div class="list-group list-group-flush">
            @forelse($notifications as $n)
                <div class="list-group-item d-flex justify-content-between align-items-start gap-3 {{ $n->is_read ? '' : 'bg-light' }}">
                    <div class="me-auto">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-circle-fill text-primary" style="font-size:.5rem" {{ $n->is_read ? 'style=opacity:0' : '' }}></i>
                            <strong class="small">{{ $n->title }}</strong>
                            @if(!$n->is_read)<span class="badge text-bg-primary">New</span>@endif
                        </div>
                        <p class="small text-muted mb-0">{{ $n->message }}</p>
                        <small class="text-muted" style="font-size:.72rem">{{ $n->type }} · {{ $n->created_at->format('d M Y H:i') }}</small>
                    </div>
                    <div class="d-flex gap-1">
                        @if(!$n->is_read)
                            <form method="POST" action="{{ route('notifications.read', $n) }}">
                                @csrf
                                <button class="btn btn-outline-secondary btn-sm" type="submit" title="Mark read"><i class="bi bi-check"></i></button>
                            </form>
                        @endif
                        @if($n->link)
                            <a href="{{ route('notifications.read', $n) }}" class="btn btn-primary btn-sm">View</a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="list-group-item">
                    @include('partials.empty', ['icon' => 'bi-bell-slash', 'title' => 'No notifications.', 'message' => 'You are all caught up.'])
                </div>
            @endforelse
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3">{{ $notifications->links() }}</div>
@endsection
