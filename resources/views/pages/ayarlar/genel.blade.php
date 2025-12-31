@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Genel Ayarlar"
        subtitle="Temel sistem ayarlarınızı yapılandırın"
    >
        <x-slot name="icon">
            <x-ui.icon name="settings" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>
    </x-layout.page-header>

    {{-- Mesajlar --}}
    @if(session('success'))
        <x-feedback.alert type="success" class="mb-6 max-w-2xl">{{ session('success') }}</x-feedback.alert>
    @endif

    <div class="max-w-2xl space-y-6">
        {{-- Kullanıcı Bilgileri --}}
        <x-ui.card>
            <form action="{{ route('ayarlar.general.update') }}" method="POST">
                @csrf
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Kullanıcı Bilgileri</h3>

                <div class="space-y-4">
                    <x-form.input name="name" label="Ad Soyad" :value="old('name', $user->name)" :error="$errors->first('name')" />
                    <x-form.input type="email" name="email" label="E-posta" :value="old('email', $user->email)" :error="$errors->first('email')" />
                    <x-form.input name="roles" label="Rol(ler)" :value="implode(', ', $user->roles ?? [])" disabled />
                </div>

                <x-ui.button type="submit" class="w-full mt-4">Kullanıcı Bilgilerini Güncelle</x-ui.button>
            </form>
        </x-ui.card>

        {{-- İşletme Bilgileri --}}
        @if($businessInfo)
        <x-ui.card>
            <form action="{{ route('ayarlar.business.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">İşletme Bilgileri</h3>

                <div class="space-y-4">
                    <x-form.input name="name" label="İşletme Adı" :value="old('name', $businessInfo->name)" :error="$errors->first('name')" />
                    <x-form.input type="tel" name="phone" label="Telefon" :value="old('phone', $businessInfo->phone)" :error="$errors->first('phone')" />
                    <x-form.input type="email" name="email" label="E-posta" :value="old('email', $businessInfo->email)" :error="$errors->first('email')" />
                    <x-form.textarea name="address" label="Adres" :rows="3" :error="$errors->first('address')">{{ old('address', $businessInfo->address) }}</x-form.textarea>
                    <x-form.input name="tax_number" label="Vergi Numarası" :value="old('tax_number', $businessInfo->tax_number)" />
                    <x-form.input type="file" name="logo" label="Logo" accept="image/*" />
                </div>

                <x-ui.button type="submit" class="w-full mt-4">İşletme Bilgilerini Güncelle</x-ui.button>
            </form>
        </x-ui.card>
        @endif

        {{-- Şifre Değiştir --}}
        <x-ui.card>
            <form action="{{ route('ayarlar.password.update') }}" method="POST">
                @csrf
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Şifre Değiştir</h3>

                <div class="space-y-4">
                    <x-form.input type="password" name="current_password" label="Mevcut Şifre" :error="$errors->first('current_password')" />
                    <x-form.input type="password" name="password" label="Yeni Şifre" :error="$errors->first('password')" />
                    <x-form.input type="password" name="password_confirmation" label="Yeni Şifre (Tekrar)" />
                </div>

                <x-ui.button type="submit" class="w-full mt-4">Şifreyi Değiştir</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</div>
@endsection
