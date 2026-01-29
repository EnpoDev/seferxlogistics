@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn" x-data="bayiMapController()">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Kurye Haritası"
        subtitle="Kuryelerinizi ve siparişleri canlı olarak takip edin"
    >
        <x-slot name="icon">
            <x-ui.icon name="map" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <button @click="refreshData()"
                    :disabled="isLoading"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-black dark:bg-white text-white dark:text-black hover:bg-gray-800 dark:hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black dark:focus:ring-white disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                <template x-if="!isLoading">
                    <x-ui.icon name="refresh" class="w-4 h-4" />
                </template>
                <template x-if="isLoading">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </template>
                Yenile
            </button>
            <x-ui.button variant="secondary" @click="toggleFilters()" icon="filter">
                Filtreler
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Arama Kartları --}}
    <x-layout.grid cols="1" mdCols="2" gap="4" class="mb-6">
        {{-- Sipariş Ara --}}
        <x-ui.card class="relative">
            <x-form.form-group label="Sipariş Ara...">
                <x-form.search-input
                    x-model="orderSearch"
                    @input.debounce.300ms="searchOrders()"
                    @focus="showOrderResults = true"
                    @click.away="showOrderResults = false"
                    placeholder="Sipariş ID, müşteri adı..."
                />
            </x-form.form-group>

            {{-- Arama Sonuçları --}}
            <div x-show="showOrderResults && orderSearchResults.length > 0"
                 x-cloak
                 class="absolute left-4 right-4 top-full mt-2 bg-white dark:bg-[#181818] rounded-lg border border-gray-200 dark:border-gray-800 shadow-lg max-h-64 overflow-y-auto z-10">
                <template x-for="order in orderSearchResults" :key="order.id">
                    <div @click="selectOrder(order)"
                         class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900 cursor-pointer border-b border-gray-100 dark:border-gray-800 last:border-0">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-mono font-semibold text-sm text-black dark:text-white" x-text="order.order_number"></p>
                                <p class="text-xs text-gray-600 dark:text-gray-400" x-text="order.customer_name"></p>
                                <p class="text-xs text-gray-500 truncate max-w-xs" x-text="order.customer_address"></p>
                            </div>
                            <x-data.status-badge x-bind:status="order.status" entity="order" />
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>

        {{-- Kurye Ara --}}
        <x-ui.card class="relative">
            <x-form.form-group label="Kurye Ara...">
                <x-form.search-input
                    x-model="courierSearch"
                    @input.debounce.300ms="searchCouriers()"
                    @focus="showCourierResults = true"
                    @click.away="showCourierResults = false"
                    placeholder="Kurye adı, plaka..."
                />
            </x-form.form-group>

            {{-- Kurye Arama Sonuçları --}}
            <div x-show="showCourierResults && courierSearchResults.length > 0"
                 x-cloak
                 class="absolute left-4 right-4 top-full mt-2 bg-white dark:bg-[#181818] rounded-lg border border-gray-200 dark:border-gray-800 shadow-lg max-h-64 overflow-y-auto z-10">
                <template x-for="courier in courierSearchResults" :key="courier.id">
                    <div @click="selectCourier(courier)"
                         class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-900 cursor-pointer border-b border-gray-100 dark:border-gray-800 last:border-0">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold"
                                     :class="{
                                         'bg-green-500': courier.status === 'available',
                                         'bg-orange-500': courier.status === 'busy',
                                         'bg-yellow-500': courier.status === 'on_break',
                                         'bg-gray-400': courier.status === 'offline'
                                     }">
                                    <span x-text="courier.name.substring(0, 2).toUpperCase()"></span>
                                </div>
                                <div>
                                    <p class="font-semibold text-sm text-black dark:text-white" x-text="courier.name"></p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400" x-text="courier.vehicle_plate || '-'"></p>
                                    <p class="text-xs text-gray-500" x-text="courier.phone"></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <x-data.status-badge x-bind:status="courier.status" entity="courier" />
                                <p class="text-xs text-gray-400 mt-1" x-text="courier.active_orders_count + ' sipariş'"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>
    </x-layout.grid>

    {{-- Sipariş İstatistik Sekmeleri --}}
    <div class="flex flex-wrap items-center gap-2 mb-6">
        <button @click="activeTab = 'new'; filterOrdersByTab()"
                :class="activeTab === 'new' ? 'bg-yellow-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                class="flex items-center gap-2 px-4 py-2 rounded-xl font-medium transition-all">
            <span>Yeni</span>
            <span class="px-2 py-0.5 text-xs rounded-full"
                  :class="activeTab === 'new' ? 'bg-white/30' : 'bg-yellow-500 text-white'"
                  x-text="stats.pending"></span>
        </button>
        <button @click="activeTab = 'active'; filterOrdersByTab()"
                :class="activeTab === 'active' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                class="flex items-center gap-2 px-4 py-2 rounded-xl font-medium transition-all">
            <span>Aktif</span>
            <span class="px-2 py-0.5 text-xs rounded-full"
                  :class="activeTab === 'active' ? 'bg-white/30' : 'bg-blue-500 text-white'"
                  x-text="stats.active"></span>
        </button>
        <button @click="activeTab = 'pool'; filterOrdersByTab()"
                :class="activeTab === 'pool' ? 'bg-red-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                class="flex items-center gap-2 px-4 py-2 rounded-xl font-medium transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <span>Havuz</span>
            <span class="px-2 py-0.5 text-xs rounded-full"
                  :class="activeTab === 'pool' ? 'bg-white/30 animate-pulse' : 'bg-red-500 text-white'"
                  x-text="stats.pool"></span>
        </button>
        <button @click="activeTab = 'cancelled'; filterOrdersByTab()"
                :class="activeTab === 'cancelled' ? 'bg-gray-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                class="flex items-center gap-2 px-4 py-2 rounded-xl font-medium transition-all">
            <span>İptal</span>
            <span class="px-2 py-0.5 text-xs rounded-full"
                  :class="activeTab === 'cancelled' ? 'bg-white/30' : 'bg-gray-500 text-white'"
                  x-text="stats.cancelled"></span>
        </button>

        {{-- Pool Yönetimi Link --}}
        <a href="{{ route('bayi.havuz') }}"
           class="ml-auto flex items-center gap-2 px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-xl font-medium hover:bg-red-100 dark:hover:bg-red-900/30 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Havuz Yönetimi
        </a>
    </div>

    {{-- Pool Alert (Havuzda sipariş varsa) --}}
    <div x-show="stats.pool > 0 && activeTab !== 'pool'"
         x-transition
         class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center animate-pulse">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-red-700 dark:text-red-400">
                    <span x-text="stats.pool"></span> sipariş havuzda bekliyor
                </p>
                <p class="text-sm text-red-600 dark:text-red-500">Kurye ataması yapılmayı bekleyen siparişler var</p>
            </div>
        </div>
        <button @click="activeTab = 'pool'; filterOrdersByTab()"
                class="px-4 py-2 bg-red-600 text-white rounded-xl font-medium hover:bg-red-700 transition-all">
            Havuzu Gör
        </button>
    </div>

    {{-- Harita --}}
    <x-ui.card class="mb-6">
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
    </x-ui.card>

    {{-- Kurye Listesi --}}
    <div x-show="showCourierList" x-cloak>
        <x-ui.card>
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
        </x-ui.card>
    </div>

    {{-- Kurye Detay Modal --}}
    <x-ui.modal name="courierDetailModal" size="2xl">
        <div x-show="selectedCourier" class="space-y-6">
            {{-- Modal Header --}}
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl font-bold"
                     :class="{
                         'bg-green-500': selectedCourier?.status === 'available',
                         'bg-orange-500': selectedCourier?.status === 'busy',
                         'bg-yellow-500': selectedCourier?.status === 'on_break',
                         'bg-gray-400': selectedCourier?.status === 'offline'
                     }">
                    <span x-text="selectedCourier?.name?.substring(0, 2).toUpperCase()"></span>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-black dark:text-white" x-text="selectedCourier?.name"></h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400" x-text="selectedCourier?.status_label"></p>
                </div>
            </div>

            {{-- İletişim Bilgileri --}}
            <x-layout.grid cols="2" gap="4">
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Telefon</p>
                    <p class="font-medium text-black dark:text-white" x-text="selectedCourier?.phone || '-'"></p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Plaka</p>
                    <p class="font-medium text-black dark:text-white" x-text="selectedCourier?.vehicle_plate || '-'"></p>
                </div>
            </x-layout.grid>

            {{-- Günlük İstatistikler --}}
            <x-layout.grid cols="3" gap="4">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="selectedCourier?.active_orders_count || 0"></p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Aktif Sipariş</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="selectedCourier?.today_deliveries || 0"></p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Bugün Teslim</p>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 text-center">
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                        <x-data.money x-bind:amount="selectedCourier?.today_earnings || 0" />
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Bugün Kazanç</p>
                </div>
            </x-layout.grid>

            {{-- Vardiya Durumu --}}
            <div x-show="selectedCourier?.is_on_shift !== undefined">
                <h3 class="font-semibold text-black dark:text-white mb-3">Vardiya Durumu</h3>
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Şu an vardiyada mı?</span>
                        <x-ui.badge
                            x-bind:type="selectedCourier?.is_on_shift ? 'success' : 'danger'"
                            x-text="selectedCourier?.is_on_shift ? 'Evet' : 'Hayır'"
                        />
                    </div>
                </div>
            </div>

            {{-- Aktif Siparişler --}}
            <div x-show="selectedCourier?.active_orders && selectedCourier.active_orders.length > 0">
                <h3 class="font-semibold text-black dark:text-white mb-3">Aktif Siparişler</h3>
                <div class="space-y-2">
                    <template x-for="order in selectedCourier?.active_orders || []" :key="order.id">
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-mono font-semibold text-black dark:text-white" x-text="order.order_number"></span>
                                <x-data.status-badge x-bind:status="order.status" entity="order" />
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400" x-text="order.customer_name"></p>
                            <p class="text-xs text-gray-500 mt-1" x-text="order.customer_address"></p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs text-gray-400" x-text="order.created_at"></span>
                                <span class="font-semibold text-black dark:text-white">
                                    <x-data.money x-bind:amount="parseFloat(order.total)" />
                                </span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </x-ui.modal>
