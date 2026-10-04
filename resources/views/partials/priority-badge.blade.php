{{-- Usage: @include('partials.priority-badge', ['priority' => $order->priority]) --}}
@php
    $prioMap = [
        'Critical' => 'danger',
        'High' => 'warning',
        'Urgent' => 'danger',
        'Normal' => 'info',
        'Medium' => 'info',
        'Low' => 'secondary',
    ];
    $prioBadge = $prioMap[$priority] ?? 'secondary';
@endphp
<span class="badge rounded-pill text-bg-{{ $prioBadge }}">{{ $priority }}</span>
