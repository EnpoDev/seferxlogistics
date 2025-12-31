@extends('layouts.kurye')

@section('content')
<div class="p-4 space-y-4 slide-up">
    <h1 class="text-xl font-bold text-black dark:text-white">Geçmiş Siparişler</h1>

    @if($orders->isEmpty())
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-12 text-center">
            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Geçmiş Yok</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Henüz tamamlanmış sipariş bulunmuyor</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($orders as $order)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <span class="text-sm font-bold text-black dark:text-white">#{{ $order->order_number }}</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $order->updated_at->format('d.m.Y H:i') }}</p>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium rounded-full
                            {{ $order->status === 'delivered' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                            {{ $order->status === 'cancelled' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                            {{ $order->status === 'delivered' ? 'Teslim Edildi' : 'İptal Edildi' }}
                        </span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <span>{{ $order->customer_name }}</span>
                        </div>
                        <span class="text-sm font-bold text-black dark:text-white">₺{{ number_format($order->total, 2, ',', '.') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Pagination -->
        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection

