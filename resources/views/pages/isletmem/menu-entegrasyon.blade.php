@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-black dark:text-white">Menü Entegrasyonu</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Menünüzü harici platformlarla senkronize edin</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Yemeksepeti (Yakinda) -->
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 opacity-60">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-black dark:text-white">Yemeksepeti</h3>
                <span class="px-2 py-0.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full">Yakinda</span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Menünüzü Yemeksepeti ile senkronize edin</p>
            <button disabled class="w-full mt-4 px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-lg cursor-not-allowed">
                Yakinda Aktif Olacak
            </button>
        </div>

        <!-- Getir Yemek (Yakinda) -->
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 opacity-60">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-black dark:text-white">Getir Yemek</h3>
                <span class="px-2 py-0.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full">Yakinda</span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Menünüzü Getir Yemek ile senkronize edin</p>
            <button disabled class="w-full px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-lg cursor-not-allowed">
                Yakinda Aktif Olacak
            </button>
        </div>
    </div>
</div>
@endsection
