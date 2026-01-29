@extends('layouts.app')

@section('content')
<div class="p-6" x-data="{ showCancelModal: false }">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-black dark:text-white">Aboneliklerim</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Aktif aboneliklerinizi goruntuleyin</p>
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

    @if($subscription && $subscription->plan)
    <!-- Aktif Abonelik -->
    <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6 mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-1">{{ $subscription->plan->name }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $subscription->plan->getPeriodLabel() }} abonelik</p>
            </div>
            @php
                $statusColors = [
                    'active' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
                    'cancelled' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                    'trial' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
                ];
            @endphp
            <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$subscription->status] ?? 'bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-400' }}">
                {{ $subscription->getStatusLabel() }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ $subscription->plan->getPeriodLabel() }} Ucret</p>
                <p class="text-2xl font-bold text-black dark:text-white">{{ $subscription->plan->getFormattedPrice() }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Sonraki Odeme</p>
                <p class="text-lg font-semibold text-black dark:text-white">
                    {{ $subscription->next_billing_date ? $subscription->next_billing_date->locale('tr')->isoFormat('D MMMM YYYY') : '-' }}
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Baslangic Tarihi</p>
                <p class="text-lg font-semibold text-black dark:text-white">
                    {{ $subscription->starts_at ? $subscription->starts_at->locale('tr')->isoFormat('D MMMM YYYY') : '-' }}
                </p>
            </div>
        </div>

        @if($subscription->isActive())
        <div class="flex gap-3">
            <a href="{{ route('yonetim.paketler') }}" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
                Plani Yukselt
            </a>
            <button @click="showCancelModal = true" class="px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
                Iptal Et
            </button>
        </div>
        @elseif($subscription->isCancelled())
        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
            <p class="text-yellow-700 dark:text-yellow-400">
                Aboneliginiz {{ $subscription->ends_at ? $subscription->ends_at->locale('tr')->isoFormat('D MMMM YYYY') : 'donem sonunda' }} tarihinde sona erecektir.
                @if($subscription->cancel_reason)
                    <br><span class="text-xs">Iptal nedeni: {{ $subscription->cancel_reason }}</span>
                @endif
            </p>
        </div>
        @endif
    </div>

    <!-- Plan Ozellikleri -->
    @if($subscription->plan->features)
    <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
        <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Plan Ozellikleri</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($subscription->plan->features as $feature)
            <div class="flex items-center space-x-3">
                <div class="w-5 h-5 border-2 border-black dark:border-white rounded-full flex items-center justify-center">
                    <div class="w-2 h-2 bg-black dark:bg-white rounded-full"></div>
                </div>
                <span class="text-sm text-black dark:text-white">{{ $feature }}</span>
            </div>
            @endforeach
        </div>

        @if($subscription->plan->max_users || $subscription->plan->max_orders || $subscription->plan->max_branches)
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-3 gap-4">
                @if($subscription->plan->max_users)
                <div class="text-center">
                    <p class="text-2xl font-bold text-black dark:text-white">{{ $subscription->plan->max_users }}</p>
                    <p class="text-xs text-gray-500">Kullanici</p>
                </div>
                @endif
                @if($subscription->plan->max_orders)
                <div class="text-center">
                    <p class="text-2xl font-bold text-black dark:text-white">{{ $subscription->plan->max_orders === -1 ? '∞' : $subscription->plan->max_orders }}</p>
                    <p class="text-xs text-gray-500">Siparis/Ay</p>
                </div>
                @endif
                @if($subscription->plan->max_branches)
                <div class="text-center">
                    <p class="text-2xl font-bold text-black dark:text-white">{{ $subscription->plan->max_branches }}</p>
                    <p class="text-xs text-gray-500">Sube</p>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
    @endif

    @else
    <!-- Abonelik Yok -->
    <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Aktif aboneliginiz bulunmuyor</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">Hemen bir plan secerek tum ozelliklere erisin.</p>
        <a href="{{ route('yonetim.paketler') }}" class="inline-block px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
            Planlari Incele
        </a>
    </div>
    @endif

    <!-- Iptal Modal -->
    <div x-show="showCancelModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showCancelModal = false"></div>
            <div class="relative bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Aboneligi Iptal Et</h3>

                <form action="{{ route('billing.subscription.cancel') }}" method="POST">
                    @csrf
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Aboneliginizi iptal etmek istediginizden emin misiniz? Aboneliginiz donem sonuna kadar aktif kalacaktir.
                    </p>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Iptal Nedeni (Opsiyonel)</label>
                        <textarea name="reason" rows="3" placeholder="Iptal nedeninizi paylasirsiniz seviniriz..."
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400"></textarea>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="showCancelModal = false" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
                            Vazgec
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                            Aboneligi Iptal Et
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
