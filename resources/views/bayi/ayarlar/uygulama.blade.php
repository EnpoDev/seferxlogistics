<x-bayi-layout>
    <x-slot name="title">Uygulama Ayarları - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Uygulama Ayarları</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Genel uygulama tercihlerini ayarlayın</p>
            </div>
            <button class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors font-medium">
                Kaydet
            </button>
        </div>

        <!-- Ayarlar Formu -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Dil</label>
                    <select class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800">
                        <option>Türkçe</option>
                        <option>English</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Zaman Dilimi</label>
                    <select class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800">
                        <option>Europe/Istanbul</option>
                    </select>
                </div>

                <div>
                    <label class="flex items-center space-x-3">
                        <input type="checkbox" class="w-5 h-5 rounded border-gray-300">
                        <span class="text-sm font-medium text-black dark:text-white">Sesli bildirimler</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</x-bayi-layout>

