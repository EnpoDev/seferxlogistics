@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn" x-data="{
    autoAcceptOrders: {{ $settings->auto_accept_orders ? 'true' : 'false' }},
    soundNotifications: {{ $settings->sound_notifications ? 'true' : 'false' }}
}">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Uygulama Ayarları"
        subtitle="Uygulama davranışlarını yapılandırın"
    >
        <x-slot name="icon">
            <x-ui.icon name="cog" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>
    </x-layout.page-header>

    {{-- Mesajlar --}}
    @if(session('success'))
        <x-feedback.alert type="success" class="mb-6 max-w-2xl">{{ session('success') }}</x-feedback.alert>
    @endif

    @if($errors->any())
        <x-feedback.alert type="danger" class="mb-6 max-w-2xl">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </x-feedback.alert>
    @endif

    <form action="{{ route('ayarlar.uygulama.update') }}" method="POST">
        @csrf
        <input type="hidden" name="auto_accept_orders" :value="autoAcceptOrders ? '1' : '0'">
        <input type="hidden" name="sound_notifications" :value="soundNotifications ? '1' : '0'">

        <div class="max-w-2xl space-y-6">
            {{-- Dil ve Bölge --}}
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Dil ve Bölge</h3>
                <div class="space-y-4">
                    <x-form.select name="language" label="Dil" :options="[
                        'tr' => 'Türkçe',
                        'en' => 'English',
                    ]" :selected="$settings->language" />

                    <x-form.select name="timezone" label="Saat Dilimi" :options="$timezones" :selected="$settings->timezone" />

                    <x-form.select name="currency" label="Para Birimi" :options="[
                        'TRY' => 'Türk Lirası (₺)',
                        'EUR' => 'Euro (€)',
                        'USD' => 'Dolar ($)',
                    ]" :selected="$settings->currency" />
                </div>
            </x-ui.card>

            {{-- Sipariş Ayarları --}}
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Sipariş Ayarları</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">Otomatik sipariş kabul</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Gelen siparişleri otomatik kabul et</p>
                        </div>
                        <button type="button" @click="autoAcceptOrders = !autoAcceptOrders"
                                class="relative w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black dark:focus:ring-white"
                                :class="autoAcceptOrders ? 'bg-black dark:bg-white' : 'bg-gray-300 dark:bg-gray-700'">
                            <span class="absolute top-1 left-1 w-4 h-4 rounded-full bg-white dark:bg-black transition-transform duration-200"
                                  :class="autoAcceptOrders ? 'translate-x-5' : 'translate-x-0'"></span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">Sesli bildirim</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Yeni sipariş geldiğinde ses çal</p>
                        </div>
                        <button type="button" @click="soundNotifications = !soundNotifications"
                                class="relative w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black dark:focus:ring-white"
                                :class="soundNotifications ? 'bg-black dark:bg-white' : 'bg-gray-300 dark:bg-gray-700'">
                            <span class="absolute top-1 left-1 w-4 h-4 rounded-full bg-white dark:bg-black transition-transform duration-200"
                                  :class="soundNotifications ? 'translate-x-5' : 'translate-x-0'"></span>
                        </button>
                    </div>

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <x-layout.grid cols="2" gap="4">
                            <x-form.input
                                type="number"
                                name="default_order_timeout"
                                label="Sipariş Zaman Aşımı (dk)"
                                :value="$settings->default_order_timeout"
                                min="5"
                                max="120"
                            />
                            <x-form.input
                                type="number"
                                name="default_preparation_time"
                                label="Varsayılan Hazırlık Süresi (dk)"
                                :value="$settings->default_preparation_time"
                                min="5"
                                max="120"
                            />
                        </x-layout.grid>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.button type="submit" class="w-full">Değişiklikleri Kaydet</x-ui.button>
        </div>
    </form>
</div>
@endsection
