@extends('layouts.kurye')

@section('content')
<div class="slide-up" x-data="orderDetail()">
    <!-- Map Section -->
    <div class="relative h-56 bg-gray-200 dark:bg-gray-800">
        <div id="orderMap" class="w-full h-full"></div>
        
        <!-- Back Button -->
        <a href="{{ route('kurye.orders') }}" class="absolute top-4 left-4 w-10 h-10 bg-white dark:bg-black rounded-full flex items-center justify-center shadow-lg">
            <svg class="w-5 h-5 text-black dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
    </div>

    <!-- Order Details -->
    <div class="p-4 space-y-4 -mt-6 relative">
        <!-- Navigation Button (Moved here to float on top) -->
        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $order->lat }},{{ $order->lng }}" 
           target="_blank"
           class="absolute right-4 -top-5 px-4 py-2 bg-blue-500 text-white rounded-full flex items-center space-x-2 shadow-lg z-20 hover:bg-blue-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            <span class="font-medium">Yol Tarifi</span>
        </a>

        <!-- Status Card -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <span class="text-lg font-bold text-black dark:text-white">#{{ $order->order_number }}</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                </div>
                <span class="px-3 py-1 text-sm font-medium rounded-full
                    {{ $order->status === 'assigned' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                    {{ $order->status === 'picked_up' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : '' }}
                    {{ $order->status === 'on_way' ? 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400' : '' }}
                    {{ $order->status === 'delivered' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}">
                    {{ $order->getStatusLabel() }}
                </span>
            </div>
            
            <!-- Status Progress -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex-1 flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center {{ in_array($order->status, ['assigned', 'picked_up', 'on_way', 'delivered']) ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-700' }}">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="flex-1 h-1 mx-2 {{ in_array($order->status, ['picked_up', 'on_way', 'delivered']) ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-700' }}"></div>
                </div>
                <div class="flex-1 flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center {{ in_array($order->status, ['picked_up', 'on_way', 'delivered']) ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-700' }}">
                        @if(in_array($order->status, ['picked_up', 'on_way', 'delivered']))
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">2</span>
                        @endif
                    </div>
                    <div class="flex-1 h-1 mx-2 {{ in_array($order->status, ['on_way', 'delivered']) ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-700' }}"></div>
                </div>
                <div class="flex-1 flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center {{ in_array($order->status, ['on_way', 'delivered']) ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-700' }}">
                        @if(in_array($order->status, ['on_way', 'delivered']))
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">3</span>
                        @endif
                    </div>
                    <div class="flex-1 h-1 mx-2 {{ $order->status === 'delivered' ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-700' }}"></div>
                </div>
                <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $order->status === 'delivered' ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-700' }}">
                    @if($order->status === 'delivered')
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    @else
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-400">4</span>
                    @endif
                </div>
            </div>
            
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>Atandı</span>
                <span>Alındı</span>
                <span>Yolda</span>
                <span>Teslim</span>
            </div>
        </div>

        <!-- Pickup Info -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-3">TESLİM ALINACAK YER</h3>
            
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-black dark:text-white">
                        {{ $order->branch->name ?? ($order->restaurant->name ?? 'Restoran') }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $order->branch->address ?? ($order->restaurant->address ?? 'Adres bilgisi yok') }}
                    </p>
                </div>
            </div>
            
            @if(isset($order->branch->phone) || isset($order->restaurant->phone))
            <a href="tel:{{ $order->branch->phone ?? $order->restaurant->phone }}" class="mt-3 flex items-center justify-center space-x-2 py-2 w-full rounded-xl bg-gray-50 dark:bg-gray-800 text-black dark:text-white text-sm font-medium border border-gray-200 dark:border-gray-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                <span>Restoranı Ara</span>
            </a>
            @endif
        </div>

        <!-- Customer Info -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-3">MÜŞTERİ BİLGİLERİ</h3>
            
            <div class="space-y-3">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-black dark:text-white">{{ $order->customer_name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Müşteri</p>
                    </div>
                </div>
                
                <a href="tel:{{ $order->customer_phone }}" class="flex items-center space-x-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-green-800 dark:text-green-400">{{ $order->customer_phone }}</p>
                        <p class="text-xs text-green-600 dark:text-green-500">Aramak için dokun</p>
                    </div>
                </a>
                
                <div class="flex items-start space-x-3">
                    <div class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-black dark:text-white">{{ $order->customer_address }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Teslimat Adresi</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-3">SİPARİŞ İÇERİĞİ</h3>
            
            <div class="space-y-3">
                @if($order->items && $order->items->count() > 0)
                    @foreach($order->items as $item)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <span class="w-6 h-6 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center text-xs font-medium text-gray-600 dark:text-gray-400">
                                    {{ $item->quantity }}x
                                </span>
                                <span class="text-sm text-black dark:text-white">{{ $item->product_name ?? $item->name }}</span>
                            </div>
                            <span class="text-sm font-medium text-black dark:text-white">₺{{ number_format($item->price * $item->quantity, 2, ',', '.') }}</span>
                        </div>
                    @endforeach
                @else
                    <p class="text-sm text-gray-500">Sipariş detayları mevcut değil</p>
                @endif
            </div>
            
            <div class="border-t border-gray-200 dark:border-gray-700 mt-4 pt-4">
                <div class="flex items-center justify-between text-lg font-bold">
                    <span class="text-black dark:text-white">Toplam</span>
                    <span class="text-black dark:text-white">₺{{ number_format($order->total, 2, ',', '.') }}</span>
                </div>
                @if($order->payment_method)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Ödeme: {{ $order->payment_method === 'cash' ? 'Nakit' : ($order->payment_method === 'card' ? 'Kart' : 'Online') }}
                    </p>
                @endif
            </div>
        </div>

        @if($order->notes)
        <!-- Notes -->
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-2xl p-4">
            <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-400 mb-2">📝 Sipariş Notu</h3>
            <p class="text-sm text-yellow-700 dark:text-yellow-300">{{ $order->notes }}</p>
        </div>
        @endif

        <!-- Action Buttons -->
        @if(!in_array($order->status, ['delivered', 'cancelled']))
        <div class="space-y-3 pb-4">
            @if($order->status === 'assigned')
                <button @click="updateStatus('picked_up')"
                        x-bind:disabled="loading"
                        class="w-full py-4 bg-purple-600 text-white rounded-xl font-semibold text-lg disabled:opacity-50 touch-active">
                    <span x-show="!loading">📦 Siparişi Aldım</span>
                    <span x-show="loading">İşleniyor...</span>
                </button>
            @elseif($order->status === 'picked_up')
                <button @click="updateStatus('on_way')"
                        x-bind:disabled="loading"
                        class="w-full py-4 bg-cyan-600 text-white rounded-xl font-semibold text-lg disabled:opacity-50 touch-active">
                    <span x-show="!loading">🚗 Yola Çıktım</span>
                    <span x-show="loading">İşleniyor...</span>
                </button>
            @elseif($order->status === 'on_way')
                <button @click="updateStatus('delivered')"
                        x-bind:disabled="loading"
                        class="w-full py-4 bg-green-600 text-white rounded-xl font-semibold text-lg disabled:opacity-50 touch-active">
                    <span x-show="!loading">✅ Teslim Ettim</span>
                    <span x-show="loading">İşleniyor...</span>
                </button>
            @endif
            
            <button @click="showCancelConfirm = true" 
                    class="w-full py-3 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 rounded-xl font-medium touch-active">
                Siparişi İptal Et
            </button>
        </div>
        @endif
    </div>

    <!-- Cancel Confirmation Modal -->
    <div x-show="showCancelConfirm" 
         x-transition
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @click.self="showCancelConfirm = false">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-sm">
            <h3 class="text-lg font-bold text-black dark:text-white mb-2">Siparişi İptal Et</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Bu siparişi iptal etmek istediğinizden emin misiniz?</p>
            
            <div class="flex space-x-3">
                <button @click="showCancelConfirm = false" class="flex-1 py-3 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium">
                    Vazgeç
                </button>
                <button @click="updateStatus('cancelled')" class="flex-1 py-3 bg-red-600 text-white rounded-xl font-medium">
                    İptal Et
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
function orderDetail() {
    return {
        loading: false,
        showCancelConfirm: false,
        
        async updateStatus(status) {
            this.loading = true;
            this.showCancelConfirm = false;
            
            try {
                const response = await fetch('{{ route("kurye.order.updateStatus", $order) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (status === 'delivered' || status === 'cancelled') {
                        window.location.href = '{{ route("kurye.orders") }}';
                    } else {
                        location.reload();
                    }
                } else {
                    alert(data.message || 'Bir hata oluştu');
                }
            } catch (error) {
                alert('Bağlantı hatası');
            } finally {
                this.loading = false;
            }
        },
        
        init() {
            // Initialize map
            @if($order->lat && $order->lng)
            const map = L.map('orderMap').setView([{{ $order->lat }}, {{ $order->lng }}], 15);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);
            
            L.marker([{{ $order->lat }}, {{ $order->lng }}]).addTo(map)
                .bindPopup('{{ $order->customer_address }}');
            @endif
        }
    }
}
</script>
@endpush
@endsection

