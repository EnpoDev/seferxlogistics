@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-black dark:text-white">Aboneliklerim</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Aktif aboneliklerinizi görüntüleyin</p>
    </div>

    <!-- Aktif Abonelik -->
    @if($subscription)
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-1">{{ $subscription['plan_name'] }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $subscription['period'] }} abonelik</p>
            </div>
            <span class="px-3 py-1 text-sm bg-black dark:bg-white text-white dark:text-black rounded">
                {{ ucfirst($subscription['status'] == 'active' ? 'Aktif' : $subscription['status']) }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Aylık Ücret</p>
                <p class="text-2xl font-bold text-black dark:text-white">₺{{ $subscription['price'] }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Sonraki Ödeme</p>
                <p class="text-lg font-semibold text-black dark:text-white">{{ $subscription['next_payment'] }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Başlangıç Tarihi</p>
                <p class="text-lg font-semibold text-black dark:text-white">{{ $subscription['start_date'] }}</p>
            </div>
        </div>

        <div class="flex gap-3">
            <button class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                Planı Yükselt
            </button>
            <button class="px-4 py-2 border border-gray-300 dark:border-gray-700 text-black dark:text-white rounded-lg hover:bg-gray-50 dark:hover:bg-gray-900">
                İptal Et
            </button>
        </div>
    </div>
    @else
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 mb-6 text-center">
        <p class="text-gray-600 dark:text-gray-400 mb-4">Aktif aboneliğiniz bulunmamaktadır.</p>
        <button class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
            Plan Seç
        </button>
    </div>
    @endif

    <!-- Özellikler -->
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Plan Özellikleri</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-5 h-5 border-2 border-black dark:border-white rounded-full flex items-center justify-center">
                    <div class="w-2 h-2 bg-black dark:bg-white rounded-full"></div>
                </div>
                <span class="text-sm text-black dark:text-white">Sınırsız Sipariş</span>
            </div>
            <div class="flex items-center space-x-3">
                <div class="w-5 h-5 border-2 border-black dark:border-white rounded-full flex items-center justify-center">
                    <div class="w-2 h-2 bg-black dark:bg-white rounded-full"></div>
                </div>
                <span class="text-sm text-black dark:text-white">5 Kullanıcı</span>
            </div>
            <div class="flex items-center space-x-3">
                <div class="w-5 h-5 border-2 border-black dark:border-white rounded-full flex items-center justify-center">
                    <div class="w-2 h-2 bg-black dark:bg-white rounded-full"></div>
                </div>
                <span class="text-sm text-black dark:text-white">Gelişmiş Raporlar</span>
            </div>
            <div class="flex items-center space-x-3">
                <div class="w-5 h-5 border-2 border-black dark:border-white rounded-full flex items-center justify-center">
                    <div class="w-2 h-2 bg-black dark:bg-white rounded-full"></div>
                </div>
                <span class="text-sm text-black dark:text-white">Öncelikli Destek</span>
            </div>
        </div>
    </div>
</div>
@endsection
