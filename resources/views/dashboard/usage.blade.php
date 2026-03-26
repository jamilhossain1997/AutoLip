{{-- resources/views/dashboard/usage.blade.php --}}
@extends('layouts.app')
@section('title', 'Usage')
@section('heading', 'Usage')

@section('content')

{{-- Period header --}}
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">
        {{ now()->format('F Y') }} ·
        resets {{ now()->endOfMonth()->format('M d') }}
    </p>
</div>

{{-- Usage meters --}}
<div class="grid grid-cols-3 gap-4 mb-8">
    @foreach([
        ['key' => 'scrape', 'label' => 'Web scraping',     'color' => 'blue',   'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
        ['key' => 'pdf',    'label' => 'PDF generation',   'color' => 'violet', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['key' => 'email',  'label' => 'Email automation', 'color' => 'emerald','icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
    ] as $ep)
    @php
        $u = $usage[$ep['key']];
        $pct = $u['limit'] === PHP_INT_MAX ? 0 : ($u['limit'] > 0 ? min(100, round($u['used'] / $u['limit'] * 100)) : 0);
        $barColor = $pct >= 90 ? 'bg-red-400' : ($pct >= 70 ? 'bg-amber-400' : 'bg-'.$ep['color'].'-400');
    @endphp
    <div class="bg-white border border-gray-100 rounded-xl p-5">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 rounded-lg bg-{{ $ep['color'] }}-50 flex items-center justify-center">
                <svg class="w-4 h-4 text-{{ $ep['color'] }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $ep['icon'] }}"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-gray-900">{{ $ep['label'] }}</span>
        </div>

        <div class="flex items-end justify-between mb-2">
            <span class="text-2xl font-semibold text-gray-900">{{ number_format($u['used']) }}</span>
            <span class="text-xs text-gray-400 mb-1">
                / {{ $u['limit'] === PHP_INT_MAX ? '∞' : number_format($u['limit']) }}
            </span>
        </div>

        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden mb-2">
            <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $pct }}%"></div>
        </div>

        <div class="flex justify-between text-xs text-gray-400">
            <span>{{ $pct }}% used</span>
            <span>{{ $u['limit'] === PHP_INT_MAX ? '∞' : number_format(max(0, $u['limit'] - $u['used'])) }} remaining</span>
        </div>
    </div>
    @endforeach
</div>

{{-- Daily chart --}}
<div class="bg-white border border-gray-100 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-5">
        <p class="text-sm font-medium text-gray-900">Daily breakdown — last 30 days</p>
        <div class="flex items-center gap-4 text-xs text-gray-400">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-blue-400 inline-block"></span>Scrape</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-violet-400 inline-block"></span>PDF</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-400 inline-block"></span>Email</span>
        </div>
    </div>
    <div style="height:220px;position:relative">
        <canvas id="dailyChart"></canvas>
    </div>
</div>

{{-- Recent activity log --}}
<div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-50">
        <p class="text-sm font-medium text-gray-900">Recent activity</p>
    </div>
    @if($logs->isEmpty())
    <div class="py-12 text-center text-sm text-gray-400">No API calls yet this month.</div>
    @else
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-50">
                <th class="text-left text-xs font-medium text-gray-400 px-5 py-3">Endpoint</th>
                <th class="text-left text-xs font-medium text-gray-400 px-5 py-3">Status</th>
                <th class="text-left text-xs font-medium text-gray-400 px-5 py-3">Response time</th>
                <th class="text-left text-xs font-medium text-gray-400 px-5 py-3">IP</th>
                <th class="text-left text-xs font-medium text-gray-400 px-5 py-3">Time</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($logs as $log)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3">
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full
                        {{ $log->endpoint === 'scrape' ? 'bg-blue-50 text-blue-700' :
                           ($log->endpoint === 'pdf'   ? 'bg-violet-50 text-violet-700' :
                                                         'bg-emerald-50 text-emerald-700') }}">
                        {{ $log->endpoint }}
                    </span>
                </td>
                <td class="px-5 py-3">
                    <span class="text-xs {{ $log->status_code < 300 ? 'text-green-600' : ($log->status_code < 500 ? 'text-amber-600' : 'text-red-600') }}">
                        {{ $log->status_code }}
                    </span>
                </td>
                <td class="px-5 py-3 text-xs text-gray-400">
                    {{ $log->response_ms ? $log->response_ms . 'ms' : '—' }}
                </td>
                <td class="px-5 py-3 text-xs text-gray-400 font-mono">
                    {{ $log->ip_address ?? '—' }}
                </td>
                <td class="px-5 py-3 text-xs text-gray-400">
                    {{ $log->created_at->diffForHumans() }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($logs->hasPages())
    <div class="px-5 py-3 border-t border-gray-50">
        {{ $logs->links() }}
    </div>
    @endif
    @endif
</div>

@endsection

@push('scripts')
<script>
const history = @json($history);
const labels = [];
for (let i = 29; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    labels.push(d.toLocaleDateString('en', { month: 'short', day: 'numeric' }));
}
function buildDataset(endpoint, color) {
    return labels.map((_, i) => {
        const d = new Date();
        d.setDate(d.getDate() - (29 - i));
        const dateStr = d.toISOString().split('T')[0];
        const found = history.find(h => h.date === dateStr && h.endpoint === endpoint);
        return found ? parseInt(found.count) : 0;
    });
}
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels,
        datasets: [
            { label: 'Scrape', data: buildDataset('scrape'), backgroundColor: '#60a5fa', borderRadius: 3 },
            { label: 'PDF',    data: buildDataset('pdf'),    backgroundColor: '#a78bfa', borderRadius: 3 },
            { label: 'Email',  data: buildDataset('email'),  backgroundColor: '#34d399', borderRadius: 3 },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { stacked: true, ticks: { color: '#9ca3af', font: { size: 10 }, maxTicksLimit: 8 }, grid: { display: false } },
            y: { stacked: true, ticks: { color: '#9ca3af', font: { size: 10 }, precision: 0 }, grid: { color: '#f3f4f6' } },
        },
    },
});
</script>
@endpush