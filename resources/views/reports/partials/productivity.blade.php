@if(($rowsData ?? collect())->isNotEmpty())
    <div class="card-body border-bottom">
        <div class="chart-box"><canvas id="prodChart"></canvas></div>
    </div>
@endif
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
        <tr>@foreach($data['headers'] as $h)<th>{{ $h }}</th>@endforeach</tr>
        </thead>
        <tbody>
        @forelse($data['rows'] as $row)
            <tr>@foreach($row as $cell)<td class="small">{{ $cell }}</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($data['headers']) }}">@include('partials.empty', ['icon' => 'bi-graph-up', 'title' => 'No productivity data.', 'message' => 'No results were entered in this period.'])</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('prodChart');
        if (!el) return;
        var rows = @json($rowsData ?? collect());
        new Chart(el, {
            type: 'bar',
            data: {
                labels: rows.map(function (r) { return r.name; }),
                datasets: [
                    { label: 'Assigned', data: rows.map(function (r) { return r.assigned; }), backgroundColor: '#93b4d8', borderRadius: 4 },
                    { label: 'Completed', data: rows.map(function (r) { return r.completed; }), backgroundColor: '#1d4e89', borderRadius: 4 },
                    { label: 'Results entered', data: rows.map(function (r) { return r.results; }), backgroundColor: '#16a34a', borderRadius: 4 }
                ]
            },
            options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });
    });
</script>
@endpush
