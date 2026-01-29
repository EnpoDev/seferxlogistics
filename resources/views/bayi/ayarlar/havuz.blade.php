<x-bayi-layout>
    <x-slot name="title">Havuz Ayarları - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Havuz Ayarları</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Kurye havuzu yönetim ayarları</p>
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
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <x-form.toggle
                            name="pool_enabled"
                            label="Havuz Sistemini Aktif Et"
                            description="Siparişler havuza düşer ve kuryeler tarafından alınabilir"
                            :checked="$settings?->pool_enabled"
                        />
                    </div>

                    <!-- Havuz Bekleme Süresi -->
                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">
                            Havuz Bekleme Süresi (dakika)
                        </label>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            Sipariş havuzda ne kadar süre bekleyecek
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

                    <!-- Maksimum Sipariş -->
                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">
                            Havuzda Maksimum Sipariş Sayısı
                        </label>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            Havuzda aynı anda bekleyebilecek maksimum sipariş sayısı
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
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <x-form.toggle
                            name="pool_auto_assign"
                            label="Otomatik Havuzdan Atama"
                            description="Bekleme süresi dolunca en uygun kuryeye otomatik ata"
                            :checked="$settings?->pool_auto_assign"
                        />
                    </div>

                    <!-- AI Dağıtım -->
                    <div class="p-4 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                        <div class="flex items-start gap-3">
                            <div class="p-2 bg-purple-500/10 rounded-lg">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <x-form.toggle
                                    name="pool_ai_distribution"
                                    label="AI Tabanlı Akıllı Dağıtım"
                                    description="Kurye seçiminde mesafe, iş yükü, performans ve bölge uyumunu analiz eder"
                                    :checked="$settings?->pool_ai_distribution ?? true"
                                />
                                <div class="mt-3 text-xs text-purple-600 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/30 rounded px-3 py-2">
                                    <strong>AI Faktörleri:</strong> Mesafe (%35), İş Yükü (%25), Performans (%20), Bölge (%15), Müsaitlik (%5)
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mesafeye Göre Öncelik -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <x-form.toggle
                            name="pool_priority_by_distance"
                            label="Mesafeye Göre Öncelik"
                            description="Otomatik atamada en yakın kuryeye öncelik ver"
                            :checked="$settings?->pool_priority_by_distance"
                        />
                    </div>

                    <!-- Kuryelere Bildirim -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <x-form.toggle
                            name="pool_notify_couriers"
                            label="Kuryelere Bildirim Gönder"
                            description="Yeni sipariş havuza düşünce kuryelere bildirim gönder"
                            :checked="$settings?->pool_notify_couriers"
                        />
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
