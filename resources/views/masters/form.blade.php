@extends('layouts.app')

@section('title', ($item ? 'Edit ' : 'New ').$config['singular'])

@section('content')
    <div class="page-header">
        <div>
            <h1>{{ ($item ? 'Edit ' : 'New ').$config['singular'] }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('masters.index', $master) }}">{{ $config['label'] }}</a></li>
                    <li class="breadcrumb-item active">{{ $item ? 'Edit' : 'New' }}</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('masters.index', $master) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8 col-xl-6">
            <div class="card">
                <div class="card-header">{{ $item ? 'Edit' : 'Create' }} {{ $config['singular'] }}</div>
                <div class="card-body">
                    <form method="POST"
                          action="{{ $item ? route('masters.update', [$master, $item->id]) : route('masters.store', $master) }}">
                        @csrf
                        @if($item) @method('PUT') @endif

                        <div class="row g-3">
                            @foreach($config['columns'] as $column => [$label, $type, $options, $required])
                                <div class="col-{{ in_array($type, ['textarea'], true) ? 12 : 12 }} col-md-{{ in_array($type, ['textarea'], true) ? 12 : 6 }}">
                                    <label class="form-label" for="{{ $column }}">
                                        {{ $label }}
                                        @if($required)<span class="required">*</span>@endif
                                    </label>

                                    @if($type === 'textarea')
                                        <textarea id="{{ $column }}" name="{{ $column }}" rows="3"
                                                  class="form-control @error($column) is-invalid @enderror"
                                                  @required($required)>{{ old($column, $item?->{$column}) }}</textarea>
                                    @elseif($type === 'fk')
                                        <select id="{{ $column }}" name="{{ $column }}" class="form-select @error($column) is-invalid @enderror" @required($required)>
                                            <option value="">Select…</option>
                                            @foreach(($options ?: collect()) as $opt)
                                                <option value="{{ $opt->id }}" @selected(old($column, $item?->{$column}) == $opt->id)>{{ $opt->name }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($type === 'select')
                                        <select id="{{ $column }}" name="{{ $column }}" class="form-select @error($column) is-invalid @enderror" @required($required)>
                                            @if(!$required)<option value="">Select…</option>@endif
                                            @foreach($options as $opt)
                                                <option value="{{ $opt }}" @selected(old($column, $item?->{$column}) === $opt)>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($type === 'status')
                                        <select id="{{ $column }}" name="{{ $column }}" class="form-select @error($column) is-invalid @enderror" @required($required)>
                                            <option value="active" @selected(old($column, $item?->{$column} ?? 'active') === 'active')>Active</option>
                                            <option value="inactive" @selected(old($column, $item?->{$column}) === 'inactive')>Inactive</option>
                                        </select>
                                    @elseif($type === 'number')
                                        <input type="number" id="{{ $column }}" name="{{ $column }}" class="form-control @error($column) is-invalid @enderror"
                                               value="{{ old($column, $item?->{$column}) }}" @required($required)>
                                    @elseif($type === 'date')
                                        <input type="date" id="{{ $column }}" name="{{ $column }}" class="form-control @error($column) is-invalid @enderror"
                                               value="{{ old($column, $item?->{$column}) }}" @required($required)>
                                    @else
                                        <input type="text" id="{{ $column }}" name="{{ $column }}" class="form-control @error($column) is-invalid @enderror"
                                               value="{{ old($column, $item?->{$column}) }}" @required($required)>
                                    @endif

                                    @error($column)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('masters.index', $master) }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>{{ $item ? 'Save Changes' : 'Create' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
