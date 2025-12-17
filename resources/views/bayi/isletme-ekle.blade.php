@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-2">
            @if(isset($parent) && $parent)
                <a href="{{ route('bayi.isletme-detay', $parent->id) }}" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
            @else
                <a href="{{ route('bayi.isletmelerim') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
            @endif
                <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-black dark:text-white">
                @if(isset($parent) && $parent)
                    Yeni Şube Ekle
                @else
                    Yeni İşletme Ekle
                @endif
            </h1>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 ml-12">
            @if(isset($parent) && $parent)
                {{ $parent->name }} işletmesine yeni bir şube ekleyin
            @else
                Sisteme yeni bir işletme ekleyin
            @endif
        </p>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm max-w-7xl mx-auto">
        <div class="p-6 sm:p-8">
            <form action="{{ route('bayi.isletme-kaydet') }}" method="POST" class="flex flex-col" style="gap: 2rem;">
                @csrf
                @if(isset($parent) && $parent)
                    <input type="hidden" name="parent_id" value="{{ $parent->id }}">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900 rounded-xl p-4 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-100 dark:bg-blue-800 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-blue-800 dark:text-blue-200 font-medium">Bağlı İşletme</p>
                                <p class="text-sm text-blue-600 dark:text-blue-300">Bu şube <strong>{{ $parent->name }}</strong> altına eklenecektir.</p>
                            </div>
                        </div>
                    </div>
                @endif
                
                <!-- İşletme Bilgileri -->
                <div>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4 border-b border-gray-100 dark:border-gray-800 pb-2">İşletme Bilgileri</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">İşletme Adı *</label>
                            <input type="text" name="name" required placeholder="Örn: Merkez Şube"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Durum *</label>
                            <select name="status" required 
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="active">Aktif</option>
                                <option value="passive">Pasif</option>
                            </select>
                        </div>

                    </div>
                </div>

                <!-- İletişim Bilgileri -->
                <div>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4 border-b border-gray-100 dark:border-gray-800 pb-2">İletişim Bilgileri</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Telefon *</label>
                            <input type="text" name="phone" required placeholder="0212 555 55 55"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">E-posta</label>
                            <input type="email" name="email" placeholder="ornek@email.com"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Adres *</label>
                            <textarea name="address" required rows="3" placeholder="Açık adres giriniz"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ (isset($parent) && $parent) ? route('bayi.isletme-detay', $parent->id) : route('bayi.isletmelerim') }}" 
                        class="px-6 py-2.5 border border-gray-300 dark:border-gray-700 rounded-xl text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors font-medium">
                        İptal
                    </a>
                    <button type="submit" 
                        class="px-6 py-2.5 bg-black dark:bg-white text-white dark:text-black rounded-xl hover:scale-105 transition-all duration-200 font-medium shadow-lg hover:shadow-xl">
                        {{ (isset($parent) && $parent) ? 'Şube Oluştur' : 'İşletme Oluştur' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

