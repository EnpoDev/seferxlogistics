@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn" x-data="poolDashboard()" x-init="init()">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Sipariş Havuzu"
        subtitle="Bekleyen siparişleri yönet ve kuryelere ata"
    >
        <x-slot name="icon">
            <svg class="w-7 h-7 text-black dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
        </x-slot>

        <x-slot name="actions">
            <div class="flex items-center gap-3">
                {{-- Auto Refresh Toggle --}}
                <button @click="toggleAutoRefresh()"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl border transition-all"
                        :class="autoRefresh ? 'bg-green-50 border-green-200 text-green-700 dark:bg-green-900/30 dark:border-green-700 dark:text-green-400' : 'bg-gray-50 border-gray-200 text-gray-600 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400'">
                    <svg class="w-5 h-5" :class="{ 'animate-spin': autoRefresh && countdown <= 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-text="autoRefresh ? countdown + 's' : 'Otomatik'"></span>
                </button>

                {{-- Bulk Assign Button --}}
                <button @click="bulkAutoAssign()"
                        :disabled="poolOrders.length === 0 || bulkAssigning"
                        class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                    <svg class="w-5 h-5" :class="{ 'animate-spin': bulkAssigning }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span x-text="bulkAssigning ? 'Atanıyor...' : 'Tümünü Ata'"></span>
                </button>
            </div>
        </x-slot>
    </x-layout.page-header>

    {{-- Stats Cards --}}
    <x-layout.grid cols="1" mdCols="2" lgCols="4" gap="6" class="mb-6">
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Havuzda Bekleyen</p>
                    <p class="text-3xl font-bold text-black dark:text-white" x-text="stats.total_pool || {{ $poolStats['total_pool'] ?? 0 }}"></p>
                </div>
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ort. Bekleme</p>
                    <p class="text-3xl font-bold text-black dark:text-white">
                        <span x-text="stats.avg_wait_time || {{ $poolStats['avg_wait_time'] ?? 0 }}"></span>
                        <span class="text-lg font-normal text-gray-400">dk</span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Timeout Aşan</p>
                    <p class="text-3xl font-bold text-black dark:text-white" x-text="stats.timeout_count || {{ $poolStats['timeout_count'] ?? 0 }}"></p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aktif Kurye</p>
                    <p class="text-3xl font-bold text-black dark:text-white" x-text="stats.available_couriers || {{ $poolStats['available_couriers'] ?? 0 }}"></p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </x-layout.grid>

    {{-- Pool Orders Table --}}
    <x-ui.card>
        <x-layout.section title="Havuzdaki Siparişler" icon="order">
            <x-slot name="actions">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400" x-text="poolOrders.length + ' sipariş'"></span>
                    <button @click="refreshPool()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-5 h-5" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </x-slot>

            <template x-if="poolOrders.length === 0 && !loading">
                <div class="py-16 text-center">
                    <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Havuz Boş</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Şu anda bekleyen sipariş bulunmuyor</p>
                </div>
            </template>

            <template x-if="poolOrders.length > 0">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-800">
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Sipariş</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Müşteri</th>
                                <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Adres</th>
                                <th class="text-center py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Bekleme</th>
                                <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500 uppercase">Tutar</th>
                                <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500 uppercase">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="order in poolOrders" :key="order.id">
                                <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                                    :class="{ 'bg-orange-50 dark:bg-orange-900/10': order.is_timeout }">
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                                 :class="order.is_timeout ? 'bg-orange-100 dark:bg-orange-900/30' : 'bg-red-100 dark:bg-red-900/30'">
                                                <svg class="w-5 h-5" :class="order.is_timeout ? 'text-orange-600' : 'text-red-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <span class="font-bold text-black dark:text-white" x-text="'#' + order.order_number"></span>
                                                <p class="text-xs text-gray-500" x-text="order.created_at"></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            <span class="text-black dark:text-white" x-text="order.customer_name"></span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 max-w-xs">
                                        <p class="text-sm text-gray-600 dark:text-gray-400 truncate" x-text="order.customer_address"></p>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium"
                                              :class="order.is_timeout ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span x-text="order.waiting_minutes + ' dk'"></span>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <span class="font-bold text-green-600 dark:text-green-400" x-text="'₺' + parseFloat(order.total).toFixed(2)"></span>
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            {{-- Manuel Ata Butonu --}}
                                            <button @click="openAssignModal(order)"
                                                    class="p-2 text-blue-600 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 rounded-lg transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                                </svg>
                                            </button>
                                            {{-- Otomatik Ata Butonu --}}
                                            <button @click="autoAssignOrder(order.id)"
                                                    :disabled="assigning === order.id"
                                                    class="p-2 text-green-600 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:hover:bg-green-900/50 rounded-lg transition-colors disabled:opacity-50">
                                                <svg class="w-5 h-5" :class="{ 'animate-spin': assigning === order.id }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </x-layout.section>
    </x-ui.card>

    {{-- Kurye Atama Modal --}}
    <div x-show="showAssignModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
         @click.self="closeAssignModal()">
        <div class="bg-white dark:bg-[#181818] rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden"
             x-show="showAssignModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-black dark:text-white">Kurye Ata</h3>
                        <p class="text-sm text-gray-500" x-text="selectedOrder ? '#' + selectedOrder.order_number : ''"></p>
                    </div>
                    <button @click="closeAssignModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                {{-- Kurye Arama --}}
                <div class="mb-4">
                    <input type="text"
                           x-model="courierSearch"
                           placeholder="Kurye ara..."
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent">
                </div>

                {{-- Kurye Listesi --}}
                <div class="space-y-2">
                    <template x-for="courier in filteredCouriers" :key="courier.id">
                        <button @click="assignToCourier(courier.id)"
                                :disabled="assigningCourier === courier.id"
                                class="w-full p-4 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl hover:border-red-500 dark:hover:border-red-500 transition-all disabled:opacity-50 text-left">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                         :class="{
                                             'bg-green-100 dark:bg-green-900/30': courier.status === 'available',
                                             'bg-yellow-100 dark:bg-yellow-900/30': courier.status === 'busy',
                                             'bg-gray-100 dark:bg-gray-800': courier.status === 'offline'
                                         }">
                                        <span class="text-lg font-bold"
                                              :class="{
                                                  'text-green-600': courier.status === 'available',
                                                  'text-yellow-600': courier.status === 'busy',
                                                  'text-gray-600': courier.status === 'offline'
                                              }"
                                              x-text="courier.name.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-black dark:text-white" x-text="courier.name"></p>
                                        <p class="text-xs text-gray-500" x-text="courier.vehicle_plate"></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full"
                                          :class="{
                                              'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': courier.status === 'available',
                                              'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': courier.status === 'busy',
                                              'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400': courier.status === 'offline'
                                          }"
                                          x-text="courier.status_label"></span>
                                    <p class="text-xs text-gray-500 mt-1" x-text="courier.active_orders + ' aktif sipariş'"></p>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Audio for notifications --}}
    <audio id="poolNotification" preload="auto">
        <source src="/sounds/notification.mp3" type="audio/mpeg">
    </audio>
