<x-bayi-layout>
    <x-slot name="title">Yemek Maliyet Raporu - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('bayi.finans.index') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-black dark:text-white">Yemek Maliyet Raporu</h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $startDate->format('d.m.Y') }} - {{ $endDate->format('d.m.Y') }}</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <!-- Filters -->
                <select name="period" onchange="applyFilters()" id="filterPeriod" class="px-4 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg text-sm">
                    <option value="week" {{ ($period ?? '') === 'week' ? 'selected' : '' }}>Bu Hafta</option>
                    <option value="month" {{ ($period ?? '') === 'month' ? 'selected' : '' }}>Bu Ay</option>
                    <option value="last_month" {{ ($period ?? '') === 'last_month' ? 'selected' : '' }}>Gecen Ay</option>
                </select>
                <select name="courier_id" onchange="applyFilters()" id="filterCourier" class="px-4 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg text-sm">
                    <option value="">Tum Kuryeler</option>
                    @foreach($couriers ?? [] as $courier)
                    <option value="{{ $courier->id }}" {{ request('courier_id') == $courier->id ? 'selected' : '' }}>{{ $courier->name }}</option>
                    @endforeach
                </select>
                <select name="meal_type" onchange="applyFilters()" id="filterMealType" class="px-4 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg text-sm">
                    <option value="">Tum Ogunler</option>
                    <option value="breakfast" {{ request('meal_type') === 'breakfast' ? 'selected' : '' }}>Kahvalti</option>
                    <option value="lunch" {{ request('meal_type') === 'lunch' ? 'selected' : '' }}>Ogle</option>
                    <option value="dinner" {{ request('meal_type') === 'dinner' ? 'selected' : '' }}>Aksam</option>
                </select>
                <a href="{{ route('bayi.finans.export') }}?type=meal_cost&period={{ $period ?? 'month' }}&format=csv"
                   class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    CSV Aktar
                </a>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Toplam Harcama -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Toplam Harcama</span>
                    <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-black dark:text-white">{{ number_format($totalSpending ?? 0, 2) }} TL</p>
                <p class="text-xs text-gray-400 mt-1">Bu donem toplam yemek harcamasi</p>
            </div>

            <!-- Toplam Hak -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Toplam Hak</span>
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-black dark:text-white">{{ $totalEntitlements ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-1">Toplam ogun hakki</p>
            </div>

            <!-- Kullanim Orani -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Kullanim Orani</span>
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-black dark:text-white">%{{ number_format($usageRate ?? 0, 1) }}</p>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ min($usageRate ?? 0, 100) }}%"></div>
                </div>
            </div>

            <!-- Ortalama Ogun Maliyeti -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-5">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Ort. Ogun Maliyeti</span>
                    <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-black dark:text-white">{{ number_format($avgMealCost ?? 0, 2) }} TL</p>
                <p class="text-xs text-gray-400 mt-1">Ogun basina ortalama maliyet</p>
            </div>
        </div>

        <!-- Courier Table -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-black dark:text-white">Kurye Bazli Yemek Raporu</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kurye</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Toplam Hak</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kullanilan</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tutar</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kullanim Orani</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($courierMealData ?? [] as $data)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center">
                                        <span class="text-orange-600 dark:text-orange-400 font-semibold">{{ substr($data['courier_name'], 0, 1) }}</span>
                                    </div>
                                    <span class="font-medium text-black dark:text-white">{{ $data['courier_name'] }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="font-semibold text-black dark:text-white">{{ $data['total_entitlements'] }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="font-semibold text-blue-600 dark:text-blue-400">{{ $data['used'] }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="font-semibold text-red-600 dark:text-red-400">{{ number_format($data['total_cost'], 2) }} TL</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="h-2 rounded-full {{ $data['usage_rate'] >= 80 ? 'bg-green-500' : ($data['usage_rate'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                             style="width: {{ min($data['usage_rate'], 100) }}%"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">%{{ number_format($data['usage_rate'], 0) }}</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                Bu donemde yemek hakki kullanimi bulunmamaktadir.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Two Column Layout: Restaurant Distribution + Daily Trend -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Restaurant Distribution (Horizontal Bars) -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Restoran Dagilimi</h2>
                <div class="space-y-4">
                    @forelse($restaurantDistribution ?? [] as $restaurant)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $restaurant['name'] }}</span>
                            <span class="text-sm text-gray-500">{{ $restaurant['count'] }} ogun ({{ number_format($restaurant['percentage'], 1) }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-blue-500 dark:bg-blue-400 h-3 rounded-full transition-all" style="width: {{ $restaurant['percentage'] }}%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">{{ number_format($restaurant['total_cost'], 2) }} TL</p>
                    </div>
                    @empty
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <p>Restoran verisi bulunamadi</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Daily Trend -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Gunluk Harcama Trendi</h2>
                <div class="space-y-3">
                    @forelse($dailyTrend ?? [] as $day)
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-500 dark:text-gray-400 w-20 flex-shrink-0">{{ $day['date'] }}</span>
                        <div class="flex-1">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                @php $maxDailySpend = collect($dailyTrend)->max('amount'); @endphp
                                <div class="bg-orange-500 dark:bg-orange-400 h-2.5 rounded-full transition-all"
                                     style="width: {{ $maxDailySpend > 0 ? ($day['amount'] / $maxDailySpend * 100) : 0 }}%"></div>
                            </div>
                        </div>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-16 text-right flex-shrink-0">{{ number_format($day['amount'], 2) }} TL</span>
                        <span class="text-xs text-gray-400 w-10 text-right flex-shrink-0">{{ $day['count'] }} ogun</span>
                    </div>
                    @empty
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        <p>Gunluk veri bulunamadi</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
    function applyFilters() {
        const period = document.getElementById('filterPeriod').value;
        const courierId = document.getElementById('filterCourier').value;
        const mealType = document.getElementById('filterMealType').value;
        let url = window.location.pathname + '?period=' + period;
        if (courierId) url += '&courier_id=' + courierId;
        if (mealType) url += '&meal_type=' + mealType;
        window.location.href = url;
    }
    </script>
</x-bayi-layout>
