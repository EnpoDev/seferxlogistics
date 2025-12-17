@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-2">
            <a href="{{ route('bayi.isletmelerim') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-black dark:text-white flex items-center gap-2">
                    {{ $branch->name }}
                    @if($branch->is_main)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200">
                            Merkez
                        </span>
                    @endif
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">İşletme detayları ve alt şubeleri</p>
            </div>
            
            <div class="ml-auto flex gap-3">
                <a href="{{ route('bayi.isletme-duzenle', $branch->id) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-xl text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors font-medium">
                    Düzenle
                </a>
                <a href="{{ route('bayi.isletme-ekle', ['parent_id' => $branch->id]) }}" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-xl hover:scale-105 transition-all duration-200 font-medium shadow-lg hover:shadow-xl">
                    Şube Ekle
                </a>
            </div>
        </div>
    </div>

    <!-- İşletme Bilgileri -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- İletişim Kartı -->
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">İletişim Bilgileri</h3>
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600 dark:text-blue-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Telefon</p>
                        <p class="text-base font-medium text-black dark:text-white">{{ $branch->phone }}</p>
                    </div>
                </div>
                
                @if($branch->email)
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-purple-600 dark:text-purple-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">E-posta</p>
                        <p class="text-base font-medium text-black dark:text-white">{{ $branch->email }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Adres Kartı -->
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Adres Bilgisi</h3>
            <div class="flex items-start gap-3">
                <div class="p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg text-orange-600 dark:text-orange-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-base text-black dark:text-white">{{ $branch->address }}</p>
                    @if($branch->lat && $branch->lng)
                        <a href="https://maps.google.com/?q={{ $branch->lat }},{{ $branch->lng }}" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">Haritada Göster &rarr;</a>
                    @endif
                </div>
            </div>
        </div>

        <!-- İstatistik Kartı -->
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Şube Özeti</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Alt Şubeler</p>
                    <p class="text-2xl font-bold text-black dark:text-white">{{ $children->count() }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Durum</p>
                    <p class="text-base font-bold mt-1">
                        @if($branch->is_active)
                            <span class="text-green-600 dark:text-green-400">Aktif</span>
                        @else
                            <span class="text-gray-600 dark:text-gray-400">Pasif</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Alt Şubeler Listesi -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-black dark:text-white">Bağlı Şubeler</h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $children->count() }} şube listeleniyor</span>
        </div>
        
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
            @if($children->isEmpty())
                <div class="p-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-black dark:text-white mb-2">Henüz şube eklenmemiş</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">Bu işletmeye bağlı herhangi bir şube bulunmuyor.</p>
                    <a href="{{ route('bayi.isletme-ekle', ['parent_id' => $branch->id]) }}" class="px-6 py-2.5 bg-black dark:bg-white text-white dark:text-black rounded-xl hover:scale-105 transition-all duration-200 font-medium inline-flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Şube Ekle
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Şube Adı</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">İletişim</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Adres</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Durum</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach($children as $child)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-500 dark:text-gray-400 font-bold overflow-hidden">
                                            {{ substr($child->name, 0, 2) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-black dark:text-white">{{ $child->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">ID: #{{ $child->id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="text-sm text-black dark:text-white flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                            </svg>
                                            {{ $child->phone }}
                                        </p>
                                        @if($child->email)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            {{ $child->email }}
                                        </p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 max-w-[200px]">
                                        {{ $child->address }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    @if($child->is_active)
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full border bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-current mr-1.5 animate-pulse-slow"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full border bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-300 border-gray-200 dark:border-gray-800">
                                            Pasif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('bayi.isletme-duzenle', $child->id) }}" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Düzenle
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

