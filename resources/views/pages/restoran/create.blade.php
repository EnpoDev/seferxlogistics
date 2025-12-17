@extends('layouts.app')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex items-center space-x-4 mb-6">
        <a href="{{ route('restoran.index') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Yeni Restoran Ekle</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Sisteme yeni restoran ekleyin</p>
        </div>
    </div>

    <form action="{{ route('restoran.store') }}" method="POST" enctype="multipart/form-data" class="max-w-4xl">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Info -->
                <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Temel Bilgiler</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Restoran Adı *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                            @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Açıklama</label>
                            <textarea name="description" rows="3"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">{{ old('description') }}</textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-black dark:text-white mb-2">Telefon</label>
                                <input type="text" name="phone" value="{{ old('phone') }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-black dark:text-white mb-2">Adres</label>
                                <input type="text" name="address" value="{{ old('address') }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delivery Settings -->
                <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Teslimat Ayarları</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Min. Sipariş (₺)</label>
                            <input type="number" name="min_order_amount" value="{{ old('min_order_amount', 0) }}" step="0.01" min="0"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Teslimat Ücreti (₺)</label>
                            <input type="number" name="delivery_fee" value="{{ old('delivery_fee', 0) }}" step="0.01" min="0"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Max Teslimat (dk)</label>
                            <input type="number" name="max_delivery_time" value="{{ old('max_delivery_time', 45) }}" min="1"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                        </div>
                    </div>
                </div>

                <!-- Categories -->
                <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Kategoriler</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($categories as $category)
                        <label class="flex items-center p-3 bg-gray-50 dark:bg-black rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                            <input type="checkbox" name="categories[]" value="{{ $category->id }}" 
                                {{ in_array($category->id, old('categories', [])) ? 'checked' : '' }}
                                class="mr-3">
                            <div>
                                <p class="font-medium text-black dark:text-white text-sm">{{ $category->name }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Images -->
                <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Görseller</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Logo</label>
                            <input type="file" name="logo" accept="image/*"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white text-sm">
                            <p class="text-xs text-gray-500 mt-1">Önerilen: 200x200px</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Banner Görseli</label>
                            <input type="file" name="banner_image" accept="image/*"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white text-sm">
                            <p class="text-xs text-gray-500 mt-1">Önerilen: 1200x400px</p>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Durum</h3>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="mr-3">
                            <span class="text-black dark:text-white">Aktif (Açık)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }} class="mr-3">
                            <span class="text-black dark:text-white">Ana Sayfada Öne Çıkar</span>
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex flex-col space-y-3">
                    <button type="submit" class="w-full px-6 py-3 bg-black dark:bg-white text-white dark:text-black rounded-lg font-medium hover:opacity-80 transition-opacity">
                        Restoranı Kaydet
                    </button>
                    <a href="{{ route('restoran.index') }}" class="w-full px-6 py-3 border border-gray-300 dark:border-gray-700 rounded-lg text-center font-medium text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors">
                        İptal
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

