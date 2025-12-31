<x-bayi-layout>
    <x-slot name="title">Havuz Ayarlari - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Havuz Ayarlari</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Kurye havuzu yonetim ayarlari</p>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-red-700 dark:text-red-400">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Ayarlar Formu -->
        <form action="{{ route('bayi.ayarlar.havuz.update') }}" method="POST">
            @csrf

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
                <div class="p-6 space-y-6">
                    <!-- Havuz Sistemi -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div>
                            <h3 class="font-medium text-black dark:text-white">Havuz Sistemini Aktif Et</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Siparisler havuza duser ve kuryeler tarafindan alinabilir
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="pool_enabled" value="1" class="sr-only peer" {{ $settings?->pool_enabled ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black dark:peer-checked:bg-white"></div>
                        </label>
                    </div>

                    <!-- Havuz Bekleme Suresi -->
                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">
                            Havuz Bekleme Suresi (dakika)
                        </label>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            Siparis havuzda ne kadar sure bekleyecek
                        </p>
                        <input type="number"
                               name="pool_wait_time"
                               value="{{ old('pool_wait_time', $settings?->pool_wait_time ?? 5) }}"
                               min="1"
                               max="60"
                               class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-black dark:focus:border-white">
                        @error('pool_wait_time')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Maksimum Siparis -->
                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">
                            Havuzda Maksimum Siparis Sayisi
                        </label>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            Havuzda ayni anda bekleyebilecek maksimum siparis sayisi
                        </p>
                        <input type="number"
                               name="pool_max_orders"
                               value="{{ old('pool_max_orders', $settings?->pool_max_orders ?? 10) }}"
                               min="1"
                               max="100"
                               class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-black dark:focus:border-white">
                        @error('pool_max_orders')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Otomatik Atama -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div>
                            <h3 class="font-medium text-black dark:text-white">Otomatik Havuzdan Atama</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Bekleme suresi dolunca en uygun kuryeye otomatik ata
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="pool_auto_assign" value="1" class="sr-only peer" {{ $settings?->pool_auto_assign ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black dark:peer-checked:bg-white"></div>
                        </label>
                    </div>

                    <!-- Mesafeye Gore Oncelik -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div>
                            <h3 class="font-medium text-black dark:text-white">Mesafeye Gore Oncelik</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Otomatik atamada en yakin kuryeye oncelik ver
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="pool_priority_by_distance" value="1" class="sr-only peer" {{ $settings?->pool_priority_by_distance ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black dark:peer-checked:bg-white"></div>
                        </label>
                    </div>

                    <!-- Kuryelere Bildirim -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div>
                            <h3 class="font-medium text-black dark:text-white">Kuryelere Bildirim Gonder</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Yeni siparis havuza dusunce kuryelere bildirim gonder
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="pool_notify_couriers" value="1" class="sr-only peer" {{ $settings?->pool_notify_couriers ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black dark:peer-checked:bg-white"></div>
                        </label>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors font-medium">
                        Kaydet
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-bayi-layout>
