<x-bayi-layout>
    <x-slot name="title">Genel Ayarlar - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Genel Ayarlar</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Sistem genelayarlarını düzenleyin</p>
            </div>
            <button class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors font-medium">
                Kaydet
            </button>
        </div>

        <!-- Ayarlar Formu -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-6 space-y-6">
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Ad Soyad</label>
                    <input type="text" value="{{ $user->name }}" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-black dark:focus:border-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">E-posta</label>
                    <input type="email" value="{{ $user->email }}" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-black dark:focus:border-white">
                </div>

                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Telefon</label>
                    <input type="tel" value="{{ $user->phone ?? '' }}" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-black dark:focus:border-white">
                </div>
            </div>
        </div>
    </div>
</x-bayi-layout>

