<x-bayi-layout>
    <x-slot name="title">Sube Karsilastirma - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('bayi.analytics.index') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-black dark:text-white">Sube Karsilastirma</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $startDate->format('d.m.Y') }} - {{ $endDate->format('d.m.Y') }}</p>
                </div>
            </div>
            <select onchange="window.location.href='?period='+this.value" class="px-4 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg text-sm">
                <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Bu Hafta</option>
                <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bu Ay</option>
                <option value="quarter" {{ $period === 'quarter' ? 'selected' : '' }}>Bu Ceyrek</option>
                <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Bu Yil</option>
            </select>
        </div>

        <!-- Branch Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($branchComparison as $index => $branch)
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6 {{ $index === 0 ? 'ring-2 ring-yellow-400' : '' }}">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        @if($index === 0)
                            <span class="text-2xl">&#x1F947;</span>
                        @elseif($index === 1)
                            <span class="text-2xl">&#x1F948;</span>
                        @elseif($index === 2)
                            <span class="text-2xl">&#x1F949;</span>
                        @else
                            <span class="w-8 h-8 flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-full text-sm font-medium">{{ $index + 1 }}</span>
                        @endif
                        <h3 class="font-semibold text-black dark:text-white">{{ $branch['name'] }}</h3>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <p class="text-gray-500 text-sm">Toplam Gelir</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($branch['revenue'], 0) }} TL</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-500 text-sm">Siparisler</p>
                            <p class="text-xl font-semibold text-black dark:text-white">{{ number_format($branch['total_orders']) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Teslim</p>
                            <p class="text-xl font-semibold text-black dark:text-white">{{ number_format($branch['delivered_orders']) }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-500 text-sm">Ort. Siparis</p>
                            <p class="text-lg font-semibold text-black dark:text-white">{{ number_format($branch['avg_order_value'], 0) }} TL</p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Teslimat Orani</p>
                            <p class="text-lg font-semibold {{ $branch['delivery_rate'] >= 90 ? 'text-green-600' : ($branch['delivery_rate'] >= 80 ? 'text-yellow-600' : 'text-red-600') }}">
                                %{{ $branch['delivery_rate'] }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-12 text-gray-500">
                Sube verisi bulunamadi.
            </div>
            @endforelse
        </div>

        <!-- Comparison Table -->
        @if(count($branchComparison) > 0)
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <h3 class="font-semibold text-black dark:text-white mb-4">Detayli Tablo</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sube</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Siparis</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Teslim</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Gelir</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ort. Siparis</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Teslimat %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach($branchComparison as $index => $branch)
                        <tr>
                            <td class="px-4 py-3 text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-black dark:text-white">{{ $branch['name'] }}</td>
                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ number_format($branch['total_orders']) }}</td>
                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ number_format($branch['delivered_orders']) }}</td>
                            <td class="px-4 py-3 text-center font-semibold text-green-600 dark:text-green-400">{{ number_format($branch['revenue'], 0) }} TL</td>
                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ number_format($branch['avg_order_value'], 0) }} TL</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded-full text-sm {{ $branch['delivery_rate'] >= 90 ? 'bg-green-100 text-green-700' : ($branch['delivery_rate'] >= 80 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                    %{{ $branch['delivery_rate'] }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-bayi-layout>
