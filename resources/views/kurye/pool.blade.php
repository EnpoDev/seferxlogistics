@extends('layouts.kurye')

@section('content')
<div class="p-4 space-y-4 slide-up" x-data="poolPage()" x-init="init()">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-black dark:text-white">Sipariş Havuzu</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                <span x-text="orders.length"></span> sipariş bekliyor
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-400" x-show="autoRefresh">
                <span x-text="countdown"></span>s
            </span>
            <button @click="toggleAutoRefresh()" class="p-2 rounded-lg" :class="autoRefresh ? 'text-green-600 bg-green-50 dark:bg-green-900/20' : 'text-gray-400'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
            <button @click="refreshPool()" class="p-2 text-gray-600 dark:text-gray-400 touch-active">
                <svg class="w-5 h-5" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>
    </div>

    <template x-if="orders.length === 0 && !loading">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-12 text-center">
            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Havuz Boş</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Şu anda bekleyen sipariş bulunmuyor</p>
        </div>
    </template>

    <div class="space-y-3">
        <template x-for="order in orders" :key="order.id">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden transition-all duration-300"
                 :data-order-id="order.id"
                 :class="{
                     'border-orange-400 dark:border-orange-500': order.waiting_minutes >= 5,
                     'ring-2 ring-green-500 ring-opacity-50 animate-pulse-once': order.isNew,
                     'opacity-50 scale-95 pointer-events-none': accepting === order.id
                 }"
                 x-init="$nextTick(() => { if (order.isNew) setTimeout(() => order.isNew = false, 2000) })">
                <div class="p-4">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <span class="text-lg font-bold text-black dark:text-white" x-text="'#' + order.order_number"></span>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="order.created_at"></span>
                                <span class="px-1.5 py-0.5 text-xs rounded-full"
                                      :class="order.waiting_minutes >= 5 ? 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                                      x-text="order.waiting_minutes + ' dk'"></span>
                            </div>
                        </div>
                        <span class="text-lg font-bold text-green-600 dark:text-green-400" x-text="'₺' + parseFloat(order.total).toFixed(2).replace('.', ',')"></span>
                    </div>

                    <div class="space-y-2 mb-4">
                        <div class="flex items-center space-x-2 text-sm">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span class="text-black dark:text-white" x-text="order.customer_name"></span>
                        </div>
                        <div class="flex items-start space-x-2 text-sm">
                            <svg class="w-4 h-4 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            </svg>
                            <span class="text-gray-600 dark:text-gray-400 line-clamp-2" x-text="order.customer_address"></span>
                        </div>
                        <div class="flex items-center space-x-2 text-sm" x-show="order.distance_km">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            <span class="text-blue-600 dark:text-blue-400 font-medium" x-text="order.distance_km + ' km uzaklıkta'"></span>
                        </div>
                    </div>

                    <button @click="acceptOrder(order.id)"
                            :disabled="accepting === order.id"
                            class="w-full py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold disabled:opacity-50 touch-active transition-colors">
                        <span x-show="accepting !== order.id">Siparişi Al</span>
                        <span x-show="accepting === order.id" class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Alınıyor...
                        </span>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>

@php
$ordersData = $poolOrders->map(fn($o) => [
    'id' => $o->id,
    'order_number' => $o->order_number,
    'customer_name' => $o->customer_name,
    'customer_address' => $o->customer_address,
    'total' => $o->total,
    'waiting_minutes' => $o->poolWaitingMinutes() ?? 0,
    'distance_km' => $o->distance_km ?? null,
    'created_at' => $o->created_at->diffForHumans(),
])->toArray();
@endphp

