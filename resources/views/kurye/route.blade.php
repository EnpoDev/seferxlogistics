@extends('layouts.kurye')

@section('content')
<div class="slide-up" x-data="routeApp()">
    <!-- Map Section -->
    <div class="relative h-64 bg-gray-200 dark:bg-gray-800">
        <div id="routeMap" class="w-full h-full"></div>

        <!-- Back Button -->
        <a href="{{ route('kurye.dashboard') }}" class="absolute top-4 left-4 w-10 h-10 bg-white dark:bg-black rounded-full flex items-center justify-center shadow-lg z-20">
            <svg class="w-5 h-5 text-black dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>

        <!-- Start Navigation Button -->
        <a href="{{ $optimizedOrders->count() > 0 ? 'https://www.google.com/maps/dir/' . ($startPoint ? $startPoint['lat'] . ',' . $startPoint['lng'] . '/' : '') . $optimizedOrders->map(fn($o) => $o->lat . ',' . $o->lng)->implode('/') : '#' }}"
           target="_blank"
           class="absolute bottom-4 right-4 px-4 py-2 bg-blue-500 text-white rounded-full flex items-center space-x-2 shadow-lg z-20">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            <span class="font-medium">Rotayı Başlat</span>
        </a>
    </div>

    <!-- Route Summary -->
    <div class="p-4 space-y-4 -mt-6 relative z-10">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-black dark:text-white">Optimize Edilmiş Rota</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $optimizedOrders->count() }} sipariş</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($totalDistance, 1) }} km</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Toplam mesafe</p>
                </div>
            </div>
        </div>

        <!-- Route Steps -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">Teslimat Sırası</h3>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800" x-ref="sortableList">
                @foreach($optimizedOrders as $index => $order)
                <div class="p-4 flex items-start gap-4 cursor-move" data-order-id="{{ $order->id }}">
                    <!-- Sequence Number -->
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm">
                            {{ $index + 1 }}
                        </div>
                        @if($index < $optimizedOrders->count() - 1)
                        <div class="w-0.5 h-12 bg-blue-200 dark:bg-blue-800 ml-4 mt-1"></div>
                        @endif
                    </div>

                    <!-- Order Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <p class="font-medium text-black dark:text-white">#{{ $order->order_number }}</p>
                            @if($order->estimated_arrival)
                            <span class="text-sm text-blue-600 dark:text-blue-400 font-medium">
                                ~{{ $order->estimated_arrival->format('H:i') }}
                            </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $order->customer_name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $order->customer_address }}</p>

                        <div class="flex items-center gap-4 mt-2">
                            @if($order->distance_from_previous)
                            <span class="text-xs text-gray-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                                {{ number_format($order->distance_from_previous, 1) }} km
                            </span>
                            @endif
                            @if($order->estimated_minutes)
                            <span class="text-xs text-gray-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $order->estimated_minutes }} dk
                            </span>
                            @endif
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex-shrink-0 flex flex-col gap-2">
                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $order->lat }},{{ $order->lng }}"
                           target="_blank"
                           class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </a>
                        <a href="tel:{{ $order->customer_phone }}"
                           class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Optimize Again Button -->
        <button @click="reoptimize" x-bind:disabled="loading"
                class="w-full py-3 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium flex items-center justify-center gap-2 disabled:opacity-50">
            <svg class="w-5 h-5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span x-text="loading ? 'Optimize Ediliyor...' : 'Rotayı Yeniden Optimize Et'"></span>
        </button>

        <!-- Quick Access to Orders -->
        <div class="pb-4">
            <a href="{{ route('kurye.orders') }}" class="block w-full py-3 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl font-medium text-center">
                Sipariş Listesine Dön
            </a>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .route-line {
        stroke: #3B82F6;
        stroke-width: 4;
        fill: none;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
function routeApp() {
    return {
        loading: false,
        map: null,

        init() {
            this.initMap();
        },

        initMap() {
            const routeData = @json($routeGeoJson);
            const orders = @json($optimizedOrders->map(fn($o) => [
                'lat' => $o->lat,
                'lng' => $o->lng,
                'order_number' => $o->order_number,
                'customer_name' => $o->customer_name,
                'sequence' => $o->sequence ?? 0
            ])->values());

            if (orders.length === 0) return;

            // Calculate bounds
            const lats = orders.map(o => o.lat).filter(l => l);
            const lngs = orders.map(o => o.lng).filter(l => l);

            if (lats.length === 0) return;

            const centerLat = (Math.min(...lats) + Math.max(...lats)) / 2;
            const centerLng = (Math.min(...lngs) + Math.max(...lngs)) / 2;

            this.map = L.map('routeMap').setView([centerLat, centerLng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(this.map);

            // Draw route line
            if (routeData.geometry.coordinates.length > 1) {
                const coordinates = routeData.geometry.coordinates.map(c => [c[1], c[0]]);
                L.polyline(coordinates, {
                    color: '#3B82F6',
                    weight: 4,
                    opacity: 0.8
                }).addTo(this.map);
            }

            // Add markers
            orders.forEach((order, index) => {
                if (!order.lat || !order.lng) return;

                const icon = L.divIcon({
                    className: 'custom-marker',
                    html: `<div style="background: #3B82F6; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">${index + 1}</div>`,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14]
                });

                L.marker([order.lat, order.lng], { icon })
                    .addTo(this.map)
                    .bindPopup(`<strong>#${order.order_number}</strong><br>${order.customer_name}`);
            });

            // Fit bounds
            const bounds = L.latLngBounds(orders.filter(o => o.lat && o.lng).map(o => [o.lat, o.lng]));
            this.map.fitBounds(bounds, { padding: [30, 30] });

            // Add courier starting position if available
            @if($startPoint)
            const courierIcon = L.divIcon({
                className: 'courier-marker',
                html: `<div style="background: #10B981; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg>
                </div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });
            L.marker([{{ $startPoint['lat'] }}, {{ $startPoint['lng'] }}], { icon: courierIcon })
                .addTo(this.map)
                .bindPopup('<strong>Konumunuz</strong>');
            @endif
        },

        async reoptimize() {
            this.loading = true;

            try {
                const response = await fetch('{{ route("kurye.route.optimize") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Bir hata oluştu');
                }
            } catch (error) {
                alert('Bağlantı hatası');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endpush
@endsection
