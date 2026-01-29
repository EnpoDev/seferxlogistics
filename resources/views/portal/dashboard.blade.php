<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Siparişlerim - Müşteri Portalı</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-black rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="font-semibold text-gray-900">Merhaba, {{ $customer->name }}</h1>
                    <p class="text-sm text-gray-500">{{ $customer->phone }}</p>
                </div>
            </div>
            <a href="{{ route('portal.logout') }}" class="text-gray-600 hover:text-red-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
            </a>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-6 space-y-6">
        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-white rounded-xl p-4 border border-gray-200">
                <p class="text-sm text-gray-500">Toplam Sipariş</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['total_orders'] }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 border border-gray-200">
                <p class="text-sm text-gray-500">Bu Ay</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['this_month'] }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 border border-gray-200">
                <p class="text-sm text-gray-500">Toplam Harcama</p>
                <p class="text-2xl font-bold text-green-600">{{ number_format($stats['total_spent'], 0) }} TL</p>
            </div>
        </div>

        <!-- Active Orders -->
        @if($activeOrders->count() > 0)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 bg-green-50 border-b border-green-100">
                <h2 class="font-semibold text-green-800 flex items-center gap-2">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    Aktif Siparişler
                </h2>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($activeOrders as $order)
                <a href="{{ route('portal.order', $order) }}" class="block px-4 py-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">#{{ $order->order_number }}</p>
                            <p class="text-sm text-gray-500">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                @if($order->status === 'pending') bg-yellow-100 text-yellow-700
                                @elseif($order->status === 'preparing') bg-blue-100 text-blue-700
                                @elseif($order->status === 'ready') bg-purple-100 text-purple-700
                                @elseif($order->status === 'on_way') bg-green-100 text-green-700
                                @endif">
                                @if($order->status === 'pending') Beklemede
                                @elseif($order->status === 'preparing') Hazırlanıyor
                                @elseif($order->status === 'ready') Hazır
                                @elseif($order->status === 'on_way') Yolda
                                @endif
                            </span>
                            <p class="text-sm font-semibold text-gray-900 mt-1">{{ number_format($order->total, 0) }} TL</p>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Recent Orders -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">Son Siparişler</h2>
                <a href="{{ route('portal.orders') }}" class="text-sm text-blue-600 hover:text-blue-700">Tümünü Gör</a>
            </div>
            @if($recentOrders->count() > 0)
            <div class="divide-y divide-gray-100">
                @foreach($recentOrders as $order)
                <a href="{{ route('portal.order', $order) }}" class="block px-4 py-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">#{{ $order->order_number }}</p>
                            <p class="text-sm text-gray-500">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                            @if($order->customer_address)
                                <p class="text-xs text-gray-400 mt-1 truncate max-w-xs">{{ $order->customer_address }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                @if($order->status === 'delivered') bg-green-100 text-green-700
                                @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                                @else bg-gray-100 text-gray-700
                                @endif">
                                @if($order->status === 'delivered') Teslim Edildi
                                @elseif($order->status === 'cancelled') İptal
                                @elseif($order->status === 'pending') Beklemede
                                @elseif($order->status === 'preparing') Hazırlanıyor
                                @elseif($order->status === 'ready') Hazır
                                @elseif($order->status === 'on_way') Yolda
                                @endif
                            </span>
                            <p class="text-sm font-semibold text-gray-900 mt-1">{{ number_format($order->total, 0) }} TL</p>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
            @else
            <div class="px-4 py-12 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <p>Henüz siparişiniz bulunmuyor.</p>
            </div>
            @endif
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 gap-4">
            <a href="{{ route('portal.orders') }}" class="block bg-white rounded-xl border border-gray-200 p-4 hover:border-black transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Sipariş Geçmişi</p>
                        <p class="text-xs text-gray-500">Tüm siparişlerinizi görün</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('portal.addresses') }}" class="block bg-white rounded-xl border border-gray-200 p-4 hover:border-black transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Adreslerim</p>
                        <p class="text-xs text-gray-500">Kayıtlı adresleriniz</p>
                    </div>
                </div>
            </a>
        </div>
    </main>
</body>
</html>