</div>

@push('scripts')
<script>
function bayiMapController() {
    return {
        mapManager: null,
        activeTab: 'new',
        orderSearch: '',
        courierSearch: '',
        orderSearchResults: [],
        courierSearchResults: [],
        showOrderResults: false,
        showCourierResults: false,
        isLoading: false,
        showCourierList: false,
        showFilters: false,
        allOrders: [],
        poolOrders: [],
        selectedCourier: null,
        refreshInterval: null,
        lastPoolCount: {{ $poolOrders }},
        stats: {
            pending: {{ $newOrders }},
            active: {{ $activeOrders }},
            pool: {{ $poolOrders }},
            cancelled: {{ $cancelledOrders }}
        },

        init() {
            // CourierMapManager yüklenene kadar bekle
            const initializeMap = () => {
                const mapEl = document.getElementById('courier-map');

                if (window.CourierMapManager && mapEl) {
                    if (window.courierMap && window.courierMap.map) {
                        this.mapManager = window.courierMap;
                    } else {
                        this.mapManager = new CourierMapManager('courier-map');
                        this.mapManager.init();
                    }

                    if (mapEl.dataset.couriers) {
                        try {
                            const couriers = JSON.parse(mapEl.dataset.couriers);
                            this.mapManager.setCouriers(couriers.filter(c => c.lat && c.lng));
                        } catch (e) {
                            console.error('Error loading couriers:', e);
                        }
                    }

                    this.loadOrders();
                } else {
                    // CourierMapManager henüz yüklenmedi, tekrar dene
                    setTimeout(initializeMap, 100);
                }
            };

            this.$nextTick(() => {
                initializeMap();
            });

            this.$watch('activeTab', () => {
                this.filterOrdersByTab();
            });

            window.addEventListener('courier-clicked', (event) => {
                this.showCourierDetails(event.detail.courierId);
            });

            this.startPolling();

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stopPolling();
                } else {
                    this.startPolling();
                }
            });
        },

        startPolling() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
            this.refreshInterval = setInterval(() => {
                this.refreshStats();
            }, 5000);
        },

        stopPolling() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
            }
        },

        async refreshStats() {
            try {
                const response = await fetch('/api/map-data', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.stats) {
                        this.stats = data.stats;
                    }
                }
            } catch (error) {
                console.error('Error refreshing stats:', error);
            }
        },

        destroy() {
            this.stopPolling();
            if (this.mapManager) {
                this.mapManager.destroy();
                this.mapManager = null;
            }
        },

        async refreshData() {
            this.isLoading = true;
            try {
                const response = await fetch('/api/map-data', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.allOrders = data.orders || [];
                    this.poolOrders = data.pool_orders || [];

                    if (this.mapManager) {
                        this.mapManager.setCouriers(data.couriers || []);

                        // Pool siparişlerini özel marker ile göster
                        if (this.mapManager.setPoolOrders) {
                            this.mapManager.setPoolOrders(this.poolOrders);
                        }

                        this.filterOrdersByTab();
                    }

                    // Yeni pool siparişi geldi mi kontrol et
                    if (data.stats && data.stats.pool > this.lastPoolCount) {
                        this.playPoolNotification();
                    }
                    this.lastPoolCount = data.stats?.pool || 0;

                    this.stats = data.stats || this.stats;
                }
            } catch (error) {
                console.error('Error refreshing data:', error);
            } finally {
                this.isLoading = false;
            }
        },

        playPoolNotification() {
            // Ses bildirimi
            const audio = new Audio('/sounds/notification.mp3');
            audio.play().catch(e => console.log('Audio play failed:', e));
        },

        async searchOrders() {
            if (!this.orderSearch.trim()) {
                this.orderSearchResults = [];
                this.showOrderResults = false;
                this.refreshData();
                return;
            }

            try {
                const response = await fetch(`/api/orders/search?q=${encodeURIComponent(this.orderSearch)}&status=active`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const orders = await response.json();
                    this.orderSearchResults = orders;
                    this.showOrderResults = true;
                }
            } catch (error) {
                console.error('Error searching orders:', error);
            }
        },

        selectOrder(order) {
            this.showOrderResults = false;
            if (this.mapManager && order.lat && order.lng) {
                this.mapManager.setOrders([order]);
                this.mapManager.focusOn(order.lat, order.lng, 16);
            }
        },

        clearOrderSearch() {
            this.orderSearch = '';
            this.orderSearchResults = [];
            this.showOrderResults = false;
            this.refreshData();
        },

        async searchCouriers() {
            if (!this.courierSearch.trim()) {
                this.courierSearchResults = [];
                this.showCourierResults = false;
                this.refreshData();
                return;
            }

            try {
                const response = await fetch(`/api/couriers/search?q=${encodeURIComponent(this.courierSearch)}`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const couriers = await response.json();
                    this.courierSearchResults = couriers;
                    this.showCourierResults = true;
                }
            } catch (error) {
                console.error('Error searching couriers:', error);
            }
        },

        selectCourier(courier) {
            this.showCourierResults = false;
            if (this.mapManager && courier.lat && courier.lng) {
                this.mapManager.setCouriers([courier]);
                this.mapManager.focusOn(courier.lat, courier.lng, 16);
            }
            this.showCourierDetails(courier.id);
        },

        clearCourierSearch() {
            this.courierSearch = '';
            this.courierSearchResults = [];
            this.showCourierResults = false;
            this.refreshData();
        },

        async loadOrders() {
            try {
                const response = await fetch('/api/map-data', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.allOrders = data.orders || [];
                    this.poolOrders = data.pool_orders || [];
                    this.stats = data.stats || this.stats;
                    console.log('Loaded orders:', this.allOrders.length, 'Pool:', this.poolOrders.length);
                    console.log('Order statuses:', this.allOrders.map(o => o.status));
                    this.filterOrdersByTab();
                }
            } catch (error) {
                console.error('Error loading orders:', error);
            }
        },

        filterRetryCount: 0,

        filterOrdersByTab() {
            if (!this.mapManager) {
                // mapManager henüz hazır değilse max 10 kez dene
                if (this.filterRetryCount < 10) {
                    this.filterRetryCount++;
                    setTimeout(() => this.filterOrdersByTab(), 200);
                }
                return;
            }
            this.filterRetryCount = 0;

            let filteredOrders = [];

            // Pool siparişlerini gizle/göster
            if (this.mapManager.clearPoolOrders) {
                this.mapManager.clearPoolOrders();
            }

            console.log('Filtering by tab:', this.activeTab, 'Total orders:', this.allOrders.length);

            switch(this.activeTab) {
                case 'new':
                    filteredOrders = this.allOrders.filter(o => o.status === 'pending');
                    break;
                case 'active':
                    filteredOrders = this.allOrders.filter(o =>
                        ['preparing', 'ready', 'assigned', 'picked_up', 'on_delivery', 'delivering'].includes(o.status)
                    );
                    break;
                case 'pool':
                    // Pool sekmesinde sadece pool siparişlerini göster (kırmızı marker'lar)
                    console.log('Pool orders to show:', this.poolOrders.length);
                    if (this.mapManager.setPoolOrders && this.poolOrders) {
                        this.mapManager.setPoolOrders(this.poolOrders);
                    }
                    // Normal siparişleri temizle
                    this.mapManager.setOrders([]);
                    return;
                case 'cancelled':
                    this.loadCancelledOrders();
                    return;
                default:
                    filteredOrders = this.allOrders;
            }

            console.log('Filtered orders to display:', filteredOrders.length);
            this.mapManager.setOrders(filteredOrders);
        },

        async loadCancelledOrders() {
            try {
                const response = await fetch('/api/orders/search?status=cancelled', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const orders = await response.json();
                    if (this.mapManager) {
                        this.mapManager.setOrders(orders);
                    }
                }
            } catch (error) {
                console.error('Error loading cancelled orders:', error);
            }
        },

        async showCourierDetails(courierId) {
            try {
                const response = await fetch(`/api/couriers/${courierId}`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    this.selectedCourier = await response.json();
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'courierDetailModal' }));
                }
            } catch (error) {
                console.error('Error loading courier details:', error);
            }
        },

        focusOnCourier(courierId) {
            if (this.mapManager) {
                this.mapManager.focusOnCourier(courierId);
            }
            this.showCourierDetails(courierId);
        },

        toggleFilters() {
            this.showFilters = !this.showFilters;
        }
    }
}
</script>
@endpush
@endsection
