@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Ödeme Yöntemleri"
        subtitle="Kabul ettiğiniz ödeme yöntemlerini yapılandırın"
    >
        <x-slot name="icon">
            <x-ui.icon name="credit-card" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>
    </x-layout.page-header>

    {{-- Mesajlar --}}
    @if(session('success'))
        <x-feedback.alert type="success" class="mb-6">{{ session('success') }}</x-feedback.alert>
    @endif

    <form action="{{ route('ayarlar.odeme.update') }}" method="POST">
        @csrf
        <div class="max-w-2xl space-y-6">
            {{-- Ödeme Yöntemleri --}}
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Kabul Edilen Ödeme Yöntemleri</h3>
                <div class="space-y-4">
                    <x-form.toggle
                        name="accept_cash"
                        label="Nakit"
                        description="Kapıda nakit ödeme"
                        :checked="$settings->accept_cash"
                    />
                    <x-form.toggle
                        name="accept_card"
                        label="Online Kredi Kartı"
                        description="Online kredi kartı ödemesi"
                        :checked="$settings->accept_card"
                    />
                    <x-form.toggle
                        name="accept_card_on_delivery"
                        label="Kapıda Kredi Kartı"
                        description="Teslimat sırasında POS ile ödeme"
                        :checked="$settings->accept_card_on_delivery"
                    />
                    <x-form.toggle
                        name="accept_online"
                        label="Online Ödeme"
                        description="Havale/EFT veya diğer online ödemeler"
                        :checked="$settings->accept_online"
                    />
                </div>
            </x-ui.card>

            {{-- Ödeme Sağlayıcı --}}
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Ödeme Sağlayıcı</h3>
                <x-form.select name="payment_provider" label="Ödeme Sağlayıcısı Seçin" :options="[
                    '' => 'Seçiniz...',
                    'iyzico' => 'Iyzico',
                    'paytr' => 'PayTR',
                    'stripe' => 'Stripe',
                ]" :selected="$settings->payment_provider" />
            </x-ui.card>

            {{-- Limitler --}}
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Ödeme Limitleri</h3>
                <x-layout.grid cols="2" gap="4">
                    <x-form.input
                        type="number"
                        name="min_order_amount"
                        label="Minimum Sipariş Tutarı"
                        prefix="₺"
                        step="0.01"
                        :value="$settings->min_order_amount"
                        placeholder="0.00"
                    />
                    <x-form.input
                        type="number"
                        name="max_cash_amount"
                        label="Maksimum Nakit Tutarı"
                        prefix="₺"
                        step="0.01"
                        :value="$settings->max_cash_amount"
                        placeholder="0.00"
                    />
                </x-layout.grid>
            </x-ui.card>

            <x-ui.button type="submit" class="w-full">Değişiklikleri Kaydet</x-ui.button>
        </div>
    </form>
</div>
@endsection
