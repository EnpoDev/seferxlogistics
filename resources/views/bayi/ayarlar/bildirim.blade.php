<x-bayi-layout>
    <x-slot name="title">Bildirim Ayarları - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Bildirim Ayarları</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Bildirim tercihlerinizi yönetin</p>
            </div>
            <button class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors font-medium">
                Kaydet
            </button>
        </div>

        <!-- Ayarlar Formu -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-6 space-y-6">
                <div>
                    <label class="flex items-center space-x-3">
                        <input type="checkbox" class="w-5 h-5 rounded border-gray-300">
                        <span class="text-sm font-medium text-black dark:text-white">Yeni sipariş bildirimleri</span>
                    </label>
                </div>

                <div>
                    <label class="flex items-center space-x-3">
                        <input type="checkbox" class="w-5 h-5 rounded border-gray-300">
                        <span class="text-sm font-medium text-black dark:text-white">Kurye durum değişikliği</span>
                    </label>
                </div>

                <div>
                    <label class="flex items-center space-x-3">
                        <input type="checkbox" class="w-5 h-5 rounded border-gray-300">
                        <span class="text-sm font-medium text-black dark:text-white">E-posta bildirimleri</span>
                    </label>
                </div>

                <div>
                    <label class="flex items-center space-x-3">
                        <input type="checkbox" class="w-5 h-5 rounded border-gray-300">
                        <span class="text-sm font-medium text-black dark:text-white">SMS bildirimleri</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</x-bayi-layout>

