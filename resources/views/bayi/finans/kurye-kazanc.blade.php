<x-bayi-layout>
    <x-slot name="title">Kurye Kazanc Raporu - Bayi Paneli</x-slot>

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
                        <h1 class="text-3xl font-bold text-black dark:text-white">Kurye Kazanc Raporu</h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $startDate->format('d.m.Y') }} - {{ $endDate->format('d.m.Y') }}</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <select onchange="window.location.href='?period='+this.value" class="px-4 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg text-sm">
                    <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Bu Hafta</option>
                    <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bu Ay</option>
                    <option value="last_month" {{ $period === 'last_month' ? 'selected' : '' }}>Gecen Ay</option>
                </select>
                <a href="{{ route('bayi.finans.export') }}?type=courier&period={{ $period }}&format=csv"
                   class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors text-sm">
                    Disari Aktar
                </a>
            </div>
        </div>

        <!-- Courier Table -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kurye</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Teslimat</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kazanc</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Toplanan Nakit</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ort. Sure</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Gunluk Ort.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($courierEarnings as $courier)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center">
                                            <span class="text-orange-600 dark:text-orange-400 font-semibold">{{ substr($courier['courier_name'], 0, 1) }}</span>
                                        </div>
                                        <span class="font-medium text-black dark:text-white">{{ $courier['courier_name'] }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-semibold text-black dark:text-white">{{ $courier['delivery_count'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($courier['total_earnings'], 2) }} TL</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-black dark:text-white">{{ number_format($courier['cash_collected'], 2) }} TL</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-gray-600 dark:text-gray-400">{{ $courier['avg_delivery_time'] }} dk</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-gray-600 dark:text-gray-400">{{ $courier['daily_average'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    Bu donemde teslimat yapilmamis.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-bayi-layout>
