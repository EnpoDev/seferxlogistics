<x-bayi-layout>
    <x-slot name="title">Bildirim Ayarlari - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Bildirim Ayarlari</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Bildirim tercihlerinizi yonetin</p>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
            </div>
        @endif

        <!-- Ayarlar Formu -->
        <form action="{{ route('bayi.ayarlar.bildirim.update') }}" method="POST">
            @csrf

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
                <div class="p-6 space-y-6">
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div>
                            <h3 class="font-medium text-black dark:text-white">Yeni Siparis Bildirimleri</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Yeni siparis geldiginde bildirim al</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="new_order_notification" value="1" class="sr-only peer" {{ $settings->new_order_notification ?? true ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black dark:peer-checked:bg-white"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div>
                            <h3 class="font-medium text-black dark:text-white">Kurye Durum Degisikligi</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Kurye durumu degistiginde bildirim al</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="order_status_notification" value="1" class="sr-only peer" {{ $settings->order_status_notification ?? true ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black dark:peer-checked:bg-white"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div>
                            <h3 class="font-medium text-black dark:text-white">E-posta Bildirimleri</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Yeni siparisler icin e-posta al</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="email_new_order" value="1" class="sr-only peer" {{ $settings->email_new_order ?? false ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-black dark:peer-checked:bg-white"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div>
                            <h3 class="font-medium text-black dark:text-white">SMS Bildirimleri</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Onemli durumlarda SMS al</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="sms_enabled" value="1" class="sr-only peer" {{ $settings->sms_enabled ?? false ? 'checked' : '' }}>
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
