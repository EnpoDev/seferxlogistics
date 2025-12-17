<x-bayi-layout>
    <x-slot name="title">Tema Yapılandırması - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Tema Yapılandırması</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Arayüz görünümünü özelleştirin</p>
            </div>
            <button class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors font-medium">
                Kaydet
            </button>
        </div>

        <!-- Tema Seçenekleri -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Açık Tema -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border-2 border-gray-200 dark:border-gray-800 hover:border-black dark:hover:border-white transition-colors cursor-pointer p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-black dark:text-white">Açık Tema</h3>
                    <div class="w-8 h-8 bg-white border-2 border-gray-300 rounded-full"></div>
                </div>
                <div class="bg-gray-100 rounded-lg h-32 flex items-center justify-center">
                    <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Koyu Tema -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border-2 border-black dark:border-white transition-colors cursor-pointer p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-black dark:text-white">Koyu Tema</h3>
                    <div class="w-8 h-8 bg-black border-2 border-black rounded-full"></div>
                </div>
                <div class="bg-gray-900 rounded-lg h-32 flex items-center justify-center">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Renk Ayarları -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Renk Ayarları</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Ana Renk</label>
                        <input type="color" value="#000000" class="w-24 h-10 rounded-lg cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Vurgu Rengi</label>
                        <input type="color" value="#FFFFFF" class="w-24 h-10 rounded-lg cursor-pointer">
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-bayi-layout>

