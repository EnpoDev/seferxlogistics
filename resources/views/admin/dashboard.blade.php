@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-black dark:text-white">Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400">Sistem genelindeki istatistikleri görüntüleyin</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Toplam Bayi -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Toplam Bayi</p>
                    <p class="text-3xl font-bold">{{ $stats['total_bayiler'] }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Toplam Kurye -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Toplam Kurye</p>
                    <p class="text-3xl font-bold">{{ $stats['total_kuryeler'] }}</p>
                    <p class="text-green-100 text-xs mt-1">{{ $stats['aktif_kuryeler'] }} aktif</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Toplam Sube -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Toplam Sube</p>
                    <p class="text-3xl font-bold">{{ $stats['total_subeler'] }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Aktif Abonelik -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm">Aktif Abonelik</p>
                    <p class="text-3xl font-bold">{{ $stats['aktif_abonelikler'] }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Siparis Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Sipariş</p>
            <p class="text-2xl font-bold text-black dark:text-white">{{ number_format($stats['total_siparisler']) }}</p>
        </div>
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Bugün</p>
            <p class="text-2xl font-bold text-black dark:text-white">{{ number_format($stats['bugun_siparisler']) }}</p>
        </div>
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Bu Hafta</p>
            <p class="text-2xl font-bold text-black dark:text-white">{{ number_format($stats['haftalik_siparisler']) }}</p>
        </div>
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Bu Ay</p>
            <p class="text-2xl font-bold text-black dark:text-white">{{ number_format($stats['aylik_siparisler']) }}</p>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Son Siparisler -->
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h2 class="font-semibold text-black dark:text-white">Son Siparişler</h2>
                <a href="{{ route('admin.siparisler.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white">
                    Tümünü Gör
                </a>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($son_siparisler as $siparis)
                <div class="p-4 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-black dark:text-white">{{ $siparis->order_number }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $siparis->branch?->name ?? 'N/A' }}</p>
                    </div>
                    <div class="text-right">
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                'preparing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                'ready' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                'on_delivery' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                'delivered' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                            ];
                        @endphp
                        <span class="inline-block px-2 py-1 rounded-full text-xs {{ $statusColors[$siparis->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($siparis->status) }}
                        </span>
                        <p class="text-xs text-gray-500 mt-1">{{ $siparis->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-gray-500">
                    Henüz sipariş bulunmuyor
                </div>
                @endforelse
            </div>
        </div>

        <!-- Son Bayiler -->
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h2 class="font-semibold text-black dark:text-white">Son Kayıt Olan Bayiler</h2>
                <a href="{{ route('admin.bayiler.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white">
                    Tümünü Gör
                </a>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($son_bayiler as $bayi)
                <div class="p-4 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                            <span class="text-blue-600 dark:text-blue-400 font-medium">{{ strtoupper(substr($bayi->name, 0, 2)) }}</span>
                        </div>
                        <div>
                            <p class="font-medium text-black dark:text-white">{{ $bayi->name }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $bayi->email }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">{{ $bayi->created_at->diffForHumans() }}</p>
                </div>
                @empty
                <div class="p-8 text-center text-gray-500">
                    Henüz bayi bulunmuyor
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Bekleyen Destek Talepleri -->
    @if($stats['bekleyen_destek'] > 0)
    <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="font-medium text-yellow-800 dark:text-yellow-200">{{ $stats['bekleyen_destek'] }} bekleyen destek talebi var</p>
                    <p class="text-sm text-yellow-600 dark:text-yellow-400">Lütfen destek taleplerini inceleyin</p>
                </div>
            </div>
            <a href="{{ route('admin.destek.index') }}" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                Talepleri Gör
            </a>
        </div>
    </div>
    @endif
</div>
@endsection
