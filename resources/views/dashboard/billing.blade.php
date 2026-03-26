{{-- resources/views/dashboard/billing.blade.php --}}
@extends('layouts.app')
@section('title', 'Billing')
@section('heading', 'Billing')

@section('content')

@php $currentPlan = auth()->user()->plan; @endphp

{{-- Current plan banner --}}
@if($currentPlan !== 'free')
<div class="bg-white border border-gray-100 rounded-xl p-5 mb-6 flex items-center justify-between">
    <div>
        <p class="text-xs text-gray-400 mb-0.5">Current plan</p>
        <p class="text-sm font-semibold text-gray-900">{{ ucfirst($currentPlan) }} — ${{ ['starter'=>10,'pro'=>19,'business'=>29][$currentPlan] }}/month</p>
    </div>
    <form method="POST" action="{{ route('billing.portal') }}">
        @csrf
        <button type="submit"
                class="text-sm border border-gray-200 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-50">
            Manage billing →
        </button>
    </form>
</div>
@endif

{{-- Plan cards --}}
<div class="grid grid-cols-3 gap-5">
    @foreach([
        [
            'key'    => 'starter',
            'name'   => 'Starter',
            'price'  => 10,
            'limits' => ['500 scrapes / mo', '100 PDFs / mo', '1,000 emails / mo', '1 API key'],
            'note'   => 'For solo devs & side projects',
            'stripe' => env('STRIPE_PRICE_STARTER'),
        ],
        [
            'key'      => 'pro',
            'name'     => 'Pro',
            'price'    => 19,
            'limits'   => ['5,000 scrapes / mo', '500 PDFs / mo', '10,000 emails / mo', '5 API keys', 'JS rendering (Browsershot)'],
            'note'     => 'For startups & freelancers',
            'popular'  => true,
            'stripe'   => env('STRIPE_PRICE_PRO'),
        ],
        [
            'key'    => 'business',
            'name'   => 'Business',
            'price'  => 29,
            'limits' => ['Unlimited scrapes', '2,000 PDFs / mo', '50,000 emails / mo', 'Unlimited API keys', 'Priority support'],
            'note'   => 'For small teams & agencies',
            'stripe' => env('STRIPE_PRICE_BUSINESS'),
        ],
    ] as $plan)
    <div class="bg-white border rounded-xl p-6 flex flex-col
                {{ isset($plan['popular']) ? 'border-blue-400 ring-1 ring-blue-400' : 'border-gray-100' }}
                {{ $currentPlan === $plan['key'] ? 'opacity-75' : '' }}">

        @if(isset($plan['popular']))
        <div class="mb-3">
            <span class="text-xs bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full font-medium">Most popular</span>
        </div>
        @endif

        <p class="text-base font-semibold text-gray-900">{{ $plan['name'] }}</p>
        <p class="text-xs text-gray-400 mt-0.5 mb-4">{{ $plan['note'] }}</p>

        <div class="flex items-baseline gap-1 mb-5">
            <span class="text-3xl font-bold text-gray-900">${{ $plan['price'] }}</span>
            <span class="text-sm text-gray-400">/month</span>
        </div>

        <ul class="space-y-2 mb-6 flex-1">
            @foreach($plan['limits'] as $limit)
            <li class="flex items-center gap-2 text-sm text-gray-600">
                <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ $limit }}
            </li>
            @endforeach
        </ul>

        @if($currentPlan === $plan['key'])
            <span class="w-full text-center text-sm bg-gray-100 text-gray-400 px-4 py-2.5 rounded-lg font-medium">
                Current plan
            </span>
        @elseif($currentPlan === 'free' || ['free'=>0,'starter'=>1,'pro'=>2,'business'=>3][$currentPlan] < ['free'=>0,'starter'=>1,'pro'=>2,'business'=>3][$plan['key']])
            <form method="POST" action="{{ route('billing.checkout') }}">
                @csrf
                <input type="hidden" name="price_id" value="{{ $plan['stripe'] }}">
                <input type="hidden" name="plan" value="{{ $plan['key'] }}">
                <button type="submit"
                        class="w-full text-sm px-4 py-2.5 rounded-lg font-medium
                               {{ isset($plan['popular']) ? 'bg-blue-600 text-white hover:bg-blue-700' : 'border border-gray-200 text-gray-700 hover:bg-gray-50' }}">
                    Upgrade to {{ $plan['name'] }}
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('billing.portal') }}">
                @csrf
                <button type="submit"
                        class="w-full text-sm border border-gray-200 text-gray-500 px-4 py-2.5 rounded-lg hover:bg-gray-50">
                    Downgrade
                </button>
            </form>
        @endif
    </div>
    @endforeach
</div>

{{-- Free tier note --}}
<p class="text-xs text-center text-gray-400 mt-5">
    Free tier available — 50 scrapes, 10 PDFs, 100 emails per month. No credit card required.
    All paid plans include a 14-day money-back guarantee.
</p>

@endsection