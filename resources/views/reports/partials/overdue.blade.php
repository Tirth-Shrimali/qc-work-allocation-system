<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
        <tr>@foreach($data['headers'] as $h)<th>{{ $h }}</th>@endforeach</tr>
        </thead>
        <tbody>
        @forelse($data['rows'] as $row)
            <tr>
                <td class="small"><a class="table-link" href="{{ route('work-orders.show', $row[0]) }}">{{ $row[0] }}</a></td>
                <td class="small">{{ $row[1] }}</td>
                <td class="small">{{ $row[2] }}</td>
                <td class="small">@include('partials.priority-badge', ['priority' => $row[3]])</td>
                <td class="small text-danger fw-semibold">{{ $row[4] }}</td>
                <td class="small">@include('partials.status-badge', ['status' => $row[5]])</td>
            </tr>
        @empty
            <tr><td colspan="{{ count($data['headers']) }}">@include('partials.empty', ['icon' => 'bi-emoji-smile', 'title' => 'Nothing overdue.', 'message' => 'All work orders are within their due dates.'])</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
