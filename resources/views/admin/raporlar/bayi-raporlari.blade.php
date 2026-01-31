@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Bayi Raporları</h1>
            <p class="text-gray-600 dark:text-gray-400">Bayi bazlı ciro ve performans analizi</p>
        </div>
        <a href="{{ route('admin.raporlar.bayi-export', request()->all()) }}"
           class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>CSV İndir</span>
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Toplam Bayi</p>
            <p class="text-2xl font-bold text-black dark:text-white">{{ $genelToplam['bayi_sayisi'] }}</p>
        </div>
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Toplam İşletme</p>
            <p class="text-2xl font-bold text-black dark:text-white">{{ $genelToplam['isletme_sayisi'] }}</p>
        </div>
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Toplam Sipariş</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($genelToplam['total_siparis']) }}</p>
        </div>
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">Toplam Ciro</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($genelToplam['total_ciro'], 2) }} ₺</p>
        </div>
    </div>

    <!-- Monthly Comparison -->
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Bu Ay</p>
                    <p class="text-xl font-bold text-black dark:text-white">{{ number_format($genelToplam['bu_ay_ciro'], 2) }} ₺</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($genelToplam['bu_ay_siparis']) }} sipariş</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Geçen Ay</p>
                    <p class="text-xl font-bold text-black dark:text-white">{{ number_format($genelToplam['gecen_ay_ciro'], 2) }} ₺</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($genelToplam['gecen_ay_siparis']) }} sipariş</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-4 mb-6">
        <form action="{{ route('admin.raporlar.bayi') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm text-gray-500 dark:text-gray-400 mb-1">Başlangıç Tarihi</label>
                <input type="date" name="start_date" value="{{ $filters['start_date'] }}"
                    class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm text-gray-500 dark:text-gray-400 mb-1">Bitiş Tarihi</label>
                <input type="date" name="end_date" value="{{ $filters['end_date'] }}"
                    class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
            </div>
            <div class="min-w-[150px]">
                <label class="block text-sm text-gray-500 dark:text-gray-400 mb-1">Sıralama</label>
                <select name="sort" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <option value="total_ciro" {{ $filters['sort'] === 'total_ciro' ? 'selected' : '' }}>Toplam Ciro</option>
                    <option value="total_siparis" {{ $filters['sort'] === 'total_siparis' ? 'selected' : '' }}>Toplam Sipariş</option>
                    <option value="bu_ay_ciro" {{ $filters['sort'] === 'bu_ay_ciro' ? 'selected' : '' }}>Bu Ay Ciro</option>
                    <option value="isletme_sayisi" {{ $filters['sort'] === 'isletme_sayisi' ? 'selected' : '' }}>İşletme Sayısı</option>
                    <option value="kurye_sayisi" {{ $filters['sort'] === 'kurye_sayisi' ? 'selected' : '' }}>Kurye Sayısı</option>
                    <option value="name" {{ $filters['sort'] === 'name' ? 'selected' : '' }}>Bayi Adı</option>
                </select>
            </div>
            <div class="min-w-[100px]">
                <label class="block text-sm text-gray-500 dark:text-gray-400 mb-1">Yön</label>
                <select name="dir" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <option value="desc" {{ $filters['dir'] === 'desc' ? 'selected' : '' }}>Azalan</option>
                    <option value="asc" {{ $filters['dir'] === 'asc' ? 'selected' : '' }}>Artan</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                Filtrele
            </button>
            @if(request()->hasAny(['start_date', 'end_date', 'sort', 'dir']))
            <a href="{{ route('admin.raporlar.bayi') }}" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white">
                Temizle
            </a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-black">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bayi</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Abonelik</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İşletme</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kurye</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Toplam Sipariş</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Toplam Ciro</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bu Ay</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Geçen Ay</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Başarı</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($bayiler as $bayi)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                        <td class="px-4 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-blue-600 dark:text-blue-400 font-medium text-sm">{{ strtoupper(substr($bayi['name'], 0, 2)) }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-black dark:text-white truncate">{{ $bayi['name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $bayi['email'] }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($bayi['subscription_status'] === 'active')
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    {{ $bayi['subscription'] }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                    Yok
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center text-sm text-black dark:text-white">
                            {{ $bayi['isletme_sayisi'] }}
                        </td>
                        <td class="px-4 py-4 text-center text-sm text-black dark:text-white">
                            {{ $bayi['kurye_sayisi'] }}
                        </td>
                        <td class="px-4 py-4 text-right text-sm font-medium text-black dark:text-white">
                            {{ number_format($bayi['total_siparis']) }}
                        </td>
                        <td class="px-4 py-4 text-right">
                            <span class="text-sm font-bold text-green-600 dark:text-green-400">{{ number_format($bayi['total_ciro'], 2) }} ₺</span>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <p class="text-sm font-medium text-black dark:text-white">{{ number_format($bayi['bu_ay_ciro'], 2) }} ₺</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $bayi['bu_ay_siparis'] }} sipariş</p>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <p class="text-sm text-black dark:text-white">{{ number_format($bayi['gecen_ay_ciro'], 2) }} ₺</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $bayi['gecen_ay_siparis'] }} sipariş</p>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="flex items-center justify-center">
                                <div class="w-12 h-12 relative">
                                    <svg class="w-12 h-12 transform -rotate-90" viewBox="0 0 36 36">
                                        <circle cx="18" cy="18" r="16" fill="none" stroke="currentColor" stroke-width="3"
                                            class="text-gray-200 dark:text-gray-700"/>
                                        <circle cx="18" cy="18" r="16" fill="none" stroke="currentColor" stroke-width="3"
                                            stroke-dasharray="{{ $bayi['tamamlanma_orani'] }} 100"
                                            class="{{ $bayi['tamamlanma_orani'] >= 80 ? 'text-green-500' : ($bayi['tamamlanma_orani'] >= 50 ? 'text-yellow-500' : 'text-red-500') }}"/>
                                    </svg>
                                    <span class="absolute inset-0 flex items-center justify-center text-xs font-medium text-black dark:text-white">
                                        %{{ $bayi['tamamlanma_orani'] }}
                                    </span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400">Henüz bayi bulunmuyor</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($bayiler->count() > 0)
                <tfoot class="bg-gray-50 dark:bg-black border-t-2 border-gray-300 dark:border-gray-600">
                    <tr class="font-bold">
                        <td class="px-4 py-3 text-black dark:text-white">TOPLAM</td>
                        <td class="px-4 py-3 text-center text-black dark:text-white">-</td>
                        <td class="px-4 py-3 text-center text-black dark:text-white">{{ $genelToplam['isletme_sayisi'] }}</td>
                        <td class="px-4 py-3 text-center text-black dark:text-white">{{ $genelToplam['kurye_sayisi'] }}</td>
                        <td class="px-4 py-3 text-right text-black dark:text-white">{{ number_format($genelToplam['total_siparis']) }}</td>
                        <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ number_format($genelToplam['total_ciro'], 2) }} ₺</td>
                        <td class="px-4 py-3 text-right text-black dark:text-white">{{ number_format($genelToplam['bu_ay_ciro'], 2) }} ₺</td>
                        <td class="px-4 py-3 text-right text-black dark:text-white">{{ number_format($genelToplam['gecen_ay_ciro'], 2) }} ₺</td>
                        <td class="px-4 py-3 text-center text-black dark:text-white">-</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
