@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="İstatistikler"
        subtitle="Genel performans ve analiz verileri"
    >
        <x-slot name="icon">
            <x-ui.icon name="chart" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <x-ui.button href="{{ route('bayi.gelismis-istatistik') }}" icon="chart">
                Gelişmiş İstatistik
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- İstatistik Kartları --}}
    <x-layout.grid cols="1" mdCols="2" lgCols="4" gap="6" class="mb-6">
        <x-ui.stat-card
            title="Bugünkü Sipariş"
            :value="$stats['today_orders']"
            :subtitle="($stats['pending_orders'] ?? 0) . ' beklemede'"
            icon="order"
            color="blue"
        />

        <x-ui.stat-card
            title="Aktif Kurye"
            :value="$stats['active_couriers']"
            :subtitle="($stats['on_delivery_orders'] ?? 0) . ' yolda'"
            icon="users"
            color="green"
        />

        <x-ui.stat-card
            title="Ort. Teslimat Süresi"
            :value="$stats['avg_delivery_time'] . ' dk'"
            subtitle="Son 30 gün ortalaması"
            icon="clock"
            color="orange"
        />

        <x-ui.stat-card
            title="Bugunku Gelir"
            :value="'₺' . number_format($stats['today_revenue'], 0, ',', '.')"
            icon="money"
            color="green"
        />
    </x-layout.grid>

    {{-- Tamamlanma Oranı --}}
    <x-ui.card class="mb-6">
        <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Tamamlanma Oranı (Son 30 Gün)</h3>
        <div class="flex items-center gap-4">
            <div class="flex-1 h-4 bg-gray-200 dark:bg-gray-800 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full transition-all duration-500" style="width: {{ $stats['completion_rate'] }}%"></div>
            </div>
            <span class="text-2xl font-bold text-black dark:text-white">%{{ $stats['completion_rate'] }}</span>
        </div>
        <p class="text-sm text-gray-500 mt-2">Teslim edilen siparişlerin toplam siparişlere oranı</p>
    </x-ui.card>

    {{-- Hızlı İstatistikler --}}
    <x-layout.grid cols="2" mdCols="4" gap="4">
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800">
            <p class="text-xs text-yellow-600 font-medium uppercase tracking-wider">Bekleyen</p>
            <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-400">{{ $stats['pending_orders'] ?? 0 }}</p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
            <p class="text-xs text-blue-600 font-medium uppercase tracking-wider">Yolda</p>
            <p class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $stats['on_delivery_orders'] ?? 0 }}</p>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
            <p class="text-xs text-green-600 font-medium uppercase tracking-wider">Aktif Kurye</p>
            <p class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $stats['active_couriers'] }}</p>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
            <p class="text-xs text-purple-600 font-medium uppercase tracking-wider">Ort. Teslimat</p>
            <p class="text-2xl font-bold text-purple-700 dark:text-purple-400">{{ $stats['avg_delivery_time'] }} dk</p>
        </div>
    </x-layout.grid>
</div>
@endsection
