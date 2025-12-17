@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-black dark:text-white">Bildirim Ayarları</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Bildirim tercihlerinizi yönetin</p>
    </div>

    <form action="{{ route('ayarlar.notification.update') }}" method="POST">
        @csrf
        
        <div class="max-w-2xl space-y-6">
            <!-- Sipariş Bildirimleri -->
            <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Sipariş Bildirimleri</h3>
                <div class="space-y-4">
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">Yeni sipariş bildirimi</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Yeni sipariş geldiğinde bildirim al</p>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="new_order_notification" value="1" 
                                   {{ $settings->new_order_notification ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 dark:bg-gray-700 rounded-full peer peer-checked:bg-black dark:peer-checked:bg-white transition-colors"></div>
                            <div class="absolute left-1 top-1 w-4 h-4 bg-white dark:bg-black rounded-full transition-transform peer-checked:translate-x-5"></div>
                        </div>
                    </label>
                    
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">Sipariş durumu değişikliği</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Sipariş durumu değiştiğinde bildirim al</p>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="order_status_notification" value="1"
                                   {{ $settings->order_status_notification ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 dark:bg-gray-700 rounded-full peer peer-checked:bg-black dark:peer-checked:bg-white transition-colors"></div>
                            <div class="absolute left-1 top-1 w-4 h-4 bg-white dark:bg-black rounded-full transition-transform peer-checked:translate-x-5"></div>
                        </div>
                    </label>
                    
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">İptal edilen siparişler</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Sipariş iptal edildiğinde bildirim al</p>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="order_cancelled_notification" value="1"
                                   {{ $settings->order_cancelled_notification ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 dark:bg-gray-700 rounded-full peer peer-checked:bg-black dark:peer-checked:bg-white transition-colors"></div>
                            <div class="absolute left-1 top-1 w-4 h-4 bg-white dark:bg-black rounded-full transition-transform peer-checked:translate-x-5"></div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Push Bildirimleri -->
            <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Tarayıcı Bildirimleri</h3>
                <div class="space-y-4">
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">Push bildirimleri</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Tarayıcı bildirimlerini etkinleştir</p>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="push_enabled" value="1"
                                   {{ $settings->push_enabled ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 dark:bg-gray-700 rounded-full peer peer-checked:bg-black dark:peer-checked:bg-white transition-colors"></div>
                            <div class="absolute left-1 top-1 w-4 h-4 bg-white dark:bg-black rounded-full transition-transform peer-checked:translate-x-5"></div>
                        </div>
                    </label>
                    
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">Bildirim sesi</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Bildirim geldiğinde ses çal</p>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="sound_enabled" value="1"
                                   {{ $settings->sound_enabled ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 dark:bg-gray-700 rounded-full peer peer-checked:bg-black dark:peer-checked:bg-white transition-colors"></div>
                            <div class="absolute left-1 top-1 w-4 h-4 bg-white dark:bg-black rounded-full transition-transform peer-checked:translate-x-5"></div>
                        </div>
                    </label>
                </div>
                
                <!-- Browser notification permission button -->
                <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <button type="button" onclick="requestNotificationPermission()" 
                            class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg text-sm font-medium hover:opacity-80 transition-opacity">
                        Tarayıcı İzni Ver
                    </button>
                    <p class="text-xs text-gray-500 mt-2">Push bildirimleri için tarayıcı izni gereklidir.</p>
                </div>
            </div>

            <!-- E-posta Bildirimleri -->
            <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">E-posta Bildirimleri</h3>
                <div class="space-y-4">
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">Günlük özet</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Her gün sonunda özet rapor al</p>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="email_daily_summary" value="1"
                                   {{ $settings->email_daily_summary ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 dark:bg-gray-700 rounded-full peer peer-checked:bg-black dark:peer-checked:bg-white transition-colors"></div>
                            <div class="absolute left-1 top-1 w-4 h-4 bg-white dark:bg-black rounded-full transition-transform peer-checked:translate-x-5"></div>
                        </div>
                    </label>
                    
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">Haftalık rapor</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Haftalık performans raporu al</p>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="email_weekly_report" value="1"
                                   {{ $settings->email_weekly_report ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 dark:bg-gray-700 rounded-full peer peer-checked:bg-black dark:peer-checked:bg-white transition-colors"></div>
                            <div class="absolute left-1 top-1 w-4 h-4 bg-white dark:bg-black rounded-full transition-transform peer-checked:translate-x-5"></div>
                        </div>
                    </label>
                    
                    <label class="flex items-center justify-between cursor-pointer">
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">Yeni sipariş e-postası</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Her yeni siparişte e-posta al</p>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="email_new_order" value="1"
                                   {{ $settings->email_new_order ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 dark:bg-gray-700 rounded-full peer peer-checked:bg-black dark:peer-checked:bg-white transition-colors"></div>
                            <div class="absolute left-1 top-1 w-4 h-4 bg-white dark:bg-black rounded-full transition-transform peer-checked:translate-x-5"></div>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit" class="w-full px-4 py-3 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80 transition-opacity font-medium">
                Değişiklikleri Kaydet
            </button>
        </div>
    </form>
</div>

<script>
function requestNotificationPermission() {
    if ('Notification' in window) {
        Notification.requestPermission().then(function(permission) {
            if (permission === 'granted') {
                window.showToast('Tarayıcı bildirimleri etkinleştirildi!', 'success');
            } else if (permission === 'denied') {
                window.showToast('Bildirim izni reddedildi.', 'error');
            }
        });
    } else {
        window.showToast('Tarayıcınız bildirimleri desteklemiyor.', 'warning');
    }
}
</script>
@endsection
