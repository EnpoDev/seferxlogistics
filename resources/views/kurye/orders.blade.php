@extends('layouts.kurye')

@section('content')
<div class="p-4 space-y-4 slide-up">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-black dark:text-white">Siparişlerim</h1>
        <span class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-sm rounded-full">
            {{ $activeOrders->count() }} aktif
        </span>
    </div>

    @if($activeOrders->isEmpty())
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-12 text-center">
            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Aktif sipariş yok</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Havuzdan yeni sipariş alabilirsiniz</p>
            <a href="{{ route('kurye.pool') }}" class="inline-block px-6 py-3 bg-black dark:bg-white text-white dark:text-black rounded-xl font-medium">
                Havuza Git
            </a>
        </div>
    @else
        <div class="space-y-3" x-data="orderList()">
            @foreach($activeOrders as $order)
                <div class="block bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 card-interactive relative overflow-hidden">
                    <a href="{{ route('kurye.order.detail', $order) }}" class="block">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex flex-col">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Sipariş No</span>
                                <span class="text-lg font-bold text-black dark:text-white">#{{ $order->order_number }}</span>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Tutar</span>
                                <span class="text-lg font-bold text-black dark:text-white">₺{{ number_format($order->total, 2, ',', '.') }}</span>
                            </div>
                        </div>

                        <!-- Status & Payment -->
                        <div class="flex items-center space-x-2 mb-4">
                            <span class="px-2.5 py-1 text-xs font-medium rounded-lg
                                {{ $order->display_status === 'assigned' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                                {{ $order->display_status === 'picked_up' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' : '' }}
                                {{ $order->display_status === 'on_way' ? 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300' : '' }}">
                                {{ $order->getStatusLabel() }}
                            </span>
                            <span class="px-2.5 py-1 text-xs font-medium rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                {{ $order->items->count() }} Ürün
                            </span>
                        </div>
                        
                        <!-- Route Info (Pickup -> Delivery) -->
                        <div class="space-y-4 mb-4 relative pl-3">
                            <!-- Vertical Line -->
                            <div class="absolute left-[5px] top-2 bottom-6 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                            <!-- Pickup -->
                            <div class="flex items-start space-x-3 relative">
                                <div class="w-3 h-3 rounded-full bg-black dark:bg-white ring-4 ring-white dark:ring-gray-900 mt-1.5 flex-shrink-0"></div>
                                <div>
                                    <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-0.5">Teslim Alınacak</p>
                                    <p class="text-sm font-medium text-black dark:text-white">
                                        {{ $order->branch->name ?? ($order->restaurant->name ?? 'Restoran') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Delivery -->
                            <div class="flex items-start space-x-3 relative">
                                <div class="w-3 h-3 rounded-full border-2 border-black dark:border-white bg-white dark:bg-black ring-4 ring-white dark:ring-gray-900 mt-1.5 flex-shrink-0"></div>
                                <div>
                                    <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-0.5">Teslim Edilecek</p>
                                    <p class="text-sm font-medium text-black dark:text-white">{{ $order->customer_name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1">{{ $order->customer_address }}</p>
                                </div>
                            </div>
                        </div>
                    </a>

                    <!-- Quick Actions Footer -->
                    <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                        @if($order->display_status === 'assigned')
                            <button @click="updateStatus({{ $order->id }}, 'picked_up')" class="col-span-2 py-2.5 bg-black dark:bg-white text-white dark:text-black rounded-xl font-medium text-sm">
                                📦 Teslim Aldım
                            </button>
                        @elseif($order->display_status === 'picked_up')
                            <button @click="updateStatus({{ $order->id }}, 'on_way')" class="col-span-2 py-2.5 bg-black dark:bg-white text-white dark:text-black rounded-xl font-medium text-sm">
                                🚗 Yola Çıktım
                            </button>
                        @elseif($order->display_status === 'on_way')
                            <div class="col-span-2 flex space-x-2">
                                <a href="tel:{{ $order->customer_phone }}" class="flex-1 flex items-center justify-center space-x-2 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-black dark:text-white">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <span class="text-sm font-medium">Ara</span>
                                </a>
                                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $order->lat }},{{ $order->lng }}" target="_blank" class="flex-1 flex items-center justify-center space-x-2 py-2.5 rounded-xl bg-blue-600 text-white">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                    </svg>
                                    <span class="text-sm font-medium">Yol Tarifi</span>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
<script>
function orderList() {
    return {
        async updateStatus(orderId, status) {
            if (!confirm('Durumu güncellemek istediğinize emin misiniz?')) return;
            
            try {
                const response = await fetch(`/kurye/siparis/${orderId}/durum`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Bir hata oluştu');
                }
            } catch (error) {
                alert('Bağlantı hatası');
            }
        }
    }
}
</script>
@endpush
@endsection