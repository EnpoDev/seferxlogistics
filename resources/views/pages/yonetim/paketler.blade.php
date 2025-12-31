@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn" x-data="{ billingPeriod: 'monthly' }">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Paketler"
        subtitle="Size uygun planı seçin"
    >
        <x-slot name="icon">
            <x-ui.icon name="package" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>
    </x-layout.page-header>

    {{-- Mesajlar --}}
    @if(session('success'))
        <x-feedback.alert type="success" class="mb-6">{{ session('success') }}</x-feedback.alert>
    @endif
    @if(session('error'))
        <x-feedback.alert type="danger" class="mb-6">{{ session('error') }}</x-feedback.alert>
    @endif

    {{-- Periyot Seçimi --}}
    <div class="flex justify-center mb-8">
        <div class="inline-flex items-center bg-gray-100 dark:bg-gray-900 rounded-lg p-1">
            <button @click="billingPeriod = 'monthly'"
                    :class="billingPeriod === 'monthly' ? 'bg-white dark:bg-black text-black dark:text-white shadow' : 'text-gray-600 dark:text-gray-400'"
                    class="px-4 py-2 rounded-md text-sm font-medium transition-all">
                Aylık
            </button>
            <button @click="billingPeriod = 'yearly'"
                    :class="billingPeriod === 'yearly' ? 'bg-white dark:bg-black text-black dark:text-white shadow' : 'text-gray-600 dark:text-gray-400'"
                    class="px-4 py-2 rounded-md text-sm font-medium transition-all">
                Yıllık <span class="text-green-600 dark:text-green-400 text-xs ml-1">2 ay ücretsiz</span>
            </button>
        </div>
    </div>

    {{-- Aylık Planlar --}}
    <div x-show="billingPeriod === 'monthly'" x-transition>
        <x-layout.grid cols="1" mdCols="3" gap="6">
            @foreach($plans->where('billing_period', 'monthly') as $plan)
            @php
                $isCurrentPlan = $currentSubscription && $currentSubscription->plan_id === $plan->id;
                $isUpgrade = $currentSubscription && $currentSubscription->plan->price < $plan->price;
            @endphp
            <div class="relative {{ $plan->is_featured ? 'bg-black dark:bg-white border-2 border-black dark:border-white' : 'bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800' }} rounded-lg p-6">
                @if($plan->is_featured)
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <x-ui.badge type="success">En Popüler</x-ui.badge>
                    </div>
                @endif

                <h3 class="text-lg font-semibold {{ $plan->is_featured ? 'text-white dark:text-black' : 'text-black dark:text-white' }} mb-2">{{ $plan->name }}</h3>
                <p class="text-sm {{ $plan->is_featured ? 'text-gray-300 dark:text-gray-600' : 'text-gray-600 dark:text-gray-400' }} mb-4">{{ $plan->description }}</p>

                <div class="mb-4">
                    <span class="text-3xl font-bold {{ $plan->is_featured ? 'text-white dark:text-black' : 'text-black dark:text-white' }}">{{ $plan->getFormattedPrice() }}</span>
                    <span class="{{ $plan->is_featured ? 'text-gray-300 dark:text-gray-600' : 'text-gray-600 dark:text-gray-400' }}">/ay</span>
                </div>

                <ul class="space-y-2 mb-6">
                    @foreach($plan->features ?? [] as $feature)
                    <li class="flex items-center text-sm {{ $plan->is_featured ? 'text-gray-300 dark:text-gray-600' : 'text-gray-600 dark:text-gray-400' }}">
                        <x-ui.icon name="check" class="w-4 h-4 mr-2 {{ $plan->is_featured ? 'text-green-400' : 'text-green-600' }}" />
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>

                @if($isCurrentPlan)
                    <x-ui.button disabled class="w-full" :variant="$plan->is_featured ? 'secondary' : 'ghost'">Aktif Plan</x-ui.button>
                @elseif($currentSubscription && $isUpgrade)
                    <form action="{{ route('billing.subscription.upgrade', $plan) }}" method="POST">
                        @csrf
                        <x-ui.button type="submit" class="w-full" :variant="$plan->is_featured ? 'secondary' : 'primary'">Yükselt</x-ui.button>
                    </form>
                @elseif(!$currentSubscription)
                    <form action="{{ route('billing.subscribe', $plan) }}" method="POST">
                        @csrf
                        <x-ui.button type="submit" class="w-full" :variant="$plan->is_featured ? 'secondary' : 'outline'">Seç</x-ui.button>
                    </form>
                @else
                    <x-ui.button disabled class="w-full" variant="ghost">Mevcut Plandan Düşük</x-ui.button>
                @endif
            </div>
            @endforeach
        </x-layout.grid>
    </div>

    {{-- Yıllık Planlar --}}
    <div x-show="billingPeriod === 'yearly'" x-transition>
        <x-layout.grid cols="1" mdCols="3" gap="6">
            @foreach($plans->where('billing_period', 'yearly') as $plan)
            @php
                $isCurrentPlan = $currentSubscription && $currentSubscription->plan_id === $plan->id;
                $isUpgrade = $currentSubscription && $currentSubscription->plan->price < $plan->price;
            @endphp
            <div class="relative {{ $plan->is_featured ? 'bg-black dark:bg-white border-2 border-black dark:border-white' : 'bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800' }} rounded-lg p-6">
                @if($plan->is_featured)
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <x-ui.badge type="success">En Popüler</x-ui.badge>
                    </div>
                @endif

                <h3 class="text-lg font-semibold {{ $plan->is_featured ? 'text-white dark:text-black' : 'text-black dark:text-white' }} mb-2">{{ str_replace(' Yıllık', '', $plan->name) }}</h3>
                <p class="text-sm {{ $plan->is_featured ? 'text-gray-300 dark:text-gray-600' : 'text-gray-600 dark:text-gray-400' }} mb-4">{{ $plan->description }}</p>

                <div class="mb-4">
                    <span class="text-3xl font-bold {{ $plan->is_featured ? 'text-white dark:text-black' : 'text-black dark:text-white' }}">{{ $plan->getFormattedPrice() }}</span>
                    <span class="{{ $plan->is_featured ? 'text-gray-300 dark:text-gray-600' : 'text-gray-600 dark:text-gray-400' }}">/yıl</span>
                </div>

                <ul class="space-y-2 mb-6">
                    @foreach($plan->features ?? [] as $feature)
                    <li class="flex items-center text-sm {{ $plan->is_featured ? 'text-gray-300 dark:text-gray-600' : 'text-gray-600 dark:text-gray-400' }}">
                        <x-ui.icon name="check" class="w-4 h-4 mr-2 {{ $plan->is_featured ? 'text-green-400' : 'text-green-600' }}" />
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>

                @if($isCurrentPlan)
                    <x-ui.button disabled class="w-full" :variant="$plan->is_featured ? 'secondary' : 'ghost'">Aktif Plan</x-ui.button>
                @elseif($currentSubscription && $isUpgrade)
                    <form action="{{ route('billing.subscription.upgrade', $plan) }}" method="POST">
                        @csrf
                        <x-ui.button type="submit" class="w-full" :variant="$plan->is_featured ? 'secondary' : 'primary'">Yükselt</x-ui.button>
                    </form>
                @elseif(!$currentSubscription)
                    <form action="{{ route('billing.subscribe', $plan) }}" method="POST">
                        @csrf
                        <x-ui.button type="submit" class="w-full" :variant="$plan->is_featured ? 'secondary' : 'outline'">Seç</x-ui.button>
                    </form>
                @else
                    <x-ui.button disabled class="w-full" variant="ghost">Mevcut Plandan Düşük</x-ui.button>
                @endif
            </div>
            @endforeach
        </x-layout.grid>
    </div>

    @if($plans->isEmpty())
    <x-ui.empty-state title="Plan bulunamadı" description="Henüz tanımlı plan bulunmuyor" icon="package" />
    @endif

    {{-- Mevcut Abonelik Bilgisi --}}
    @if($currentSubscription)
    <x-feedback.alert type="info" class="mt-8">
        <div class="flex items-center justify-between">
            <div>
                <p class="font-medium">Aktif Aboneliğiniz: <strong>{{ $currentSubscription->plan->name }}</strong></p>
                <p class="text-xs mt-1">Sonraki ödeme: {{ $currentSubscription->next_billing_date?->format('d M Y') ?? '-' }}</p>
            </div>
            <a href="{{ route('yonetim.abonelikler') }}" class="text-sm hover:underline">Detayları Gör →</a>
        </div>
    </x-feedback.alert>
    @endif
</div>
@endsection
