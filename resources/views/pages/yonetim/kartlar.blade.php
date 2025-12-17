@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Kayıtlı Kartlarım</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Ödeme yöntemlerinizi yönetin</p>
        </div>
        <button class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
            + Yeni Kart Ekle
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($cards as $card)
        <!-- Kart -->
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Kart Numarası</p>
                    <p class="text-lg font-semibold text-black dark:text-white">{{ $card['number'] }}</p>
                </div>
                @if($card['is_default'])
                <span class="px-2 py-1 text-xs bg-black dark:bg-white text-white dark:text-black rounded">Varsayılan</span>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Son Kullanma</p>
                    <p class="text-sm text-black dark:text-white">{{ $card['expiry'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Kart Sahibi</p>
                    <p class="text-sm text-black dark:text-white">{{ $card['holder'] }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 text-black dark:text-white rounded-lg hover:bg-gray-50 dark:hover:bg-gray-900 text-sm">
                    Düzenle
                </button>
                <button class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 text-black dark:text-white rounded-lg hover:bg-gray-50 dark:hover:bg-gray-900 text-sm">
                    Sil
                </button>
            </div>
        </div>
        @empty
        <!-- Kart Yok -->
        @endforelse

        <!-- Yeni Kart Ekle Alanı -->
        <div class="bg-white dark:bg-[#181818] border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-6 flex flex-col items-center justify-center min-h-[200px]">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Yeni ödeme yöntemi ekle</p>
            <button class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                Kart Ekle
            </button>
        </div>
    </div>
</div>
@endsection