@push('styles')
<style>
    @keyframes pulse-once {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    .animate-pulse-once {
        animation: pulse-once 0.5s ease-in-out 3;
    }
</style>
@endpush

@push('scripts')
<script>
function poolPage() {
    return {
        orders: @json($ordersData),
        refreshing: false,
        accepting: null,
        acceptedOrderIds: new Set(), // Track orders that were taken
        loading: false,
        autoRefresh: true,
        countdown: 10,
        intervalId: null,
        lastOrderCount: @json(count($ordersData)),

        init() {
            this.startAutoRefresh();
            this.setupRealtimeListeners();

            // Visibility change handler
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stopAutoRefresh();
                } else if (this.autoRefresh) {
                    this.startAutoRefresh();
                    // Refresh immediately when returning to page
                    this.refreshPool();
                }
            });
        },

        setupRealtimeListeners() {
            // Listen for pool order assignments via Echo/Pusher
            if (typeof window.Echo !== 'undefined') {
                window.Echo.channel('pool')
                    .listen('.pool.order.assigned', (data) => {
                        console.log('Pool order assigned:', data);
                        // Remove order from list with animation
                        this.removeOrderWithAnimation(data.order.id, 'taken');
                    })
                    .listen('.pool.order.added', (data) => {
                        console.log('New pool order:', data);
                        this.addNewOrder(data);
                    });
            }
        },

        removeOrderWithAnimation(orderId, reason = 'removed') {
            const orderEl = document.querySelector(`[data-order-id="${orderId}"]`);
            if (orderEl) {
                orderEl.classList.add('opacity-50', 'scale-95', 'pointer-events-none');
                orderEl.style.transition = 'all 0.3s ease-out';

                setTimeout(() => {
                    this.orders = this.orders.filter(o => o.id !== orderId);
                }, 300);
            } else {
                this.orders = this.orders.filter(o => o.id !== orderId);
            }

            if (reason === 'taken') {
                this.acceptedOrderIds.add(orderId);
            }
        },

        addNewOrder(orderData) {
            // Don't add if already in list
            if (this.orders.some(o => o.id === orderData.id)) return;

            // Add with "isNew" flag for animation
            const order = {
                id: orderData.id,
                order_number: orderData.order_number,
                customer_name: orderData.customer_name,
                customer_address: orderData.customer_address,
                total: orderData.total,
                waiting_minutes: 0,
                distance_km: orderData.distance_km || null,
                created_at: 'Az önce',
                isNew: true
            };
            this.orders.unshift(order);

            // Notify
            this.playNotificationSound();
            this.vibrate();
        },

        playNotificationSound() {
            // Create a simple beep sound using Web Audio API
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.3);
            } catch (e) {
                console.log('Audio not available');
            }
        },

        vibrate() {
            if ('vibrate' in navigator) {
                navigator.vibrate([100, 50, 100]);
            }
        },

        startAutoRefresh() {
            this.countdown = 10;
            this.intervalId = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    this.refreshPool();
                    this.countdown = 10;
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
                const response = await fetch('/kurye/havuz', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        const currentOrderIds = this.orders.map(o => o.id);
                        const newOrderIds = data.orders.map(o => o.id);

                        // Find new orders that weren't in our list
                        const newOrders = data.orders.filter(o => !currentOrderIds.includes(o.id));

                        // Mark new orders for highlight animation
                        const updatedOrders = data.orders.map(order => ({
                            ...order,
                            isNew: !currentOrderIds.includes(order.id)
                        }));

                        // Notify if there are new orders
                        if (newOrders.length > 0) {
                            this.playNotificationSound();
                            this.vibrate();
                        }

                        // Update orders array with new data
                        this.orders = updatedOrders;
                        this.lastOrderCount = updatedOrders.length;
                    }
                }
            } catch (error) {
                console.error('Refresh error:', error);
            } finally {
                this.refreshing = false;
            }
        },

        async acceptOrder(orderId) {
            // Double-click prevention
            if (this.accepting === orderId) return;
            if (this.acceptedOrderIds.has(orderId)) {
                this.showToast('Bu sipariş zaten alındı', 'warning');
                return;
            }

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
                    // Mark as accepted
                    this.acceptedOrderIds.add(orderId);

                    // Remove from local list with animation
                    this.removeOrderWithAnimation(orderId, 'accepted');

                    this.showToast('Sipariş alındı!', 'success');

                    // Navigate after short delay
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 500);
                } else {
                    // Handle different error codes
                    this.handleAcceptError(orderId, data);
                }
            } catch (error) {
                console.error('Accept error:', error);
                this.showToast('Bağlantı hatası', 'error');
                this.accepting = null;
            }
        },

        handleAcceptError(orderId, data) {
            switch (data.code) {
                case 'ORDER_TAKEN':
                    // Order was taken by another courier - remove from list
                    this.removeOrderWithAnimation(orderId, 'taken');
                    this.showToast(data.message, 'warning');
                    break;

                case 'LIMIT_REACHED':
                    this.showToast(data.message, 'error');
                    break;

                case 'LOCK_FAILED':
                    this.showToast('Lütfen tekrar deneyin', 'info');
                    break;

                case 'NOT_ON_SHIFT':
                    this.showToast(data.message, 'error');
                    break;

                default:
                    this.showToast(data.message || 'Bir hata oluştu', 'error');
            }

            this.accepting = null;
        },

        showToast(message, type = 'info') {
            // Use native toast if available, otherwise alert
            if (typeof window.showToast === 'function') {
                window.showToast(message, type);
            } else {
                // Create simple toast
                const colors = {
                    success: 'bg-green-600',
                    error: 'bg-red-600',
                    warning: 'bg-orange-600',
                    info: 'bg-blue-600'
                };
                const toast = document.createElement('div');
                toast.className = `fixed bottom-20 left-1/2 transform -translate-x-1/2 ${colors[type] || colors.info} text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-opacity`;
                toast.textContent = message;
                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }
        }
    }
}
</script>
@endpush
@endsection

