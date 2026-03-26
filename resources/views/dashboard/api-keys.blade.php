{{-- resources/views/dashboard/api-keys.blade.php --}}
@extends('layouts.app')
@section('title', 'API Keys')
@section('heading', 'API Keys')

@section('content')

{{-- New key revealed banner --}}
@if(session('new_key'))
<div x-data="{ copied: false }" class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-5">
    <p class="text-sm font-medium text-amber-900 mb-1">Copy your new API key — it will not be shown again</p>
    <div class="flex items-center gap-3 mt-2">
        <code class="flex-1 bg-white border border-amber-200 rounded-lg px-4 py-2.5 text-sm font-mono text-gray-900 overflow-x-auto">
            {{ session('new_key') }}
        </code>
        <button @click="navigator.clipboard.writeText('{{ session('new_key') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                class="flex-shrink-0 text-xs px-4 py-2.5 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-medium">
            <span x-show="!copied">Copy</span>
            <span x-show="copied" x-cloak>Copied!</span>
        </button>
    </div>
</div>
@endif

{{-- Header row --}}
<div class="flex items-center justify-between mb-5">
    <div>
        <p class="text-xs text-gray-400">
            {{ $keys->where('is_active', true)->count() }} of {{ $keyLimit }} keys used
        </p>
    </div>
    @if($keys->where('is_active', true)->count() < $keyLimit)
    <button onclick="document.getElementById('new-key-modal').classList.remove('hidden')"
            class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-medium">
        + New key
    </button>
    @else
    <span class="text-xs text-gray-400 bg-gray-100 px-3 py-2 rounded-lg">
        Key limit reached — <a href="{{ route('billing.index') }}" class="text-blue-600">upgrade to add more</a>
    </span>
    @endif
</div>

{{-- Keys table --}}
<div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
    @if($keys->isEmpty())
    <div class="py-16 text-center">
        <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
        </svg>
        <p class="text-sm text-gray-400 mb-4">No API keys yet</p>
        <button onclick="document.getElementById('new-key-modal').classList.remove('hidden')"
                class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            Create your first key
        </button>
    </div>
    @else
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100">
                <th class="text-left text-xs font-medium text-gray-400 px-5 py-3">Name</th>
                <th class="text-left text-xs font-medium text-gray-400 px-5 py-3">Key</th>
                <th class="text-left text-xs font-medium text-gray-400 px-5 py-3">Status</th>
                <th class="text-left text-xs font-medium text-gray-400 px-5 py-3">Last used</th>
                <th class="text-left text-xs font-medium text-gray-400 px-5 py-3">Created</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($keys as $key)
            <tr class="hover:bg-gray-50 group">
                <td class="px-5 py-3.5 font-medium text-gray-900">{{ $key->name }}</td>
                <td class="px-5 py-3.5">
                    <code class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded font-mono">
                        {{ $key->prefix }}••••••••••••••••••••••••••••••
                    </code>
                </td>
                <td class="px-5 py-3.5">
                    @if($key->is_active)
                        <span class="inline-flex items-center gap-1.5 text-xs text-green-700 bg-green-50 px-2 py-0.5 rounded-full">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>Active
                        </span>
                    @else
                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Revoked</span>
                    @endif
                </td>
                <td class="px-5 py-3.5 text-xs text-gray-400">
                    {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Never' }}
                </td>
                <td class="px-5 py-3.5 text-xs text-gray-400">
                    {{ $key->created_at->format('M d, Y') }}
                </td>
                <td class="px-5 py-3.5 text-right">
                    @if($key->is_active)
                    <form method="POST" action="{{ route('api-keys.destroy', $key->id) }}"
                          onsubmit="return confirm('Revoke this key? Any apps using it will stop working immediately.')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="text-xs text-gray-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity">
                            Revoke
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- New key modal --}}
<div id="new-key-modal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50"
     onclick="if(event.target === this) this.classList.add('hidden')">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-base font-semibold">Create new API key</h2>
            <button onclick="document.getElementById('new-key-modal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form method="POST" action="{{ route('api-keys.store') }}">
            @csrf
            <label class="block text-xs font-medium text-gray-700 mb-1.5">Key name</label>
            <input type="text" name="name" placeholder="e.g. Production, My App"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   maxlength="64" required>
            <p class="text-xs text-gray-400 mt-1.5">Give it a name so you remember what it's for.</p>
            <div class="flex gap-3 mt-5">
                <button type="button"
                        onclick="document.getElementById('new-key-modal').classList.add('hidden')"
                        class="flex-1 border border-gray-200 text-sm text-gray-600 px-4 py-2.5 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 bg-blue-600 text-white text-sm px-4 py-2.5 rounded-lg hover:bg-blue-700 font-medium">
                    Generate key
                </button>
            </div>
        </form>
    </div>
</div>

@endsection