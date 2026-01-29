<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş #{{ $order->order_number }} - Takip</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .pulse-ring {
            animation: pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 1; }
            80%, 100% { transform: scale(2); opacity: 0; }
        }
        .step-completed { background-color: #22c55e; }
        .step-current { background-color: #f97316; animation: pulse 2s infinite; }
        .step-pending { background-color: #d1d5db; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen" x-data="trackingApp()">
    <div class="max-w-lg mx-auto pb-8">
        <!-- Header -->
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white p-6 rounded-b-3xl shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('tracking.index') }}" class="p-2 hover:bg-white/20 rounded-full transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <span class="text-sm font-medium bg-white/20 px-3 py-1 rounded-full">
                    #{{ $order->order_number }}
                </span>
            </div>

            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-white/20 rounded-full mb-3">
                    @if($order->status === 'delivered')
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    @elseif($order->status === 'on_delivery')
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path>
                        </svg>
                    @else
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    @endif
                </div>
                <h1 class="text-xl font-bold" x-text="statusLabel">{{ $order->getStatusLabel() }}</h1>
                <p class="text-white/80 text-sm mt-1" x-show="estimatedMinutes > 0">
                    Tahmini <span x-text="estimatedMinutes" class="font-semibold">{{ $tracking['tracking']['estimated_minutes'] ?? 0 }}</span> dakika
                </p>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="px-6 -mt-4">
            <div class="bg-white rounded-2xl shadow-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-500">İlerleme</span>
                    <span class="text-sm font-semibold text-orange-500" x-text="progress + '%'">{{ $tracking['tracking']['progress'] }}%</span>
                </div>
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-orange-400 to-orange-500 rounded-full transition-all duration-500"
                         :style="'width: ' + progress + '%'"></div>
                </div>
            </div>
        </div>

        <!-- Map -->
        @if($order->courier && $order->status === 'on_delivery')
        <div class="px-6 mt-4">
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div id="map" class="h-48 w-full"></div>
            </div>
        </div>
        @endif

        <!-- Tracking Steps -->
        <div class="px-6 mt-4">
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Sipariş Durumu</h2>
                <div class="relative">
                    @foreach($tracking['tracking']['steps'] as $index => $step)
                        <div class="flex items-start mb-6 last:mb-0">
                            <div class="relative">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center
                                    {{ $step['completed'] ? 'bg-green-500' : ($tracking['tracking']['current_step'] === $step['key'] ? 'bg-orange-500 animate-pulse' : 'bg-gray-200') }}">
                                    @if($step['completed'])
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    @elseif($tracking['tracking']['current_step'] === $step['key'])
                                        <div class="w-3 h-3 bg-white rounded-full"></div>
                                    @else
                                        <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                                    @endif
                                </div>
                                @if($index < count($tracking['tracking']['steps']) - 1)
                                    <div class="absolute top-10 left-1/2 w-0.5 h-8 -translate-x-1/2
                                        {{ $step['completed'] ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                                @endif
                            </div>
                            <div class="ml-4 flex-1">
                                <p class="font-medium text-gray-900">{{ $step['label'] }}</p>
                                @if($step['time'])
                                    <p class="text-sm text-gray-500">{{ $step['time']->format('H:i') }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Courier Info -->
        @if($tracking['courier'])
        <div class="px-6 mt-4">
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Kurye Bilgileri</h2>
                <div class="flex items-center">
                    <div class="w-14 h-14 bg-orange-100 rounded-full flex items-center justify-center">
                        @if($tracking['courier']['photo'])
                            <img src="{{ $tracking['courier']['photo'] }}" class="w-full h-full rounded-full object-cover" alt="">
                        @else
                            <svg class="w-7 h-7 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        @endif
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="font-semibold text-gray-900">{{ $tracking['courier']['name'] }}</p>
                        <div class="flex items-center text-sm text-gray-500">
                            <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            {{ number_format($tracking['courier']['rating'], 1) }}
                        </div>
                    </div>
                    <a href="tel:{{ $tracking['courier']['phone'] }}" class="p-3 bg-green-500 text-white rounded-full hover:bg-green-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Order Details -->
        <div class="px-6 mt-4">
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Sipariş Detayları</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Teslimat Adresi</span>
                        <span class="text-gray-900 text-right max-w-[200px]">{{ $order->customer_address }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Ödeme</span>
                        <span class="text-gray-900">{{ $order->getPaymentMethodLabel() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Toplam</span>
                        <span class="text-gray-900 font-semibold">{{ number_format($order->total, 2) }} TL</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Branch Info -->
        @if($tracking['branch'])
        <div class="px-6 mt-4">
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Restoran</h2>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">{{ $tracking['branch']['name'] }}</p>
                        <p class="text-sm text-gray-500">{{ $tracking['branch']['address'] }}</p>
                    </div>
                    @if($tracking['branch']['phone'])
                    <a href="tel:{{ $tracking['branch']['phone'] }}" class="p-3 bg-gray-100 text-gray-600 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div class="px-6 mt-8 text-center">
            <p class="text-sm text-gray-400">
                Takip Kodu: <span class="font-mono font-semibold">{{ $order->tracking_token }}</span>
            </p>
            <p class="text-xs text-gray-400 mt-2">
                Powered by SeferX Lojistik
            </p>
        </div>
    </div>

    <script>
        function trackingApp() {
            return {
                token: '{{ $order->tracking_token }}',
                status: '{{ $order->status }}',
                statusLabel: '{{ $order->getStatusLabel() }}',
                progress: {{ $tracking['tracking']['progress'] }},
                estimatedMinutes: {{ $tracking['tracking']['estimated_minutes'] ?? 0 }},
                courierLat: {{ $tracking['courier']['location']['lat'] ?? 'null' }},
                courierLng: {{ $tracking['courier']['location']['lng'] ?? 'null' }},
                map: null,
                courierMarker: null,

                init() {
                    @if($order->courier && $order->status === 'on_delivery')
                    this.initMap();
                    @endif

                    // 10 saniyede bir güncelle
                    if (this.status !== 'delivered' && this.status !== 'cancelled') {
                        setInterval(() => this.fetchUpdate(), 10000);
                    }
                },

                initMap() {
                    this.map = L.map('map').setView([{{ $order->lat }}, {{ $order->lng }}], 14);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(this.map);

                    // Teslimat noktası
                    L.marker([{{ $order->lat }}, {{ $order->lng }}], {
                        icon: L.divIcon({
                            className: 'custom-marker',
                            html: '<div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center shadow-lg"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg></div>',
                            iconSize: [32, 32],
                            iconAnchor: [16, 32]
                        })
                    }).addTo(this.map);

                    // Kurye konumu
                    if (this.courierLat && this.courierLng) {
                        this.courierMarker = L.marker([this.courierLat, this.courierLng], {
                            icon: L.divIcon({
                                className: 'courier-marker',
                                html: '<div class="relative"><div class="absolute w-12 h-12 bg-orange-400 rounded-full opacity-30 pulse-ring"></div><div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center shadow-lg"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg></div></div>',
                                iconSize: [32, 32],
                                iconAnchor: [16, 16]
                            })
                        }).addTo(this.map);

                        // Her iki noktayı da göster
                        const bounds = L.latLngBounds([
                            [{{ $order->lat }}, {{ $order->lng }}],
                            [this.courierLat, this.courierLng]
                        ]);
                        this.map.fitBounds(bounds, { padding: [50, 50] });
                    }
                },

                async fetchUpdate() {
                    try {
                        const response = await fetch(`/tracking/${this.token}/data`);
                        const data = await response.json();

                        this.status = data.order.status;
                        this.statusLabel = data.order.status_label;
                        this.progress = data.tracking.progress;
                        this.estimatedMinutes = data.tracking.estimated_minutes;

                        // Kurye konumu güncelle
                        if (data.courier && data.courier.location) {
                            this.courierLat = data.courier.location.lat;
                            this.courierLng = data.courier.location.lng;

                            if (this.courierMarker) {
                                this.courierMarker.setLatLng([this.courierLat, this.courierLng]);
                            }
                        }

                        // Teslim edildiyse sayfayı yenile
                        if (data.order.status === 'delivered') {
                            location.reload();
                        }
                    } catch (error) {
                        console.error('Güncelleme hatası:', error);
                    }
                }
            }
        }
    </script>
</body>
</html>
