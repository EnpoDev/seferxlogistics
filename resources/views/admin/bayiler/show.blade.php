@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.bayiler.index') }}" class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white mb-4">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Bayilere Dön
            </a>
            <h1 class="text-2xl font-bold text-black dark:text-white">{{ $user->name }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ $user->email }}</p>
        </div>
        <a href="{{ route('admin.bayiler.edit', $user) }}" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            <span>Düzenle</span>
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Sipariş</p>
            <p class="text-2xl font-bold text-black dark:text-white">{{ number_format($stats['total_siparisler']) }}</p>
        </div>
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Bu Ay Sipariş</p>
            <p class="text-2xl font-bold text-black dark:text-white">{{ number_format($stats['aylik_siparisler']) }}</p>
        </div>
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Kayıt Tarihi</p>
            <p class="text-2xl font-bold text-black dark:text-white">{{ $user->created_at->format('d.m.Y') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Bayi Bilgileri -->
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                <h2 class="font-semibold text-black dark:text-white">Bayi Bilgileri</h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Ad Soyad</p>
                    <p class="font-medium text-black dark:text-white">{{ $user->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">E-posta</p>
                    <p class="font-medium text-black dark:text-white">{{ $user->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Roller</p>
                    <div class="flex flex-wrap gap-2 mt-1">
                        @foreach($user->roles ?? [] as $role)
                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded text-xs">{{ ucfirst($role) }}</span>
                        @endforeach
                    </div>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Kayıt Tarihi</p>
                    <p class="font-medium text-black dark:text-white">{{ $user->created_at->format('d.m.Y H:i') }}</p>
                </div>
            </div>
        </div>

        <!-- Abonelik Bilgileri -->
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                <h2 class="font-semibold text-black dark:text-white">Abonelik Bilgileri</h2>
            </div>
            <div class="p-6">
                @if($user->activeSubscription)
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Plan</p>
                        <p class="font-medium text-black dark:text-white">{{ $user->activeSubscription->plan->name ?? 'Bilinmiyor' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Durum</p>
                        @php
                            $statusColors = [
                                'active' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                'trial' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                'expired' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-400',
                            ];
                        @endphp
                        <span class="inline-block px-2 py-1 rounded text-sm {{ $statusColors[$user->activeSubscription->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($user->activeSubscription->status) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Başlangıç</p>
                        <p class="font-medium text-black dark:text-white">{{ $user->activeSubscription->starts_at?->format('d.m.Y') ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Sonraki Ödeme</p>
                        <p class="font-medium text-black dark:text-white">{{ $user->activeSubscription->next_billing_date?->format('d.m.Y') ?? '-' }}</p>
                    </div>
                </div>
                @else
                <div class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">Aktif abonelik bulunmuyor</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Abonelik Gecmisi -->
    @if($user->subscriptions->count() > 0)
    <div class="mt-6 bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800">
        <div class="p-4 border-b border-gray-200 dark:border-gray-800">
            <h2 class="font-semibold text-black dark:text-white">Abonelik Geçmişi</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-black">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Başlangıç</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bitiş</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($user->subscriptions as $subscription)
                    <tr>
                        <td class="px-6 py-4 text-black dark:text-white">{{ $subscription->plan->name ?? 'Bilinmiyor' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded text-xs {{ $statusColors[$subscription->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($subscription->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $subscription->starts_at?->format('d.m.Y') ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $subscription->ends_at?->format('d.m.Y') ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
