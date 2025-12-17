@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-2">
            @if($branch->parent_id)
                <a href="{{ route('bayi.isletme-detay', $branch->parent_id) }}" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
            @else
                <a href="{{ route('bayi.isletmelerim') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
            @endif
                <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-black dark:text-white">İşletme Düzenle</h1>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 ml-12">{{ $branch->name }} işletme bilgilerini güncelleyin</p>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm max-w-7xl mx-auto">
        <div class="p-6 sm:p-8">
            <form action="{{ route('bayi.isletme-guncelle', $branch->id) }}" method="POST" class="flex flex-col" style="gap: 2rem;">
                @csrf
                @method('PUT')
                
                <!-- İşletme Bilgileri -->
                <div>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4 border-b border-gray-100 dark:border-gray-800 pb-2">İşletme Bilgileri</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">İşletme Adı *</label>
                            <input type="text" name="name" required value="{{ old('name', $branch->name) }}"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Durum *</label>
                            <select name="status" required 
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="active" {{ $branch->is_active ? 'selected' : '' }}>Aktif</option>
                                <option value="passive" {{ !$branch->is_active ? 'selected' : '' }}>Pasif</option>
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
                            <input type="text" name="phone" required value="{{ old('phone', $branch->phone) }}"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">E-posta</label>
                            <input type="email" name="email" value="{{ old('email', $branch->email) }}"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Adres *</label>
                            <textarea name="address" required rows="3"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">{{ old('address', $branch->address) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-800">
                    <button type="button" onclick="confirmDelete()" class="px-6 py-2.5 border border-red-300 dark:border-red-900 text-red-600 dark:text-red-400 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors font-medium">
                        İşletmeyi Sil
                    </button>
                    
                    <div class="flex gap-4">
                        <a href="{{ $branch->parent_id ? route('bayi.isletme-detay', $branch->parent_id) : route('bayi.isletmelerim') }}" 
                            class="px-6 py-2.5 border border-gray-300 dark:border-gray-700 rounded-xl text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors font-medium">
                            İptal
                        </a>
                        <button type="submit" 
                            class="px-6 py-2.5 bg-black dark:bg-white text-white dark:text-black rounded-xl hover:scale-105 transition-all duration-200 font-medium shadow-lg hover:shadow-xl">
                            Güncelle
                        </button>
                    </div>
                </div>
            </form>
            
            <form id="delete-form" action="{{ route('bayi.isletme-sil', $branch->id) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>

<script>
    function confirmDelete() {
        if (confirm('Bu işletmeyi silmek istediğinize emin misiniz? Bu işlem geri alınamaz.')) {
            document.getElementById('delete-form').submit();
        }
    }
</script>
@endsection

