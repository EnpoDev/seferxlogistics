@extends('layouts.admin')

@section('content')
@php
    $statusColors = [
        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        'preparing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'ready' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        'on_delivery' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        'delivered' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    ];
    $statusLabels = [
        'pending' => 'Bekliyor',
        'preparing' => 'Hazırlanıyor',
        'ready' => 'Hazır',
        'on_delivery' => 'Yolda',
        'delivered' => 'Teslim Edildi',
        'cancelled' => 'İptal Edildi',
    ];
    $paymentLabels = [
        'cash' => 'Nakit',
        'card' => 'Kredi Kartı',
        'online' => 'Online',
    ];
@endphp
<div class="animate-fadeIn">
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.siparisler.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-black dark:text-white">{{ $order->order_number }}</h1>
                <span class="px-3 py-1 rounded-lg text-sm font-medium {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ $statusLabels[$order->status] ?? $order->status }}
                </span>
            </div>
            <p class="text-gray-600 dark:text-gray-400">{{ $order->created_at->format('d.m.Y H:i') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Ana Bilgiler -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Siparis Detay -->
            <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Siparis Bilgileri</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sube</p>
                        <p class="text-black dark:text-white font-medium">{{ $order->branch?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Kurye</p>
                        <p class="text-black dark:text-white font-medium">{{ $order->courier?->name ?? 'Atanmadi' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Odeme Yontemi</p>
                        <p class="text-black dark:text-white font-medium">{{ $paymentLabels[$order->payment_method] ?? $order->payment_method }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Odeme Durumu</p>
                        <p class="text-black dark:text-white font-medium">{{ $order->is_paid ? 'Odendi' : 'Odenmedi' }}</p>
                    </div>
                    @if($order->notes)
                    <div class="col-span-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Not</p>
                        <p class="text-black dark:text-white">{{ $order->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Urunler -->
            @if($order->items && $order->items->count() > 0)
            <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Urunler</h2>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($order->items as $item)
                    <div class="py-3 flex justify-between">
                        <div>
                            <p class="text-black dark:text-white font-medium">{{ $item->product_name ?? 'Urun' }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $item->quantity }} adet</p>
                        </div>
                        <p class="text-black dark:text-white font-medium">{{ number_format($item->total ?? ($item->price * $item->quantity), 2) }} TL</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Zaman Cizelgesi -->
            <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Zaman Cizelgesi</h2>
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <div>
                            <p class="text-black dark:text-white font-medium">Olusturuldu</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->created_at->format('d.m.Y H:i:s') }}</p>
                        </div>
                    </div>
                    @if($order->accepted_at)
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                        <div>
                            <p class="text-black dark:text-white font-medium">Onaylandi</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->accepted_at->format('d.m.Y H:i:s') }}</p>
                        </div>
                    </div>
                    @endif
                    @if($order->prepared_at)
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                        <div>
                            <p class="text-black dark:text-white font-medium">Hazirlandi</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->prepared_at->format('d.m.Y H:i:s') }}</p>
                        </div>
                    </div>
                    @endif
                    @if($order->courier_assigned_at)
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                        <div>
                            <p class="text-black dark:text-white font-medium">Kurye Atandi</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->courier_assigned_at->format('d.m.Y H:i:s') }}</p>
                        </div>
                    </div>
                    @endif
                    @if($order->picked_up_at)
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                        <div>
                            <p class="text-black dark:text-white font-medium">Kurye Aldi</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->picked_up_at->format('d.m.Y H:i:s') }}</p>
                        </div>
                    </div>
                    @endif
                    @if($order->delivered_at)
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <div>
                            <p class="text-black dark:text-white font-medium">Teslim Edildi</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->delivered_at->format('d.m.Y H:i:s') }}</p>
                        </div>
                    </div>
                    @endif
                    @if($order->cancelled_at)
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <div>
                            <p class="text-black dark:text-white font-medium">Iptal Edildi</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->cancelled_at->format('d.m.Y H:i:s') }}</p>
                            @if($order->cancel_reason)
                            <p class="text-sm text-red-500">{{ $order->cancel_reason }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sag Panel -->
        <div class="space-y-6">
            <!-- Musteri Bilgileri -->
            <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Musteri Bilgileri</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Ad Soyad</p>
                        <p class="text-black dark:text-white font-medium">{{ $order->customer_name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Telefon</p>
                        <p class="text-black dark:text-white font-medium">{{ $order->customer_phone ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Adres</p>
                        <p class="text-black dark:text-white">{{ $order->customer_address ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Tutar Bilgileri -->
            <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Tutar Bilgileri</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <p class="text-gray-500 dark:text-gray-400">Ara Toplam</p>
                        <p class="text-black dark:text-white">{{ number_format($order->subtotal, 2) }} TL</p>
                    </div>
                    <div class="flex justify-between">
                        <p class="text-gray-500 dark:text-gray-400">Teslimat Ucreti</p>
                        <p class="text-black dark:text-white">{{ number_format($order->delivery_fee, 2) }} TL</p>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between">
                        <p class="text-black dark:text-white font-semibold">Toplam</p>
                        <p class="text-black dark:text-white font-semibold">{{ number_format($order->total, 2) }} TL</p>
                    </div>
                </div>
            </div>

            <!-- POD (Teslim Kaniti) -->
            @if($order->hasPod())
            <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Teslim Kaniti</h2>
                <img src="{{ $order->getPodPhotoUrl() }}" alt="Teslim Kaniti" class="w-full rounded-lg mb-3">
                @if($order->pod_timestamp)
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->pod_timestamp->format('d.m.Y H:i:s') }}</p>
                @endif
                @if($order->pod_note)
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">{{ $order->pod_note }}</p>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
