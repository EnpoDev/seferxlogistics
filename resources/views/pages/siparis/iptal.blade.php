@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-black dark:text-white">İptal Edilen Siparişler</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">İptal edilen ve reddedilen siparişler</p>
    </div>

    <!-- Filtreler -->
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-black dark:text-white mb-2">İptal Nedeni</label>
                <select class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    <option>Tümü</option>
                    <option>Müşteri İstedi</option>
                    <option>Ürün Yok</option>
                    <option>Teslimat Sorunu</option>
                    <option>Diğer</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-black dark:text-white mb-2">Tarih</label>
                <input type="date" class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-black dark:text-white mb-2">Arama</label>
                <input type="text" placeholder="Sipariş No..." class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white placeholder-gray-500">
            </div>
            <div class="flex items-end">
                <button class="w-full px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                    Filtrele
                </button>
            </div>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Toplam İptal</p>
            <p class="text-3xl font-bold text-black dark:text-white">87</p>
        </div>
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Bu Ay</p>
            <p class="text-3xl font-bold text-black dark:text-white">12</p>
        </div>
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">İptal Oranı</p>
            <p class="text-3xl font-bold text-black dark:text-white">2.8%</p>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">İPTAL NEDENİ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">İPTAL EDEN</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">TARİH</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                        <td class="px-6 py-4 text-sm text-black dark:text-white">#1250</td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">Emre Yıldız</td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">₺75.00</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">Müşteri İstedi</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">Müşteri</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">20 Kas 2025, 10:15</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                        <td class="px-6 py-4 text-sm text-black dark:text-white">#1248</td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">Selin Akar</td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">₺120.00</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">Ürün Yok</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">Restoran</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">19 Kas 2025, 19:30</td>
                    </tr>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                        <td class="px-6 py-4 text-sm text-black dark:text-white">#1242</td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">Murat Şahin</td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">₺165.00</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">Teslimat Sorunu</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">Restoran</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">18 Kas 2025, 21:00</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="border-t border-gray-200 dark:border-gray-800 px-6 py-4 flex items-center justify-between">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Toplam 87 iptal
            </div>
            <div class="flex gap-2">
                <button class="px-3 py-1 border border-gray-300 dark:border-gray-700 rounded text-sm text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">Önceki</button>
                <button class="px-3 py-1 bg-black dark:bg-white text-white dark:text-black rounded text-sm">1</button>
                <button class="px-3 py-1 border border-gray-300 dark:border-gray-700 rounded text-sm text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">2</button>
                <button class="px-3 py-1 border border-gray-300 dark:border-gray-700 rounded text-sm text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">Sonraki</button>
            </div>
        </div>
    </div>
</div>
@endsection
