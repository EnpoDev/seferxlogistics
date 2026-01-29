@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn">
    <div class="mb-6">
        <a href="{{ route('admin.kuryeler.index') }}" class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white mb-4">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kuryelere Dön
        </a>
        <h1 class="text-2xl font-bold text-black dark:text-white">{{ $courier->name }}</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ $courier->phone }}</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Teslimat</p>
            <p class="text-2xl font-bold text-black dark:text-white">{{ number_format($stats['total_teslimat']) }}</p>
        </div>
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Aktif Sipariş</p>
            <p class="text-2xl font-bold text-black dark:text-white">{{ $stats['aktif_siparis'] }}</p>
        </div>
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Nakit Bakiye</p>
            <p class="text-2xl font-bold text-black dark:text-white">{{ number_format($stats['nakit_bakiye'], 2) }} TL</p>
        </div>
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Ort. Teslimat Süresi</p>
            <p class="text-2xl font-bold text-black dark:text-white">{{ $stats['ortalama_sure'] ?? 0 }} dk</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Kurye Bilgileri -->
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                <h2 class="font-semibold text-black dark:text-white">Kurye Bilgileri</h2>
            </div>
            <div class="p-6 space-y-4">
                <div><p class="text-sm text-gray-600 dark:text-gray-400">Ad Soyad</p><p class="font-medium text-black dark:text-white">{{ $courier->name }}</p></div>
                <div><p class="text-sm text-gray-600 dark:text-gray-400">Telefon</p><p class="font-medium text-black dark:text-white">{{ $courier->phone }}</p></div>
                <div><p class="text-sm text-gray-600 dark:text-gray-400">E-posta</p><p class="font-medium text-black dark:text-white">{{ $courier->email ?? '-' }}</p></div>
                <div><p class="text-sm text-gray-600 dark:text-gray-400">Durum</p>
                    @php $statusLabels = ['available' => 'Müsait', 'busy' => 'Meşgul', 'offline' => 'Çevrimdışı', 'on_break' => 'Molada']; @endphp
                    <span class="px-2 py-1 rounded text-xs bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">{{ $statusLabels[$courier->status] ?? $courier->status }}</span>
                </div>
                <div><p class="text-sm text-gray-600 dark:text-gray-400">Araç Plakası</p><p class="font-medium text-black dark:text-white">{{ $courier->vehicle_plate ?? '-' }}</p></div>
            </div>
        </div>

        <!-- Son Siparisler -->
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800">
                <h2 class="font-semibold text-black dark:text-white">Son Siparişler</h2>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($son_siparisler as $siparis)
                <div class="p-4 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-black dark:text-white">{{ $siparis->order_number }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $siparis->customer_name ?? 'N/A' }}</p>
                    </div>
                    <div class="text-right">
                        <span class="px-2 py-1 rounded text-xs bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">{{ ucfirst($siparis->status) }}</span>
                        <p class="text-xs text-gray-500 mt-1">{{ $siparis->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-gray-500">Henüz sipariş yok</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
