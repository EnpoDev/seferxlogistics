@extends('layouts.kurye')

@section('content')
<div class="p-4 space-y-4 slide-up" x-data="poolPage()">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-black dark:text-white">Sipariş Havuzu</h1>
        <button @click="refreshPool()" class="p-2 text-gray-600 dark:text-gray-400 touch-active">
            <svg class="w-5 h-5" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </div>

    <p class="text-sm text-gray-500 dark:text-gray-400">
        Henüz atanmamış siparişleri buradan alabilirsiniz.
    </p>

    @if($poolOrders->isEmpty())
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-12 text-center">
            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Havuz Boş</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Şu anda bekleyen sipariş bulunmuyor</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($poolOrders as $order)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
                    <div class="p-4">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <span class="text-lg font-bold text-black dark:text-white">#{{ $order->order_number }}</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $order->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="text-lg font-bold text-green-600 dark:text-green-400">₺{{ number_format($order->total, 2, ',', '.') }}</span>
                        </div>
                        
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center space-x-2 text-sm">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="text-black dark:text-white">{{ $order->customer_name }}</span>
                            </div>
                            <div class="flex items-start space-x-2 text-sm">
                                <svg class="w-4 h-4 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                <span class="text-gray-600 dark:text-gray-400 line-clamp-2">{{ $order->customer_address }}</span>
                            </div>
                        </div>
                        
                        <button @click="acceptOrder({{ $order->id }})" 
                                :disabled="accepting === {{ $order->id }}"
                                class="w-full py-3 bg-black dark:bg-white text-white dark:text-black rounded-xl font-semibold disabled:opacity-50 touch-active">
                            <span x-show="accepting !== {{ $order->id }}">Siparişi Al</span>
                            <span x-show="accepting === {{ $order->id }}">Alınıyor...</span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
<script>
function poolPage() {
    return {
        refreshing: false,
        accepting: null,
        
        async refreshPool() {
            this.refreshing = true;
            location.reload();
        },
        
        async acceptOrder(orderId) {
            this.accepting = orderId;
            
            try {
                const response = await fetch(`/kurye/siparis/${orderId}/kabul`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'Bir hata oluştu');
                    this.accepting = null;
                }
            } catch (error) {
                alert('Bağlantı hatası');
                this.accepting = null;
            }
        }
    }
}
</script>
@endpush
@endsection

