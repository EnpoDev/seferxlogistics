<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Geçmişi - Müşteri Portalı</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center gap-4">
            <a href="{{ route('portal.dashboard') }}" class="p-2 hover:bg-gray-100 rounded-lg">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <h1 class="font-semibold text-gray-900">Sipariş Geçmişi</h1>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-6">
        @if($orders->count() > 0)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden divide-y divide-gray-100">
            @foreach($orders as $order)
            <a href="{{ route('portal.order', $order) }}" class="block px-4 py-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-start justify-between">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <p class="font-medium text-gray-900">#{{ $order->order_number }}</p>
                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                                @if($order->status === 'delivered') bg-green-100 text-green-700
                                @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                                @elseif($order->status === 'pending') bg-yellow-100 text-yellow-700
                                @elseif($order->status === 'preparing') bg-blue-100 text-blue-700
                                @elseif($order->status === 'ready') bg-purple-100 text-purple-700
                                @elseif($order->status === 'on_way') bg-green-100 text-green-700
                                @endif">
                                @if($order->status === 'delivered') Teslim Edildi
                                @elseif($order->status === 'cancelled') İptal
                                @elseif($order->status === 'pending') Beklemede
                                @elseif($order->status === 'preparing') Hazırlanıyor
                                @elseif($order->status === 'ready') Hazır
                                @elseif($order->status === 'on_way') Yolda
                                @endif
                            </span>
                        </div>
                        <p class="text-sm text-gray-500">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                        @if($order->customer_address)
                            <p class="text-xs text-gray-400 truncate max-w-xs">{{ $order->customer_address }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900">{{ number_format($order->total, 0) }} TL</p>
                        <p class="text-xs text-gray-500 mt-1">
                            @if($order->payment_method === 'cash') Nakit
                            @elseif($order->payment_method === 'credit_card') Kredi Kartı
                            @else Online
                            @endif
                        </p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($orders->hasPages())
        <div class="mt-6">
            {{ $orders->links() }}
        </div>
        @endif
        @else
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <p class="text-gray-500">Henüz siparişiniz bulunmuyor.</p>
        </div>
        @endif
    </main>
</body>
</html>
