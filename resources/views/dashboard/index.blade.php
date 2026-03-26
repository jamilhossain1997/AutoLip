{{-- resources/views/dashboard/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Overview')
@section('heading', 'Overview')

@section('content')

{{-- Stat cards --}}
<div class="grid grid-cols-4 gap-4 mb-8">
    @foreach([
        ['label' => 'Scrapes this month',  'used' => $usage['scrape']['used'],  'limit' => $usage['scrape']['limit'],  'color' => 'blue'],
        ['label' => 'PDFs this month',     'used' => $usage['pdf']['used'],     'limit' => $usage['pdf']['limit'],     'color' => 'violet'],
        ['label' => 'Emails this month',   'used' => $usage['email']['used'],   'limit' => $usage['email']['limit'],   'color' => 'emerald'],
        ['label' => 'Active API keys',     'used' => $keyCount,                 'limit' => $keyLimit,                  'color' => 'amber'],
    ] as $card)
    <div class="bg-white border border-gray-100 rounded-xl p-5">
        <p class="text-xs text-gray-400 mb-1">{{ $card['label'] }}</p>
        <p class="text-2xl font-semibold text-gray-900">
            {{ number_format($card['used']) }}
        </p>
        <p class="text-xs text-gray-400 mt-1">
            of {{ $card['limit'] === PHP_INT_MAX ? 'unlimited' : number_format($card['limit']) }}
        </p>
        {{-- Mini progress bar --}}
        @if($card['limit'] !== PHP_INT_MAX && $card['limit'] > 0)
        @php $pct = min(100, round($card['used'] / $card['limit'] * 100)); @endphp
        <div class="mt-3 h-1 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full
                {{ $pct >= 90 ? 'bg-red-400' : ($pct >= 70 ? 'bg-amber-400' : 'bg-'.$card['color'].'-400') }}"
                 style="width: {{ $pct }}%"></div>
        </div>
        @endif
    </div>
    @endforeach
</div>

{{-- Usage chart + quick start --}}
<div class="grid grid-cols-3 gap-6 mb-6">

    {{-- Usage chart (last 30 days) --}}
    <div class="col-span-2 bg-white border border-gray-100 rounded-xl p-6">
        <div class="flex items-center justify-between mb-5">
            <p class="text-sm font-medium text-gray-900">API calls — last 30 days</p>
            <div class="flex items-center gap-4 text-xs text-gray-400">
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-sm bg-blue-400 inline-block"></span>Scrape
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-sm bg-violet-400 inline-block"></span>PDF
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-sm bg-emerald-400 inline-block"></span>Email
                </span>
            </div>
        </div>
        <div style="height: 200px; position: relative;">
            <canvas id="usageChart"></canvas>
        </div>
    </div>

    {{-- Plan summary --}}
    <div class="bg-white border border-gray-100 rounded-xl p-6 flex flex-col">
        <p class="text-sm font-medium text-gray-900 mb-4">Your plan</p>
        @php $plan = auth()->user()->plan; @endphp
        <div class="flex items-center gap-2 mb-4">
            <span class="text-xl font-semibold">{{ ucfirst($plan) }}</span>
            <span class="text-xs px-2 py-0.5 rounded-full
                {{ $plan === 'business' ? 'bg-purple-50 text-purple-700' :
                   ($plan === 'pro'      ? 'bg-blue-50 text-blue-700' :
                   ($plan === 'starter'  ? 'bg-green-50 text-green-700' :
                                           'bg-gray-100 text-gray-500')) }}">
                {{ $plan === 'free' ? 'Free' : '$' . ['starter'=>10,'pro'=>19,'business'=>29][$plan] . '/mo' }}
            </span>
        </div>
        <div class="space-y-2 text-xs text-gray-500 flex-1">
            <div class="flex justify-between"><span>Scrapes / mo</span>
                <span class="font-medium text-gray-900">{{ $usage['scrape']['limit'] === PHP_INT_MAX ? '∞' : number_format($usage['scrape']['limit']) }}</span></div>
            <div class="flex justify-between"><span>PDFs / mo</span>
                <span class="font-medium text-gray-900">{{ $usage['pdf']['limit'] === PHP_INT_MAX ? '∞' : number_format($usage['pdf']['limit']) }}</span></div>
            <div class="flex justify-between"><span>Emails / mo</span>
                <span class="font-medium text-gray-900">{{ $usage['email']['limit'] === PHP_INT_MAX ? '∞' : number_format($usage['email']['limit']) }}</span></div>
            <div class="flex justify-between"><span>Resets</span>
                <span class="font-medium text-gray-900">{{ now()->endOfMonth()->format('M d') }}</span></div>
        </div>
        @if($plan !== 'business')
        <a href="{{ route('billing.index') }}"
           class="mt-4 w-full text-center text-xs bg-blue-600 text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 font-medium">
            Upgrade plan
        </a>
        @endif
    </div>
</div>

{{-- Quick start --}}
<div class="bg-white border border-gray-100 rounded-xl p-6">
    <p class="text-sm font-medium text-gray-900 mb-4">Quick start</p>
    <div x-data="{ tab: 'scrape' }">
        <div class="flex gap-2 mb-4">
            @foreach(['scrape' => 'Scrape', 'pdf' => 'PDF', 'email' => 'Email'] as $key => $label)
            <button @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
                    class="text-xs px-3 py-1.5 rounded-lg font-medium transition-colors">
                {{ $label }}
            </button>
            @endforeach
        </div>

        <div x-show="tab === 'scrape'">
            <pre class="bg-gray-950 text-gray-100 rounded-lg p-4 text-xs overflow-x-auto leading-relaxed"><code>curl -X POST https://api.autolib.dev/api/scrape \
  -H "Authorization: Bearer <span class="text-blue-400">YOUR_API_KEY</span>" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://example.com",
    "selector": "h1"
  }'</code></pre>
        </div>
        <div x-show="tab === 'pdf'" x-cloak>
            <pre class="bg-gray-950 text-gray-100 rounded-lg p-4 text-xs overflow-x-auto leading-relaxed"><code>curl -X POST https://api.autolib.dev/api/pdf \
  -H "Authorization: Bearer <span class="text-blue-400">YOUR_API_KEY</span>" \
  -H "Content-Type: application/json" \
  -d '{
    "html": "&lt;h1&gt;Invoice #001&lt;/h1&gt;",
    "filename": "invoice.pdf"
  }' --output invoice.pdf</code></pre>
        </div>
        <div x-show="tab === 'email'" x-cloak>
            <pre class="bg-gray-950 text-gray-100 rounded-lg p-4 text-xs overflow-x-auto leading-relaxed"><code>curl -X POST https://api.autolib.dev/api/email \
  -H "Authorization: Bearer <span class="text-blue-400">YOUR_API_KEY</span>" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "user@example.com",
    "subject": "Hello from AutoLib",
    "html": "&lt;h1&gt;Hello!&lt;/h1&gt;"
  }'</code></pre>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const history = @json($history);

