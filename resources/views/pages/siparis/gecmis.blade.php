@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-black dark:text-white">Sipariş Geçmişi</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Tamamlanan tüm siparişler</p>
    </div>

    <!-- Filtreler -->
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-black dark:text-white mb-2">Başlangıç Tarihi</label>
                <input type="date" class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-black dark:text-white mb-2">Bitiş Tarihi</label>
                <input type="date" class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-black dark:text-white mb-2">Arama</label>
                <input type="text" placeholder="Sipariş No, Müşteri..." class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white placeholder-gray-500">
            </div>
            <div class="flex items-end">
                <button class="w-full px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                    Filtrele
                </button>
            </div>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Toplam Sipariş</p>
            <p class="text-3xl font-bold text-black dark:text-white">1,234</p>
        </div>
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Toplam Gelir</p>
            <p class="text-3xl font-bold text-black dark:text-white">₺45,670</p>
        </div>
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Ortalama Sepet</p>
            <p class="text-3xl font-bold text-black dark:text-white">₺125</p>
        </div>
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Ort. Teslimat</p>
            <p class="text-3xl font-bold text-black dark:text-white">28 dk</p>
        </div>
    </div>

    <!-- Sipariş Listesi -->
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">SİPARİŞ NO</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">MÜŞTERİ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">TUTAR</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">KURYE</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">TARİH</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">SÜRE</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                        <td class="px-6 py-4 text-sm text-black dark:text-white">#1245</td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">Ayşe Demir</td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">₺185.00</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">Mehmet Kaya</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">19 Kas 2025, 14:30</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">25 dk</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                        <td class="px-6 py-4 text-sm text-black dark:text-white">#1244</td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">Can Öztürk</td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">₺95.50</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">Ali Demir</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">19 Kas 2025, 13:15</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">32 dk</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                        <td class="px-6 py-4 text-sm text-black dark:text-white">#1243</td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">Zeynep Aydın</td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">₺140.00</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">Mehmet Kaya</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">19 Kas 2025, 12:45</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">28 dk</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="border-t border-gray-200 dark:border-gray-800 px-6 py-4 flex items-center justify-between">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Toplam 1,234 sipariş
            </div>
            <div class="flex gap-2">
                <button class="px-3 py-1 border border-gray-300 dark:border-gray-700 rounded text-sm text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">Önceki</button>
                <button class="px-3 py-1 bg-black dark:bg-white text-white dark:text-black rounded text-sm">1</button>
                <button class="px-3 py-1 border border-gray-300 dark:border-gray-700 rounded text-sm text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">2</button>
                <button class="px-3 py-1 border border-gray-300 dark:border-gray-700 rounded text-sm text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">3</button>
                <button class="px-3 py-1 border border-gray-300 dark:border-gray-700 rounded text-sm text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">Sonraki</button>
            </div>
        </div>
    </div>
</div>
@endsection
