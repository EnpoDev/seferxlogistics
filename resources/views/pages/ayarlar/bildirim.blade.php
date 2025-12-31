@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Bildirim Ayarları"
        subtitle="Bildirim tercihlerinizi yönetin"
    >
        <x-slot name="icon">
            <x-ui.icon name="bell" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>
    </x-layout.page-header>

    <form action="{{ route('ayarlar.notification.update') }}" method="POST">
        @csrf
        <div class="max-w-2xl space-y-6">
            {{-- Sipariş Bildirimleri --}}
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Sipariş Bildirimleri</h3>
                <div class="space-y-4">
                    <x-form.toggle
                        name="new_order_notification"
                        label="Yeni sipariş bildirimi"
                        description="Yeni sipariş geldiğinde bildirim al"
                        :checked="$settings->new_order_notification"
                    />
                    <x-form.toggle
                        name="order_status_notification"
                        label="Sipariş durumu değişikliği"
                        description="Sipariş durumu değiştiğinde bildirim al"
                        :checked="$settings->order_status_notification"
                    />
                    <x-form.toggle
                        name="order_cancelled_notification"
                        label="İptal edilen siparişler"
                        description="Sipariş iptal edildiğinde bildirim al"
                        :checked="$settings->order_cancelled_notification"
                    />
                </div>
            </x-ui.card>

            {{-- Tarayıcı Bildirimleri --}}
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Tarayıcı Bildirimleri</h3>
                <div class="space-y-4">
                    <x-form.toggle
                        name="push_enabled"
                        label="Push bildirimleri"
                        description="Tarayıcı bildirimlerini etkinleştir"
                        :checked="$settings->push_enabled"
                    />
                    <x-form.toggle
                        name="sound_enabled"
                        label="Bildirim sesi"
                        description="Bildirim geldiğinde ses çal"
                        :checked="$settings->sound_enabled"
                    />
                </div>

                <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <x-ui.button type="button" onclick="requestNotificationPermission()">
                        Tarayıcı İzni Ver
                    </x-ui.button>
                    <p class="text-xs text-gray-500 mt-2">Push bildirimleri için tarayıcı izni gereklidir.</p>
                </div>
            </x-ui.card>

            {{-- E-posta Bildirimleri --}}
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">E-posta Bildirimleri</h3>
                <div class="space-y-4">
                    <x-form.toggle
                        name="email_daily_summary"
                        label="Günlük özet"
                        description="Her gün sonunda özet rapor al"
                        :checked="$settings->email_daily_summary"
                    />
                    <x-form.toggle
                        name="email_weekly_report"
                        label="Haftalık rapor"
                        description="Haftalık performans raporu al"
                        :checked="$settings->email_weekly_report"
                    />
                    <x-form.toggle
                        name="email_new_order"
                        label="Yeni sipariş e-postası"
                        description="Her yeni siparişte e-posta al"
                        :checked="$settings->email_new_order"
                    />
                </div>
            </x-ui.card>

            <x-ui.button type="submit" class="w-full">Değişiklikleri Kaydet</x-ui.button>
        </div>
    </form>
</div>

@push('scripts')
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
@endpush
@endsection