</div>

@php
    $poolOrdersJson = $poolOrders->map(function($o) {
        return [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'customer_name' => $o->customer_name,
            'customer_address' => $o->customer_address,
            'customer_phone' => $o->customer_phone,
            'total' => $o->total,
            'waiting_minutes' => $o->poolWaitingMinutes() ?? 0,
            'is_timeout' => ($o->poolWaitingMinutes() ?? 0) >= 5,
            'created_at' => $o->created_at->diffForHumans(),
        ];
    });

    $couriersJson = $availableCouriers->map(function($c) {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'phone' => $c->phone,
            'vehicle_plate' => $c->vehicle_plate,
            'status' => $c->status,
            'status_label' => $c->getStatusLabel(),
            'active_orders' => $c->active_orders_count ?? 0,
        ];
    });
@endphp

@push('scripts')
<script>
function poolDashboard() {
    return {
        poolOrders: @json($poolOrdersJson),
        stats: @json($poolStats),
        couriers: @json($couriersJson),

        loading: false,
        refreshing: false,
        autoRefresh: true,
        countdown: 15,
        intervalId: null,

        assigning: null,
        assigningCourier: null,
        bulkAssigning: false,

        showAssignModal: false,
        selectedOrder: null,
        courierSearch: '',

        lastOrderCount: 0,
        soundEnabled: true,

        init() {
            this.lastOrderCount = this.poolOrders.length;
            this.startAutoRefresh();

            // Visibility change handler
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stopAutoRefresh();
                } else if (this.autoRefresh) {
                    this.startAutoRefresh();
                }
            });
        },

        startAutoRefresh() {
            this.countdown = 15;
            this.intervalId = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    this.refreshPool();
                    this.countdown = 15;
                }
            }, 1000);
        },

        stopAutoRefresh() {
            if (this.intervalId) {
                clearInterval(this.intervalId);
                this.intervalId = null;
            }
        },

        toggleAutoRefresh() {
            this.autoRefresh = !this.autoRefresh;
            if (this.autoRefresh) {
                this.startAutoRefresh();
            } else {
                this.stopAutoRefresh();
            }
        },

        async refreshPool() {
            if (this.refreshing) return;
            this.refreshing = true;

            try {
                const response = await fetch('{{ route("bayi.havuz.istatistik") }}', {
                    headers: { 'Accept': 'application/json' }
                });

                if (response.ok) {
                    const data = await response.json();

                    // Check for new orders
                    if (data.pool_orders.length > this.lastOrderCount && this.soundEnabled) {
                        this.playNotificationSound();
                    }

                    this.poolOrders = data.pool_orders;
                    this.stats = data.stats;
                    this.couriers = data.couriers;
                    this.lastOrderCount = this.poolOrders.length;
                }
            } catch (error) {
                console.error('Refresh error:', error);
            } finally {
                this.refreshing = false;
            }
        },

        playNotificationSound() {
            const audio = document.getElementById('poolNotification');
            if (audio) {
                audio.currentTime = 0;
                audio.play().catch(e => console.log('Audio play failed:', e));
            }
        },

        openAssignModal(order) {
            this.selectedOrder = order;
            this.courierSearch = '';
            this.showAssignModal = true;
        },

        closeAssignModal() {
            this.showAssignModal = false;
            this.selectedOrder = null;
            this.courierSearch = '';
        },

        get filteredCouriers() {
            if (!this.courierSearch) return this.couriers;
            const search = this.courierSearch.toLowerCase();
            return this.couriers.filter(c =>
                c.name.toLowerCase().includes(search) ||
                (c.vehicle_plate && c.vehicle_plate.toLowerCase().includes(search))
            );
        },

        async assignToCourier(courierId) {
            if (!this.selectedOrder) return;
            this.assigningCourier = courierId;

            try {
                const response = await fetch(`/bayi/havuz/${this.selectedOrder.id}/ata`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ courier_id: courierId })
                });

                const data = await response.json();

                if (data.success) {
                    this.poolOrders = this.poolOrders.filter(o => o.id !== this.selectedOrder.id);
                    this.closeAssignModal();
                    this.showToast('Sipariş başarıyla atandı', 'success');
                } else {
                    this.showToast(data.message || 'Bir hata oluştu', 'error');
                }
            } catch (error) {
                this.showToast('Bağlantı hatası', 'error');
            } finally {
                this.assigningCourier = null;
            }
        },

        async autoAssignOrder(orderId) {
            this.assigning = orderId;

            try {
                const response = await fetch(`/bayi/havuz/${orderId}/otomatik-ata`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.poolOrders = this.poolOrders.filter(o => o.id !== orderId);
                    this.showToast(`${data.courier_name} kuryesine atandı`, 'success');
                } else {
                    this.showToast(data.message || 'Uygun kurye bulunamadı', 'error');
                }
            } catch (error) {
                this.showToast('Bağlantı hatası', 'error');
            } finally {
                this.assigning = null;
            }
        },

        async bulkAutoAssign() {
            if (this.poolOrders.length === 0) return;
            this.bulkAssigning = true;

            try {
                const response = await fetch('/bayi/havuz/toplu-ata', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    await this.refreshPool();
                    this.showToast(`${data.assigned_count} sipariş atandı`, 'success');
                } else {
                    this.showToast(data.message || 'Bir hata oluştu', 'error');
                }
            } catch (error) {
                this.showToast('Bağlantı hatası', 'error');
            } finally {
                this.bulkAssigning = false;
            }
        },

        showToast(message, type = 'info') {
            // Simple toast notification
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-xl shadow-lg z-50 text-white ${type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-blue-600'}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    }
}
</script>
@endpush
@endsection
