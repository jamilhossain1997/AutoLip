{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — AutoLib</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .font-mono { font-family: ui-monospace, 'Cascadia Code', monospace; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased">

{{-- Sidebar + main layout --}}
<div class="flex min-h-screen">

    {{-- Sidebar --}}
    <aside class="w-56 bg-white border-r border-gray-100 flex flex-col fixed inset-y-0 z-20">
        {{-- Logo --}}
        <div class="h-16 flex items-center px-5 border-b border-gray-100">
            <span class="text-lg font-semibold tracking-tight">AutoLib</span>
            <span class="ml-2 text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full font-medium">API</span>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 py-4 px-3 space-y-0.5">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium
                      {{ request()->routeIs('dashboard') ? 'bg-gray-100 text-gray-900' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Overview
            </a>
            <a href="{{ route('api-keys.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium
                      {{ request()->routeIs('api-keys.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                API Keys
            </a>
            <a href="{{ route('usage.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium
                      {{ request()->routeIs('usage.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Usage
            </a>
            <a href="{{ route('billing.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium
                      {{ request()->routeIs('billing.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                Billing
            </a>
            <a href="https://docs.autolib.dev" target="_blank"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Docs
                <svg class="w-3 h-3 ml-auto opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </nav>

        {{-- User section --}}
        <div class="border-t border-gray-100 p-3">
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                        class="w-full flex items-center gap-2 px-2 py-2 rounded-lg hover:bg-gray-50 text-left">
                    <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-semibold flex-shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ auth()->user()->plan }} plan</p>
                    </div>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false"
                     class="absolute bottom-full left-0 w-full mb-1 bg-white border border-gray-100 rounded-lg shadow-lg overflow-hidden">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full text-left px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50">
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    {{-- Main content --}}
    <main class="flex-1 ml-56">
        {{-- Top bar --}}
        <header class="h-16 bg-white border-b border-gray-100 flex items-center px-8 sticky top-0 z-10">
            <h1 class="text-sm font-semibold text-gray-900">@yield('heading', 'Dashboard')</h1>
            <div class="ml-auto flex items-center gap-3">
                {{-- Plan badge --}}
                @php $plan = auth()->user()->plan; @endphp
                <span class="text-xs px-2.5 py-1 rounded-full font-medium
                    {{ $plan === 'business' ? 'bg-purple-50 text-purple-700' :
                       ($plan === 'pro'      ? 'bg-blue-50 text-blue-700' :
                       ($plan === 'starter'  ? 'bg-green-50 text-green-700' :
                                               'bg-gray-100 text-gray-600')) }}">
                    {{ ucfirst($plan) }}
                </span>
                @if($plan === 'free' || $plan === 'starter')
                    <a href="{{ route('billing.index') }}"
                       class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 font-medium">
                        Upgrade
                    </a>
                @endif
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-cloak
                 class="mx-8 mt-6 bg-green-50 border border-green-100 text-green-800 text-sm px-4 py-3 rounded-lg flex items-center justify-between">
                <span>{{ session('success') }}</span>
                <button @click="show = false" class="text-green-500 hover:text-green-700 ml-4">✕</button>
            </div>
        @endif
        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-cloak
                 class="mx-8 mt-6 bg-red-50 border border-red-100 text-red-800 text-sm px-4 py-3 rounded-lg flex items-center justify-between">
                <span>{{ session('error') }}</span>
                <button @click="show = false" class="text-red-500 hover:text-red-700 ml-4">✕</button>
            </div>
        @endif

        <div class="p-8">
            @yield('content')
        </div>
    </main>
</div>

</body>
</html>