// Build last 30 days labels
const labels = [];
for (let i = 29; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    labels.push(d.toLocaleDateString('en', { month: 'short', day: 'numeric' }));
}

// Map history data to datasets
function buildDataset(endpoint, color) {
    return labels.map((_, i) => {
        const d = new Date();
        d.setDate(d.getDate() - (29 - i));
        const dateStr = d.toISOString().split('T')[0];
        const found = history.find(h => h.date === dateStr && h.endpoint === endpoint);
        return found ? parseInt(found.count) : 0;
    });
}

new Chart(document.getElementById('usageChart'), {
    type: 'bar',
    data: {
        labels,
        datasets: [
            { label: 'Scrape', data: buildDataset('scrape'), backgroundColor: '#60a5fa', borderRadius: 3, borderSkipped: false },
            { label: 'PDF',    data: buildDataset('pdf'),    backgroundColor: '#a78bfa', borderRadius: 3, borderSkipped: false },
            { label: 'Email',  data: buildDataset('email'),  backgroundColor: '#34d399', borderRadius: 3, borderSkipped: false },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: {
                stacked: true,
                ticks: { color: '#9ca3af', font: { size: 10 }, maxTicksLimit: 8 },
                grid: { display: false },
            },
            y: {
                stacked: true,
                ticks: { color: '#9ca3af', font: { size: 10 }, precision: 0 },
                grid: { color: '#f3f4f6' },
            },
        },
    },
});
</script>
@endpush