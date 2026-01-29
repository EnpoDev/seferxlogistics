<x-bayi-layout>
    <x-slot name="title">Kurye Ayarları - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Kurye Ayarları</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Kurye sistemine özel ayarlar</p>
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
        <form action="{{ route('bayi.ayarlar.kurye.update') }}" method="POST">
            @csrf

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
                <div class="p-6 space-y-6">
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <x-form.toggle
                            name="auto_assign_courier"
                            label="Otomatik Kurye Ataması"
                            description="Siparişleri otomatik olarak en uygun kuryeye ata"
                            :checked="$settings?->auto_assign_courier"
                        />
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <x-form.toggle
                            name="check_courier_shift"
                            label="Kurye Mesai Saati Kontrolü"
                            description="Sadece mesai saatlerindeki kuryelere sipariş ata"
                            :checked="$settings?->check_courier_shift ?? true"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Maksimum Teslimat Süresi (dakika)</label>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Bu süreden uzun teslimatlar gecikme olarak işaretlenir</p>
                        <input type="number"
                               name="max_delivery_time"
                               value="{{ old('max_delivery_time', $settings?->max_delivery_time ?? 45) }}"
                               min="10"
                               max="180"
                               class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-black dark:focus:border-white">
                        @error('max_delivery_time')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
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
