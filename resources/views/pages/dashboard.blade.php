@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Dashboard</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Hoş geldiniz! İşte bugünkü özet.</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-500">{{ now()->translatedFormat('l, d F Y') }}</p>
            <p class="text-xs text-gray-400">Son güncelleme: {{ now()->format('H:i') }}</p>
        </div>
    </div>

    <!-- Today's Stats -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500">Bugün Sipariş</span>
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-black dark:text-white">{{ $todayStats['orders'] }}</p>
        </div>

        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500">Bugün Gelir</span>
                <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-black dark:text-white">₺{{ number_format($todayStats['revenue'], 0, ',', '.') }}</p>
        </div>

        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500">Bekleyen</span>
                <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-yellow-600">{{ $todayStats['pending'] }}</p>
        </div>

        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500">Teslim Edildi</span>
                <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-emerald-600">{{ $todayStats['delivered'] }}</p>
        </div>

        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500">İptal</span>
                <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-red-600">{{ $todayStats['cancelled'] }}</p>
        </div>

        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-500">Yeni Müşteri</span>
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-purple-600">{{ $todayStats['new_customers'] }}</p>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Active Orders -->
        <div class="lg:col-span-2 bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h3 class="font-semibold text-black dark:text-white">Aktif Siparişler</h3>
                <a href="{{ route('siparis.liste') }}" class="text-sm text-blue-600 hover:underline">Tümünü Gör</a>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-800 max-h-96 overflow-y-auto">
                @forelse($activeOrders as $order)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center space-x-3">
                            <span class="font-mono font-semibold text-black dark:text-white">{{ $order->order_number }}</span>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full
                                @if($order->status === 'pending') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400
                                @elseif($order->status === 'preparing') bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400
                                @elseif($order->status === 'ready') bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400
                                @elseif($order->status === 'on_delivery') bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400
                                @endif">
                                {{ $order->getStatusLabel() }}
                            </span>
                        </div>
                        <span class="font-semibold text-black dark:text-white">₺{{ number_format($order->total, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <div class="text-gray-600 dark:text-gray-400">
                            <span>{{ $order->customer_name }}</span>
                            @if($order->courier)
                            <span class="mx-2">•</span>
                            <span>{{ $order->courier->name }}</span>
                            @endif
                        </div>
                        <span class="text-gray-500">{{ $order->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p>Aktif sipariş bulunmuyor</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Courier Status -->
        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                <h3 class="font-semibold text-black dark:text-white">Kurye Durumu</h3>
            </div>
            <div class="p-4 space-y-4">
                <!-- Courier Stats -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <p class="text-xs text-green-600">Müsait</p>
                        <p class="text-xl font-bold text-green-700 dark:text-green-400">{{ $courierStats['available_couriers'] }}</p>
                    </div>
                    <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                        <p class="text-xs text-orange-600">Meşgul</p>
                        <p class="text-xl font-bold text-orange-700 dark:text-orange-400">{{ $courierStats['busy_couriers'] }}</p>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <p class="text-xs text-gray-600">Çevrimdışı</p>
                        <p class="text-xl font-bold text-gray-700 dark:text-gray-400">{{ $courierStats['offline_couriers'] }}</p>
                    </div>
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <p class="text-xs text-blue-600">Vardiyada</p>
                        <p class="text-xl font-bold text-blue-700 dark:text-blue-400">{{ $courierStats['on_shift_couriers'] }}</p>
                    </div>
                </div>

                <!-- Available Couriers List -->
                <div>
                    <p class="text-sm text-gray-500 mb-2">Müsait Kuryeler</p>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        @forelse($availableCouriers as $courier)
                        <div class="flex items-center space-x-3 p-2 bg-gray-50 dark:bg-black rounded-lg">
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                {{ substr($courier->name, 0, 2) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-black dark:text-white truncate">{{ $courier->name }}</p>
                                <p class="text-xs text-gray-500">{{ $courier->active_orders_count }} aktif sipariş</p>
                            </div>
                        </div>
                        @empty
                        <p class="text-sm text-gray-400 text-center py-4">Müsait kurye yok</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Top Products -->
        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                <h3 class="font-semibold text-black dark:text-white">En Çok Satan Ürünler</h3>
            </div>
            <div class="p-4 space-y-3">
                @forelse($topProducts as $index => $product)
                <div class="flex items-center space-x-3">
                    <span class="w-6 h-6 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center text-xs font-bold text-gray-600 dark:text-gray-400">
                        {{ $index + 1 }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-black dark:text-white truncate">{{ $product->product_name }}</p>
                        <p class="text-xs text-gray-500">{{ $product->total_sold }} adet satıldı</p>
                    </div>
                    <span class="text-sm font-semibold text-black dark:text-white">₺{{ number_format($product->total_revenue, 0) }}</span>
                </div>
                @empty
                <p class="text-sm text-gray-400 text-center py-4">Henüz satış yok</p>
                @endforelse
            </div>
        </div>

        <!-- Top Customers -->
        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                <h3 class="font-semibold text-black dark:text-white">En İyi Müşteriler</h3>
            </div>
            <div class="p-4 space-y-3">
                @forelse($topCustomers as $index => $customer)
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-xs font-bold">
                        {{ strtoupper(substr($customer->name, 0, 2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-black dark:text-white truncate">{{ $customer->name }}</p>
                        <p class="text-xs text-gray-500">{{ $customer->total_orders }} sipariş</p>
                    </div>
                    <span class="text-sm font-semibold text-black dark:text-white">₺{{ number_format($customer->total_spent, 0) }}</span>
                </div>
                @empty
                <p class="text-sm text-gray-400 text-center py-4">Henüz müşteri yok</p>
                @endforelse
            </div>
        </div>

        <!-- Overall Stats -->
        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                <h3 class="font-semibold text-black dark:text-white">Genel İstatistikler</h3>
            </div>
            <div class="p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Toplam Sipariş</span>
                    <span class="font-semibold text-black dark:text-white">{{ number_format($overallStats['total_orders']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Toplam Gelir</span>
                    <span class="font-semibold text-black dark:text-white">₺{{ number_format($overallStats['total_revenue'], 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Toplam Müşteri</span>
                    <span class="font-semibold text-black dark:text-white">{{ number_format($overallStats['total_customers']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Restoran Sayısı</span>
                    <span class="font-semibold text-black dark:text-white">{{ $overallStats['total_restaurants'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Ürün Sayısı</span>
                    <span class="font-semibold text-black dark:text-white">{{ $overallStats['total_products'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Ort. Sipariş Değeri</span>
                    <span class="font-semibold text-black dark:text-white">₺{{ number_format($overallStats['avg_order_value'], 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('siparis.create') }}" class="p-4 bg-black dark:bg-white text-white dark:text-black rounded-xl hover:opacity-90 transition-opacity flex items-center space-x-3">
            <div class="w-10 h-10 bg-white/20 dark:bg-black/20 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <span class="font-medium">Yeni Sipariş</span>
        </a>
        <a href="{{ route('musteri.index') }}" class="p-4 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex items-center space-x-3">
            <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <span class="font-medium">Müşteriler</span>
        </a>
        <a href="{{ route('restoran.index') }}" class="p-4 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex items-center space-x-3">
            <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <span class="font-medium">Restoranlar</span>
        </a>
        <a href="{{ route('isletmem.kuryeler') }}" class="p-4 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex items-center space-x-3">
            <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                </svg>
            </div>
            <span class="font-medium">Kuryeler</span>
        </a>
    </div>
</div>
@endsection

