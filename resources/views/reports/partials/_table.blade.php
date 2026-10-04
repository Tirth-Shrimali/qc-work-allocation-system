<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
        <tr>
            @foreach($data['headers'] as $h)
                <th>{{ $h }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @forelse($data['rows'] as $row)
            <tr>
                @foreach($row as $cell)
                    <td class="small">{{ $cell }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($data['headers']) }}">@include('partials.empty', ['icon' => 'bi-bar-chart', 'title' => 'No data for this period.', 'message' => 'Adjust the date range and try again.'])</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
