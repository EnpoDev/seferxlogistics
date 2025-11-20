@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Sipariş İstatistikleri</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Sipariş ve satış grafikleri</p>
        </div>
        <select class="px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
            <option>Son 7 Gün</option>
            <option>Son 30 Gün</option>
            <option>Bu Ay</option>
            <option>Geçen Ay</option>
        </select>
    </div>

    <!-- Özet Kartları -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Toplam Sipariş</p>
            <p class="text-3xl font-bold text-black dark:text-white mb-2">456</p>
            <p class="text-xs text-gray-600 dark:text-gray-400">+12.5% önceki döneme göre</p>
        </div>
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Toplam Gelir</p>
            <p class="text-3xl font-bold text-black dark:text-white mb-2">₺54,320</p>
            <p class="text-xs text-gray-600 dark:text-gray-400">+8.3% önceki döneme göre</p>
        </div>
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Ortalama Sepet</p>
            <p class="text-3xl font-bold text-black dark:text-white mb-2">₺119</p>
            <p class="text-xs text-gray-600 dark:text-gray-400">-2.1% önceki döneme göre</p>
        </div>
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Ort. Teslimat Süresi</p>
            <p class="text-3xl font-bold text-black dark:text-white mb-2">28 dk</p>
            <p class="text-xs text-gray-600 dark:text-gray-400">-3.4% önceki döneme göre</p>
        </div>
    </div>

    <!-- Grafikler -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Günlük Sipariş Grafiği -->
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Günlük Sipariş Sayısı</h3>
            <div class="h-64 flex items-end justify-between gap-2">
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-black dark:bg-white" style="height: 45%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">Pzt</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-black dark:bg-white" style="height: 65%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">Sal</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-black dark:bg-white" style="height: 55%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">Çar</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-black dark:bg-white" style="height: 75%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">Per</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-black dark:bg-white" style="height: 85%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">Cum</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-black dark:bg-white" style="height: 95%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">Cmt</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-black dark:bg-white" style="height: 100%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">Paz</span>
                </div>
            </div>
        </div>

        <!-- Sipariş Durumu Dağılımı -->
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Sipariş Durumu Dağılımı</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-black dark:text-white">Tamamlandı</span>
                        <span class="text-sm font-semibold text-black dark:text-white">412 (90.4%)</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2">
                        <div class="bg-black dark:bg-white h-2 rounded-full" style="width: 90.4%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-black dark:text-white">İptal Edildi</span>
                        <span class="text-sm font-semibold text-black dark:text-white">32 (7.0%)</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2">
                        <div class="bg-black dark:bg-white h-2 rounded-full" style="width: 7%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-black dark:text-white">İade Edildi</span>
                        <span class="text-sm font-semibold text-black dark:text-white">12 (2.6%)</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2">
                        <div class="bg-black dark:bg-white h-2 rounded-full" style="width: 2.6%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- En Çok Satılan Ürünler -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">En Çok Satılan Ürünler</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-800 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-black dark:text-white">Izgara Tavuk</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">145 adet</p>
                    </div>
                    <span class="text-sm font-semibold text-black dark:text-white">₺12,325</span>
                </div>
                <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-800 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-black dark:text-white">Karışık Pizza</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">128 adet</p>
                    </div>
                    <span class="text-sm font-semibold text-black dark:text-white">₺10,880</span>
                </div>
                <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-800 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-black dark:text-white">Köfte Tabağı</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">112 adet</p>
                    </div>
                    <span class="text-sm font-semibold text-black dark:text-white">₺9,520</span>
                </div>
            </div>
        </div>

        <!-- Saatlik Dağılım -->
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Saatlik Sipariş Dağılımı</h3>
            <div class="h-48 flex items-end justify-between gap-1">
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-gray-300 dark:bg-gray-700" style="height: 20%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">12</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-gray-300 dark:bg-gray-700" style="height: 35%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">13</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-black dark:bg-white" style="height: 55%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">14</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-black dark:bg-white" style="height: 45%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">15</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-gray-300 dark:bg-gray-700" style="height: 40%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">16</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-gray-300 dark:bg-gray-700" style="height: 30%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">17</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-black dark:bg-white" style="height: 85%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">18</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-black dark:bg-white" style="height: 100%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">19</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-black dark:bg-white" style="height: 90%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">20</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-gray-300 dark:bg-gray-700" style="height: 75%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">21</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-gray-300 dark:bg-gray-700" style="height: 50%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">22</span>
                </div>
                <div class="flex-1 flex flex-col items-center justify-end">
                    <div class="w-full bg-gray-300 dark:bg-gray-700" style="height: 25%"></div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">23</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
