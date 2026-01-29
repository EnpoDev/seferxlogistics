<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Siparis #{{ $order->order_number }} - Musteri Portali</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @if($trackingData)
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endif
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('portal.dashboard') }}" class="p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="font-semibold text-gray-900">#{{ $order->order_number }}</h1>
                    <p class="text-sm text-gray-500">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                </div>
            </div>
            <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full
                @if($order->status === 'delivered') bg-green-100 text-green-700
                @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                @elseif($order->status === 'pending') bg-yellow-100 text-yellow-700
                @elseif($order->status === 'preparing') bg-blue-100 text-blue-700
                @elseif($order->status === 'ready') bg-purple-100 text-purple-700
                @elseif($order->status === 'on_way') bg-green-100 text-green-700
                @endif">
                @if($order->status === 'delivered') Teslim Edildi
                @elseif($order->status === 'cancelled') Iptal
                @elseif($order->status === 'pending') Beklemede
                @elseif($order->status === 'preparing') Hazirlaniyor
                @elseif($order->status === 'ready') Hazir
                @elseif($order->status === 'on_way') Yolda
                @endif
            </span>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-6 space-y-6" x-data="orderTracking()">
        @if($trackingData)
        <!-- Live Tracking -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 bg-green-50 border-b border-green-100">
                <h2 class="font-semibold text-green-800 flex items-center gap-2">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    Canli Takip
                </h2>
            </div>

            <!-- Map -->
            <div id="trackingMap" class="h-64 w-full"></div>

            <!-- Progress -->
            <div class="p-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        @foreach($trackingData['steps'] as $step)
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium
                                {{ $step['completed'] ? 'bg-green-500 text-white' : ($step['active'] ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-500') }}">
                                @if($step['completed'])
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </div>
                            @if(!$loop->last)
                                <div class="w-8 h-1 {{ $step['completed'] ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>

                @if($trackingData['eta'])
                <div class="text-center py-4 bg-blue-50 rounded-xl">
                    <p class="text-sm text-blue-700">Tahmini Teslimat</p>
                    <p class="text-2xl font-bold text-blue-900">{{ $trackingData['eta'] }}</p>
                    @if($trackingData['remaining_minutes'])
                    <p class="text-sm text-blue-600">{{ $trackingData['remaining_minutes'] }} dakika</p>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Order Info -->
        <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-4">
            <h3 class="font-semibold text-gray-900">Siparis Bilgileri</h3>

            <div class="space-y-3">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <div>
                        <p class="text-sm text-gray-500">Teslimat Adresi</p>
                        <p class="text-gray-900">{{ $order->customer_address ?? '-' }}</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                    <div>
                        <p class="text-sm text-gray-500">Odeme Yontemi</p>
                        <p class="text-gray-900">
                            @if($order->payment_method === 'cash') Nakit
                            @elseif($order->payment_method === 'credit_card') Kredi Karti
                            @else Online Odeme
                            @endif
                            @if($order->is_paid)
                                <span class="text-green-600 text-sm">(Odendi)</span>
                            @endif
                        </p>
                    </div>
                </div>

                @if($order->notes)
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                    </svg>
                    <div>
                        <p class="text-sm text-gray-500">Siparis Notu</p>
                        <p class="text-gray-900">{{ $order->notes }}</p>
                    </div>
                </div>
                @endif

                @if($order->courier)
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <div>
                        <p class="text-sm text-gray-500">Kurye</p>
                        <p class="text-gray-900">{{ $order->courier->name }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Order Items -->
        @if($order->items && $order->items->count() > 0)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">Siparis Icerigi</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($order->items as $item)
                <div class="px-4 py-3 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">{{ $item->product_name }}</p>
                        <p class="text-sm text-gray-500">x{{ $item->quantity }}</p>
                    </div>
                    <p class="font-medium text-gray-900">{{ number_format($item->total, 0) }} TL</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Price Summary -->
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="space-y-2">
                <div class="flex justify-between text-gray-600">
                    <span>Ara Toplam</span>
                    <span>{{ number_format($order->subtotal ?? $order->total - ($order->delivery_fee ?? 0), 0) }} TL</span>
                </div>
                @if($order->delivery_fee)
                <div class="flex justify-between text-gray-600">
                    <span>Teslimat Ucreti</span>
                    <span>{{ number_format($order->delivery_fee, 0) }} TL</span>
                </div>
                @endif
                <div class="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t border-gray-200">
                    <span>Toplam</span>
                    <span>{{ number_format($order->total, 0) }} TL</span>
                </div>
            </div>
        </div>
    </main>

    @if($trackingData)
    <script>
        function orderTracking() {
            return {
                map: null,
                markers: {},

                init() {
                    this.initMap();
                    this.startPolling();
                },

                initMap() {
                    const trackingData = @json($trackingData);

                    if (!trackingData.courier_location && !trackingData.delivery_lat) {
                        return;
                    }

                    const mapCenter = trackingData.courier_location
                        ? [trackingData.courier_location.lat, trackingData.courier_location.lng]
                        : [trackingData.delivery_lat, trackingData.delivery_lng];

                    this.map = L.map('trackingMap').setView(mapCenter, 14);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(this.map);

                    // Delivery marker
                    if (trackingData.delivery_lat && trackingData.delivery_lng) {
                        this.markers.delivery = L.marker([trackingData.delivery_lat, trackingData.delivery_lng], {
                            icon: L.divIcon({
                                className: 'custom-marker',
                                html: '<div class="w-8 h-8 bg-red-500 rounded-full border-4 border-white shadow-lg flex items-center justify-center"><svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg></div>',
                                iconSize: [32, 32],
                                iconAnchor: [16, 32]
                            })
                        }).addTo(this.map).bindPopup('Teslimat Adresi');
                    }

                    // Courier marker
                    if (trackingData.courier_location) {
                        this.markers.courier = L.marker([trackingData.courier_location.lat, trackingData.courier_location.lng], {
                            icon: L.divIcon({
                                className: 'custom-marker',
                                html: '<div class="w-10 h-10 bg-green-500 rounded-full border-4 border-white shadow-lg flex items-center justify-center animate-pulse"><svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg></div>',
                                iconSize: [40, 40],
                                iconAnchor: [20, 40]
                            })
                        }).addTo(this.map).bindPopup('Kurye');
                    }

                    // Fit bounds
                    if (Object.keys(this.markers).length > 1) {
                        const group = new L.featureGroup(Object.values(this.markers));
                        this.map.fitBounds(group.getBounds().pad(0.1));
                    }
                },

                startPolling() {
                    setInterval(() => {
                        this.fetchTrackingData();
                    }, 30000); // 30 saniyede bir guncelle
                },

                async fetchTrackingData() {
                    try {
                        const response = await fetch(`/portal/order/{{ $order->id }}/track`, {
                            headers: {
                                'X-Portal-Token': this.getToken()
                            }
                        });
                        const data = await response.json();

                        if (data.success && data.data.courier_location && this.markers.courier) {
                            this.markers.courier.setLatLng([
                                data.data.courier_location.lat,
                                data.data.courier_location.lng
                            ]);
                        }
                    } catch (error) {
                        console.error('Tracking update error:', error);
                    }
                },

                getToken() {
                    const match = document.cookie.match(/portal_token=([^;]+)/);
                    return match ? match[1] : '';
                }
            };
        }
    </script>
    @endif
</body>
</html>
