{{-- Usage: @include('partials.status-badge', ['status' => $order->status]) --}}
@php
    $map = [
        'NEW' => ['New', 'info'],
        'ALLOCATED' => ['Allocated', 'primary'],
        'ACCEPTED' => ['Accepted', 'secondary'],
        'IN_PROGRESS' => ['In Progress', 'warning'],
        'ON_HOLD' => ['On Hold', 'secondary'],
        'SUBMITTED' => ['Submitted', 'info'],
        'UNDER_REVIEW' => ['Under Review', 'primary'],
        'REWORK' => ['Rework', 'danger'],
        'APPROVED' => ['Approved', 'success'],
        'COMPLETED' => ['Completed', 'success'],
        'REJECTED' => ['Rejected', 'danger'],
        'CANCELLED' => ['Cancelled', 'dark'],
        'PENDING' => ['Pending', 'secondary'],
        'IN_REVIEW' => ['In Review', 'primary'],
        'ACTIVE' => ['Active', 'success'],
        'SUPERSEDED' => ['Reassigned', 'secondary'],
    ];
    $entry = $map[$status] ?? [ucfirst(strtolower(str_replace('_', ' ', (string) $status))), 'secondary'];
@endphp
<span class="badge text-bg-{{ $entry[1] }}">{{ $entry[0] }}</span>
