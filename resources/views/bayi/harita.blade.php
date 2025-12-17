<x-bayi-layout>
    <x-slot name="title">Harita - Bayi Paneli</x-slot>

    <div class="space-y-6" x-data="bayiMapController()">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Kurye Haritası</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Kuryelerinizi ve siparişleri canlı olarak takip edin</p>
            </div>
            <div class="flex items-center space-x-3">
                <button @click="refreshData()" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80 transition-opacity flex items-center gap-2">
                    <svg class="w-4 h-4" :class="{ 'animate-spin': isLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Yenile</span>
                </button>
                <button @click="toggleFilters()" class="px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Search Bars -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Sipariş Ara -->
            <div class="bg-white dark:bg-[#181818] rounded-lg border border-gray-200 dark:border-gray-800 p-4">
                <label class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 block">Sipariş Ara...</label>
                <div class="flex items-center space-x-2 px-3 py-2 bg-gray-100 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" 
                           x-model="orderSearch"
                           @input.debounce.300ms="searchOrders()"
                           placeholder="Sipariş ID, müşteri adı..."
                           class="bg-transparent border-0 focus:outline-none text-sm text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 w-full">
                </div>
            </div>

            <!-- Kurye Ara -->
            <div class="bg-white dark:bg-[#181818] rounded-lg border border-gray-200 dark:border-gray-800 p-4">
                <label class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2 block">Kurye Ara...</label>
                <div class="flex items-center space-x-2 px-3 py-2 bg-gray-100 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" 
                           x-model="courierSearch"
                           @input.debounce.300ms="searchCouriers()"
                           placeholder="Kurye adı, plaka..."
                           class="bg-transparent border-0 focus:outline-none text-sm text-black dark:text-white placeholder-gray-500 dark:placeholder-gray-400 w-full">
                </div>
            </div>
        </div>

        <!-- Map Card -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-6">
                <!-- Status Tabs -->
                <div class="flex items-center space-x-4 mb-4 border-b border-gray-200 dark:border-gray-800">
                    <button @click="activeTab = 'new'" 
                            :class="activeTab === 'new' ? 'text-black dark:text-white border-black dark:border-white' : 'text-gray-600 dark:text-gray-400 border-transparent'"
                            class="px-4 py-2 text-sm font-medium border-b-2 transition-colors">
                        Yeni <span class="ml-1 px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full text-xs" x-text="stats.pending"></span>
                    </button>
                    <button @click="activeTab = 'active'" 
                            :class="activeTab === 'active' ? 'text-black dark:text-white border-black dark:border-white' : 'text-gray-600 dark:text-gray-400 border-transparent'"
                            class="px-4 py-2 text-sm font-medium border-b-2 transition-colors">
                        Aktif <span class="ml-1 px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full text-xs" x-text="stats.active"></span>
                    </button>
                    <button @click="activeTab = 'pool'" 
                            :class="activeTab === 'pool' ? 'text-black dark:text-white border-black dark:border-white' : 'text-gray-600 dark:text-gray-400 border-transparent'"
                            class="px-4 py-2 text-sm font-medium border-b-2 transition-colors">
                        Havuz <span class="ml-1 px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-full text-xs" x-text="stats.pool"></span>
                    </button>
                    <button @click="activeTab = 'cancelled'" 
                            :class="activeTab === 'cancelled' ? 'text-black dark:text-white border-black dark:border-white' : 'text-gray-600 dark:text-gray-400 border-transparent'"
                            class="px-4 py-2 text-sm font-medium border-b-2 transition-colors">
                        İptal <span class="ml-1 px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-xs" x-text="stats.cancelled"></span>
                    </button>
                </div>

                <!-- Interactive Map -->
                @php
                    $courierData = $couriers->map(function($c) {
                        return [
                            'id' => $c->id,
                            'name' => $c->name,
                            'phone' => $c->phone,
                            'lat' => (float)$c->lat,
                            'lng' => (float)$c->lng,
                            'status' => $c->status,
                            'vehicle_plate' => $c->vehicle_plate,
                            'active_orders_count' => $c->active_orders_count,
                        ];
                    });
                @endphp
                <div id="courier-map" 
                     class="rounded-lg h-[600px] border border-gray-200 dark:border-gray-800 z-0"
                     data-couriers='@json($courierData)'
                     x-ref="map"></div>
            </div>
        </div>

        <!-- Courier List Sidebar (if needed) -->
        <div x-show="showCourierList" x-cloak class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-4">
            <h3 class="font-semibold text-black dark:text-white mb-3">Kuryeler</h3>
            <div class="space-y-2 max-h-64 overflow-y-auto">
                @foreach($couriers as $courier)
                <div class="flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-gray-900 rounded-lg cursor-pointer"
                     @click="focusOnCourier({{ $courier->id }})">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold
                            {{ $courier->status === 'available' ? 'bg-green-500' : ($courier->status === 'busy' ? 'bg-orange-500' : 'bg-gray-400') }}">
                            {{ strtoupper(substr($courier->name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">{{ $courier->name }}</p>
                            <p class="text-xs text-gray-500">{{ $courier->getStatusLabel() }}</p>
                        </div>
                    </div>
                    <span class="text-xs text-gray-400">{{ $courier->active_orders_count }} sipariş</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function bayiMapController() {
        return {
            mapManager: null,
            activeTab: 'new',
            orderSearch: '',
            courierSearch: '',
            isLoading: false,
            showCourierList: false,
            showFilters: false,
            stats: {
                pending: {{ $newOrders }},
                active: {{ $activeOrders }},
                pool: {{ $poolOrders }},
                cancelled: {{ $cancelledOrders }}
            },
            
            init() {
                this.$nextTick(() => {
                    if (window.CourierMapManager) {
                        this.mapManager = new CourierMapManager('courier-map');
                        this.mapManager.init();
                        
                        // Load initial data from data attributes
                        const mapEl = document.getElementById('courier-map');
                        if (mapEl?.dataset.couriers) {
                            try {
                                const couriers = JSON.parse(mapEl.dataset.couriers);
                                this.mapManager.setCouriers(couriers.filter(c => c.lat && c.lng));
                            } catch (e) {
                                console.error('Error loading couriers:', e);
                            }
                        }
                    }
                });
            },
            
            async refreshData() {
                this.isLoading = true;
                try {
                    const response = await fetch('/api/map-data', {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        }
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        if (this.mapManager) {
                            this.mapManager.setCouriers(data.couriers || []);
                            this.mapManager.setOrders(data.orders || []);
                        }
                        this.stats = data.stats || this.stats;
                    }
                } catch (error) {
                    console.error('Error refreshing data:', error);
                } finally {
                    this.isLoading = false;
                }
            },
            
            async searchOrders() {
                if (!this.orderSearch.trim()) {
                    this.refreshData();
                    return;
                }
                
                try {
                    const response = await fetch(`/api/orders/search?q=${encodeURIComponent(this.orderSearch)}&status=active`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        }
                    });
                    
                    if (response.ok) {
                        const orders = await response.json();
                        if (this.mapManager) {
                            this.mapManager.setOrders(orders);
                            
                            // Focus on first result if any
                            if (orders.length > 0 && orders[0].lat && orders[0].lng) {
                                this.mapManager.focusOn(orders[0].lat, orders[0].lng, 14);
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error searching orders:', error);
                }
            },
            
            async searchCouriers() {
                if (!this.courierSearch.trim()) {
                    this.refreshData();
                    return;
                }
                
                try {
                    const response = await fetch(`/api/couriers/search?q=${encodeURIComponent(this.courierSearch)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        }
                    });
                    
                    if (response.ok) {
                        const couriers = await response.json();
                        if (this.mapManager) {
                            this.mapManager.setCouriers(couriers);
                            
                            // Focus on first result if any
                            if (couriers.length > 0 && couriers[0].lat && couriers[0].lng) {
                                this.mapManager.focusOn(couriers[0].lat, couriers[0].lng, 14);
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error searching couriers:', error);
                }
            },
            
            focusOnCourier(courierId) {
                if (this.mapManager) {
                    this.mapManager.focusOnCourier(courierId);
                }
            },
            
            toggleFilters() {
                this.showFilters = !this.showFilters;
            }
        }
    }
    </script>
    @endpush
</x-bayi-layout>
