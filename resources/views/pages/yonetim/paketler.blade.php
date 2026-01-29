@extends('layouts.app')

@section('content')
<div class="p-6" x-data="{ billingPeriod: 'monthly' }">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-black dark:text-white">Paketler</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Size uygun plani secin</p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg mb-6">
            <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg mb-6">
            <p class="text-red-700 dark:text-red-400">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Periyot Secimi -->
    <div class="flex justify-center mb-8">
        <div class="inline-flex items-center bg-gray-100 dark:bg-gray-900 rounded-lg p-1">
            <button @click="billingPeriod = 'monthly'"
                    :class="billingPeriod === 'monthly' ? 'bg-white dark:bg-black text-black dark:text-white shadow' : 'text-gray-600 dark:text-gray-400'"
                    class="px-4 py-2 rounded-md text-sm font-medium transition-all">
                Aylik
            </button>
            <button @click="billingPeriod = 'yearly'"
                    :class="billingPeriod === 'yearly' ? 'bg-white dark:bg-black text-black dark:text-white shadow' : 'text-gray-600 dark:text-gray-400'"
                    class="px-4 py-2 rounded-md text-sm font-medium transition-all">
                Yillik <span class="text-green-600 dark:text-green-400 text-xs ml-1">2 ay ucretsiz</span>
            </button>
        </div>
    </div>

    <!-- Aylik Planlar -->
    <div x-show="billingPeriod === 'monthly'" x-transition>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($plans->where('billing_period', 'monthly') as $plan)
            @php
                $isCurrentPlan = $currentSubscription && $currentSubscription->plan_id === $plan->id;
                $isUpgrade = $currentSubscription && $currentSubscription->plan->price < $plan->price;
            @endphp
            <div class="relative {{ $plan->is_featured ? 'bg-black dark:bg-white border-2 border-black dark:border-white' : 'bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800' }} rounded-xl p-6">
                @if($plan->is_featured)
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="bg-green-500 text-white text-xs px-3 py-1 rounded-full">En Populer</span>
                    </div>
                @endif

                <h3 class="text-lg font-semibold {{ $plan->is_featured ? 'text-white dark:text-black' : 'text-black dark:text-white' }} mb-2">{{ $plan->name }}</h3>
                <p class="text-sm {{ $plan->is_featured ? 'text-gray-300 dark:text-gray-600' : 'text-gray-600 dark:text-gray-400' }} mb-4">{{ $plan->description }}</p>

                <div class="mb-4">
                    <span class="text-3xl font-bold {{ $plan->is_featured ? 'text-white dark:text-black' : 'text-black dark:text-white' }}">{{ number_format($plan->price, 2) }} TL</span>
                    <span class="{{ $plan->is_featured ? 'text-gray-300 dark:text-gray-600' : 'text-gray-600 dark:text-gray-400' }}">/ay</span>
                </div>

                <ul class="space-y-2 mb-6">
                    @foreach($plan->features ?? [] as $feature)
                    <li class="flex items-center text-sm {{ $plan->is_featured ? 'text-gray-300 dark:text-gray-600' : 'text-gray-600 dark:text-gray-400' }}">
                        <svg class="w-4 h-4 mr-2 {{ $plan->is_featured ? 'text-green-400' : 'text-green-600' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>

                @if($isCurrentPlan)
                    <button disabled class="w-full px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-500 rounded-lg cursor-not-allowed">Aktif Plan</button>
                @elseif($currentSubscription && $isUpgrade)
                    <form action="{{ route('billing.subscription.upgrade', $plan) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">Yukselt</button>
                    </form>
                @elseif(!$currentSubscription)
                    <form action="{{ route('billing.subscribe', $plan) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 {{ $plan->is_featured ? 'bg-white dark:bg-black text-black dark:text-white' : 'bg-black dark:bg-white text-white dark:text-black' }} rounded-lg hover:opacity-90 transition-colors">Sec</button>
                    </form>
                @else
                    <button disabled class="w-full px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-500 rounded-lg cursor-not-allowed">Mevcut Plandan Dusuk</button>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <!-- Yillik Planlar -->
    <div x-show="billingPeriod === 'yearly'" x-transition>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($plans->where('billing_period', 'yearly') as $plan)
            @php
                $isCurrentPlan = $currentSubscription && $currentSubscription->plan_id === $plan->id;
                $isUpgrade = $currentSubscription && $currentSubscription->plan->price < $plan->price;
            @endphp
            <div class="relative {{ $plan->is_featured ? 'bg-black dark:bg-white border-2 border-black dark:border-white' : 'bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800' }} rounded-xl p-6">
                @if($plan->is_featured)
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="bg-green-500 text-white text-xs px-3 py-1 rounded-full">En Populer</span>
                    </div>
                @endif

                <h3 class="text-lg font-semibold {{ $plan->is_featured ? 'text-white dark:text-black' : 'text-black dark:text-white' }} mb-2">{{ str_replace(' Yillik', '', $plan->name) }}</h3>
                <p class="text-sm {{ $plan->is_featured ? 'text-gray-300 dark:text-gray-600' : 'text-gray-600 dark:text-gray-400' }} mb-4">{{ $plan->description }}</p>

                <div class="mb-4">
                    <span class="text-3xl font-bold {{ $plan->is_featured ? 'text-white dark:text-black' : 'text-black dark:text-white' }}">{{ number_format($plan->price, 2) }} TL</span>
                    <span class="{{ $plan->is_featured ? 'text-gray-300 dark:text-gray-600' : 'text-gray-600 dark:text-gray-400' }}">/yil</span>
                </div>

                <ul class="space-y-2 mb-6">
                    @foreach($plan->features ?? [] as $feature)
                    <li class="flex items-center text-sm {{ $plan->is_featured ? 'text-gray-300 dark:text-gray-600' : 'text-gray-600 dark:text-gray-400' }}">
                        <svg class="w-4 h-4 mr-2 {{ $plan->is_featured ? 'text-green-400' : 'text-green-600' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>

                @if($isCurrentPlan)
                    <button disabled class="w-full px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-500 rounded-lg cursor-not-allowed">Aktif Plan</button>
                @elseif($currentSubscription && $isUpgrade)
                    <form action="{{ route('billing.subscription.upgrade', $plan) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">Yukselt</button>
                    </form>
                @elseif(!$currentSubscription)
                    <form action="{{ route('billing.subscribe', $plan) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 {{ $plan->is_featured ? 'bg-white dark:bg-black text-black dark:text-white' : 'bg-black dark:bg-white text-white dark:text-black' }} rounded-lg hover:opacity-90 transition-colors">Sec</button>
                    </form>
                @else
                    <button disabled class="w-full px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-500 rounded-lg cursor-not-allowed">Mevcut Plandan Dusuk</button>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    @if($plans->isEmpty())
    <div class="text-center py-12">
        <p class="text-gray-500">Henuz tanimli plan bulunmuyor</p>
    </div>
    @endif

    @if($currentSubscription)
    <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="font-medium text-blue-700 dark:text-blue-400">Aktif Aboneliğiniz: <strong>{{ $currentSubscription->plan->name }}</strong></p>
                <p class="text-xs text-blue-600 dark:text-blue-500 mt-1">Sonraki ödeme: {{ $currentSubscription->next_billing_date?->locale('tr')->isoFormat('D MMMM YYYY') ?? '-' }}</p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
