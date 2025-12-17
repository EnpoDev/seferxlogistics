@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-black dark:text-white">Genel Ayarlar</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Temel sistem ayarlarınızı yapılandırın</p>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg max-w-2xl">
            <p class="text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    <div class="max-w-2xl">
        <form action="{{ route('ayarlar.general.update') }}" method="POST" class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 mb-6">
            @csrf
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Kullanıcı Bilgileri</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Ad Soyad</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">E-posta</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Rol(ler)</label>
                    <input type="text" value="{{ implode(', ', $user->roles ?? []) }}" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-600 dark:text-gray-400" readonly>
                </div>
            </div>
            <button type="submit" class="mt-4 w-full px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                Kullanıcı Bilgilerini Güncelle
            </button>
        </form>

        @if($businessInfo)
        <form action="{{ route('ayarlar.business.update') }}" method="POST" enctype="multipart/form-data" class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 mb-6">
            @csrf
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">İşletme Bilgileri</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">İşletme Adı</label>
                    <input type="text" name="name" value="{{ old('name', $businessInfo->name) }}" class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Telefon</label>
                    <input type="tel" name="phone" value="{{ old('phone', $businessInfo->phone) }}" class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    @error('phone')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">E-posta</label>
                    <input type="email" name="email" value="{{ old('email', $businessInfo->email) }}" class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Adres</label>
                    <textarea rows="3" name="address" class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">{{ old('address', $businessInfo->address) }}</textarea>
                    @error('address')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Vergi Numarası</label>
                    <input type="text" name="tax_number" value="{{ old('tax_number', $businessInfo->tax_number) }}" class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Logo</label>
                    <input type="file" name="logo" accept="image/*" class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
            </div>
            <button type="submit" class="mt-4 w-full px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                İşletme Bilgilerini Güncelle
            </button>
        </form>
        @endif

        <form action="{{ route('ayarlar.password.update') }}" method="POST" class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 mb-6">
            @csrf
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Şifre Değiştir</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Mevcut Şifre</label>
                    <input type="password" name="current_password" class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    @error('current_password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Yeni Şifre</label>
                    <input type="password" name="password" class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Yeni Şifre (Tekrar)</label>
                    <input type="password" name="password_confirmation" class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
            </div>
            <button type="submit" class="mt-4 w-full px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                Şifreyi Değiştir
            </button>
        </form>
    </div>
</div>
@endsection
