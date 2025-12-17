@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-2">
            <a href="{{ route('bayi.kuryelerim') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-black dark:text-white">Yeni Kurye Ekle</h1>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 ml-12">Ekibinize yeni bir kurye dahil edin</p>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm max-w-7xl mx-auto">
        <div class="p-6 sm:p-8">
            <form action="{{ route('couriers.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col" style="gap: 2rem;">
                @csrf
                
                <!-- Kişisel Bilgiler -->
                <div>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4 border-b border-gray-100 dark:border-gray-800 pb-2">Kişisel Bilgiler</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="col-span-1 md:col-span-2 lg:col-span-1">
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Kurye Fotoğrafı</label>
                            <div class="flex items-center gap-4">
                                <div class="w-20 h-20 shrink-0 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center overflow-hidden border border-gray-200 dark:border-gray-700">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <input type="file" name="photo" accept="image/*"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-colors">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Ad Soyad *</label>
                            <input type="text" name="name" required placeholder="Örn: Ahmet Yılmaz"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">T.C. Kimlik No</label>
                            <input type="text" name="tc_no" maxlength="11" placeholder="11 haneli kimlik no"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                    </div>
                </div>

                <!-- İletişim Bilgileri -->
                <div>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4 border-b border-gray-100 dark:border-gray-800 pb-2">İletişim Bilgileri</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Telefon *</label>
                            <input type="text" name="phone" required placeholder="0555 555 55 55"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">E-posta</label>
                            <input type="email" name="email" placeholder="ornek@email.com"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                    </div>
                </div>

                <!-- Araç ve Durum -->
                <div>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4 border-b border-gray-100 dark:border-gray-800 pb-2">Araç ve Durum</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Araç Plakası</label>
                            <input type="text" name="vehicle_plate" placeholder="34 AB 123"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Başlangıç Durumu *</label>
                            <select name="status" required 
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black/50 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="available">Müsait - Hemen iş alabilir</option>
                                <option value="active">Aktif - Sistemde görünür</option>
                                <option value="offline">Çevrimdışı - Pasif durumda</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('bayi.kuryelerim') }}" 
                        class="px-6 py-2.5 border border-gray-300 dark:border-gray-700 rounded-xl text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors font-medium">
                        İptal
                    </a>
                    <button type="submit" 
                        class="px-6 py-2.5 bg-black dark:bg-white text-white dark:text-black rounded-xl hover:scale-105 transition-all duration-200 font-medium shadow-lg hover:shadow-xl">
                        Kurye Oluştur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

