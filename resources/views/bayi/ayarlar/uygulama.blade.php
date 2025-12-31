<x-bayi-layout>
    <x-slot name="title">Uygulama Ayarlari - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Uygulama Ayarlari</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Genel uygulama tercihlerini ayarlayin</p>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
            </div>
        @endif

        <!-- Ayarlar Formu -->
        <form action="{{ route('bayi.ayarlar.uygulama.update') }}" method="POST">
            @csrf

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Dil</label>
                        <select name="language" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-black dark:focus:border-white">
                            <option value="tr" {{ ($settings->language ?? 'tr') === 'tr' ? 'selected' : '' }}>Turkce</option>
                            <option value="en" {{ ($settings->language ?? 'tr') === 'en' ? 'selected' : '' }}>English</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Zaman Dilimi</label>
                        <select name="timezone" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-black dark:focus:border-white">
                            @foreach(\App\Models\ApplicationSetting::getTimezones() as $tz => $label)
                                <option value="{{ $tz }}" {{ ($settings->timezone ?? 'Europe/Istanbul') === $tz ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div>
                            <h3 class="font-medium text-black dark:text-white">Sesli Bildirimler</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Yeni siparis geldiginde ses cal</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="sound_notifications" value="1" class="sr-only peer" {{ $settings->sound_notifications ?? true ? 'checked' : '' }}>
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
