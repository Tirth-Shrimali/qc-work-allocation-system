{{-- Usage: @include('partials.empty', ['icon' => 'bi-inbox', 'title' => 'No work found.', 'message' => '...', 'cta' => ['label'=>'Create Work','url'=>route('work-orders.create')]]) --}}
<div class="empty-state">
    <i class="bi {{ $icon ?? 'bi-inbox' }}"></i>
    <h6>{{ $title ?? 'No records found.' }}</h6>
    <p>{{ $message ?? 'Nothing matches the current filters.' }}</p>
    @if (!empty($cta))
        <a href="{{ $cta['url'] }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>{{ $cta['label'] }}
        </a>
    @endif
</div>
