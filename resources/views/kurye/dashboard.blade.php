@extends('layouts.kurye')

@section('content')
<div class="p-4 space-y-6 slide-up">
    <!-- Welcome Section -->
    <div class="rounded-2xl p-6 text-white" style="background: linear-gradient(to bottom right, #000000, #374151);">
        <div class="flex items-center space-x-4">
            <div class="w-14 h-14 bg-white/20 dark:bg-black/20 rounded-full flex items-center justify-center">
                @if($courier->photo_path)
                    <img src="{{ Storage::url($courier->photo_path) }}" alt="{{ $courier->name }}" class="w-14 h-14 rounded-full object-cover">
                @else
                    <span class="text-xl font-bold">{{ substr($courier->name, 0, 2) }}</span>
                @endif
            </div>
            <div class="flex-1">
                <h2 class="text-xl font-bold">Merhaba, {{ explode(' ', $courier->name)[0] }}!</h2>
                <p class="text-sm opacity-80">{{ now()->translatedFormat('d F Y, l') }}</p>
            </div>
        </div>
        
        <!-- Today's Stats -->
        <div class="grid grid-cols-2 gap-4 mt-6">
            <div class="bg-white/10 dark:bg-black/10 rounded-xl p-4">
                <p class="text-3xl font-bold">{{ $todayDelivered }}</p>
                <p class="text-sm opacity-80">Bugün Teslim</p>
            </div>
            <div class="bg-white/10 dark:bg-black/10 rounded-xl p-4">
                <p class="text-3xl font-bold">₺{{ number_format($todayEarnings, 0, ',', '.') }}</p>
                <p class="text-sm opacity-80">Bugün Kazanç</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('kurye.pool') }}" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col items-center space-y-2 card-interactive">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-black dark:text-white">Sipariş Al</span>
        </a>
        
        <a href="{{ route('kurye.orders') }}" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col items-center space-y-2 card-interactive">
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <span class="text-sm font-medium text-black dark:text-white">Siparişlerim</span>
        </a>
    </div>

    <!-- Active Orders -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold text-black dark:text-white">Aktif Siparişler</h3>
            @if($activeOrders->count() > 0)
                <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-medium rounded-full">
                    {{ $activeOrders->count() }} sipariş
                </span>
            @endif
        </div>
        
        @if($activeOrders->isEmpty())
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-8 text-center">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                </div>
                <p class="text-gray-500 dark:text-gray-400">Aktif sipariş bulunmuyor</p>
                <a href="{{ route('kurye.pool') }}" class="inline-block mt-4 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg text-sm font-medium">
                    Havuzdan Sipariş Al
                </a>
            </div>
        @else
            <div class="space-y-3">
                @foreach($activeOrders as $order)
                    <a href="{{ route('kurye.order.detail', $order) }}" class="block bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 card-interactive">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <span class="text-sm font-bold text-black dark:text-white">#{{ $order->order_number }}</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $order->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                {{ $order->display_status === 'assigned' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                {{ $order->display_status === 'picked_up' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : '' }}
                                {{ $order->display_status === 'on_way' ? 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400' : '' }}">
                                {{ $order->getStatusLabel() }}
                            </span>
                        </div>
                        
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-black dark:text-white truncate">{{ $order->customer_name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $order->customer_address }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-black dark:text-white">₺{{ number_format($order->total, 2, ',', '.') }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end mt-3 text-xs text-gray-400">
                            <span>Detayları gör</span>
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

