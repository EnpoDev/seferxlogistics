@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6 animate-fadeIn">
    {{-- Header --}}
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

    {{-- Today's Stats --}}
    <x-layout.grid cols="2" mdCols="3" lgCols="6" gap="4">
        <x-ui.stat-card
            title="Bugün Sipariş"
            :value="$todayStats['orders']"
            icon="order"
            color="blue"
        />
        <x-ui.stat-card
            title="Bugün Gelir"
            :value="'₺' . number_format($todayStats['revenue'], 0, ',', '.')"
            icon="money"
            color="green"
        />
        <x-ui.stat-card
            title="Bekleyen"
            :value="$todayStats['pending']"
            icon="clock"
            color="yellow"
        />
        <x-ui.stat-card
            title="Teslim Edildi"
            :value="$todayStats['delivered']"
            icon="check"
            color="green"
        />
        <x-ui.stat-card
            title="İptal"
            :value="$todayStats['cancelled']"
            icon="x"
            color="red"
        />
        <x-ui.stat-card
            title="Yeni Müşteri"
            :value="$todayStats['new_customers']"
            icon="user-plus"
            color="purple"
        />
    </x-layout.grid>

    {{-- Main Content Grid --}}
    <x-layout.grid cols="1" lgCols="3" gap="6">
        {{-- Active Orders --}}
        <div class="lg:col-span-2">
            <x-ui.card class="overflow-hidden">
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
                                <x-data.status-badge :status="$order->status" entity="order" />
                            </div>
                            <x-data.money :amount="$order->total" />
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
                    <x-ui.empty-state
                        title="Aktif sipariş yok"
                        description="Aktif sipariş bulunmuyor"
                        icon="order"
                        class="py-8"
                    />
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        {{-- Courier Status --}}
        <x-ui.card class="overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                <h3 class="font-semibold text-black dark:text-white">Kurye Durumu</h3>
            </div>
            <div class="p-4 space-y-4">
                <x-layout.grid cols="2" gap="3">
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
                </x-layout.grid>

                <div>
                    <p class="text-sm text-gray-500 mb-2">Müsait Kuryeler</p>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        @forelse($availableCouriers as $courier)
                        <div class="flex items-center space-x-3 p-2 bg-gray-50 dark:bg-black rounded-lg">
                            <x-data.courier-avatar :courier="$courier" size="sm" />
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
        </x-ui.card>
    </x-layout.grid>

    {{-- Bottom Section --}}
    <x-layout.grid cols="1" lgCols="3" gap="6">
        {{-- Top Products --}}
        <x-ui.card class="overflow-hidden">
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
                    <x-data.money :amount="$product->total_revenue" />
                </div>
                @empty
                <p class="text-sm text-gray-400 text-center py-4">Henüz satış yok</p>
                @endforelse
            </div>
        </x-ui.card>

        {{-- Top Customers --}}
        <x-ui.card class="overflow-hidden">
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
                    <x-data.money :amount="$customer->total_spent" />
                </div>
                @empty
                <p class="text-sm text-gray-400 text-center py-4">Henüz müşteri yok</p>
                @endforelse
            </div>
        </x-ui.card>

        {{-- Overall Stats --}}
        <x-ui.card class="overflow-hidden">
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
        </x-ui.card>
    </x-layout.grid>

    {{-- Quick Actions --}}
    <x-layout.grid cols="2" mdCols="4" gap="4">
        <a href="{{ route('siparis.create') }}" class="p-4 bg-black dark:bg-white text-white dark:text-black rounded-xl hover:opacity-90 transition-opacity flex items-center space-x-3">
            <div class="w-10 h-10 bg-white/20 dark:bg-black/20 rounded-lg flex items-center justify-center">
                <x-ui.icon name="plus" class="w-5 h-5" />
            </div>
            <span class="font-medium">Yeni Sipariş</span>
        </a>
        <a href="{{ route('musteri.index') }}" class="p-4 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex items-center space-x-3">
            <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                <x-ui.icon name="users" class="w-5 h-5" />
            </div>
            <span class="font-medium">Müşteriler</span>
        </a>
        <a href="{{ route('restoran.index') }}" class="p-4 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex items-center space-x-3">
            <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                <x-ui.icon name="building" class="w-5 h-5" />
            </div>
            <span class="font-medium">Restoranlar</span>
        </a>
        <a href="{{ route('isletmem.kuryeler') }}" class="p-4 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex items-center space-x-3">
            <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                <x-ui.icon name="truck" class="w-5 h-5" />
            </div>
            <span class="font-medium">Kuryeler</span>
        </a>
    </x-layout.grid>
</div>
@endsection
