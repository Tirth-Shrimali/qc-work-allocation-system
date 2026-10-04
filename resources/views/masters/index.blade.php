@extends('layouts.app')

@section('title', $config['label'])

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ $config['label'] }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">{{ $config['label'] }}</li>
                </ol>
            </nav>
        </div>
        @permission('masters.manage')
            <a href="{{ route('masters.create', $master) }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Add {{ $config['singular'] }}
            </a>
        @endpermission
    </div>

    <form class="filter-bar" method="GET" action="{{ route('masters.index', $master) }}">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="q">Search</label>
                <input type="search" id="q" name="q" class="form-control" placeholder="Search {{ $config['label'] }}…" value="{{ request('q') }}">
            </div>
            @if(isset($config['columns']['status']))
                <div class="col-md-2">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
            @endif
            <div class="col-md-2 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Search</button></div>
            <div class="col-md-4 text-md-end"><span class="small text-muted">{{ $items->total() }} record(s)</span></div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    @foreach($config['table'] as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                    <th class="text-end" style="width:110px">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        @foreach($config['table'] as $column => $label)
                            <td>
                                @if($column === 'status')
                                    <span class="badge text-bg-{{ $item->{$column} === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($item->{$column}) }}</span>
                                @elseif($column === 'name' || str_contains($column, 'code') || $column === 'key')
                                    <span class="fw-semibold">{{ $item->{$column} ?? '—' }}</span>
                                @else
                                    <span class="small">{{ \Illuminate\Support\Str::limit((string) ($item->{$column} ?? '—'), 60) }}</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="text-end">
                            @permission('masters.manage')
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('masters.edit', [$master, $item->id]) }}" class="btn btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('masters.destroy', [$master, $item->id]) }}" method="POST"
                                          data-confirm="Delete this {{ strtolower($config['singular']) }}? This cannot be undone.">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger" type="submit" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            @endpermission
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($config['table']) + 1 }}">
                            @include('partials.empty', [
                                'icon' => 'bi-inbox',
                                'title' => 'No ' . strtolower($config['label']) . ' found.',
                                'message' => 'Create the first record or change the filters.',
                                'cta' => request()->user()->hasPermission('masters.manage') ? ['label' => 'Add '.$config['singular'], 'url' => route('masters.create', $master)] : null,
                            ])
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3">{{ $items->links() }}</div>
@endsection
