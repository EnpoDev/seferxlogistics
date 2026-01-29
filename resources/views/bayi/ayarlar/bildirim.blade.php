<x-bayi-layout>
    <x-slot name="title">Bildirim Ayarları - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Bildirim Ayarları</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Bildirim tercihlerinizi yönetin</p>
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
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <x-form.toggle
                            name="new_order_notification"
                            label="Yeni Sipariş Bildirimleri"
                            description="Yeni sipariş geldiğinde bildirim al"
                            :checked="$settings->new_order_notification ?? true"
                        />
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <x-form.toggle
                            name="order_status_notification"
                            label="Kurye Durum Değişikliği"
                            description="Kurye durumu değiştiğinde bildirim al"
                            :checked="$settings->order_status_notification ?? true"
                        />
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <x-form.toggle
                            name="email_new_order"
                            label="E-posta Bildirimleri"
                            description="Yeni siparişler için e-posta al"
                            :checked="$settings->email_new_order ?? false"
                        />
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <x-form.toggle
                            name="sms_enabled"
                            label="SMS Bildirimleri"
                            description="Önemli durumlarda SMS al"
                            :checked="$settings->sms_enabled ?? false"
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
