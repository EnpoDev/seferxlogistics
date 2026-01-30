@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Kullanici Duzenle"
        :subtitle="$user->name . ' kullanicisini duzenleyin'"
        :backUrl="route('bayi.kullanici-yonetimi')"
    />

    {{-- Form --}}
    <x-ui.card class="max-w-2xl mx-auto">
        <form action="{{ route('bayi.kullanici-guncelle', $user) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Kisisel Bilgiler --}}
            <x-layout.section title="Kullanici Bilgileri" border>
                <x-layout.grid cols="1" mdCols="2">
                    <x-form.input name="name" label="Ad Soyad" placeholder="Ornegin: Ahmet Yilmaz" :value="old('name', $user->name)" required />
                    <x-form.input name="phone" label="Telefon" placeholder="05XX XXX XX XX" :value="old('phone', $user->phone)" />
                    <div class="md:col-span-2">
                        <x-form.input type="email" name="email" label="E-posta" placeholder="ornek@email.com" :value="old('email', $user->email)" required />
                    </div>
                </x-layout.grid>
            </x-layout.section>

            {{-- Sifre --}}
            <x-layout.section title="Sifre Degistir (Opsiyonel)" border>
                <x-layout.grid cols="1" mdCols="2">
                    <x-form.input type="password" name="password" label="Yeni Sifre" placeholder="Bos birakirsaniz degismez" />
                    <x-form.input type="password" name="password_confirmation" label="Sifre Tekrar" placeholder="Yeni sifreyi tekrar girin" />
                </x-layout.grid>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Sifreyi degistirmek istemiyorsaniz bu alanlari bos birakin.</p>
            </x-layout.section>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                <x-ui.button variant="secondary" :href="route('bayi.kullanici-yonetimi')">Iptal</x-ui.button>
                <x-ui.button type="submit">Degisiklikleri Kaydet</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
