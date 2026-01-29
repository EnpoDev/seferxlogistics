<x-bayi-layout>
    <x-slot name="title">Siparis Heatmap - Bayi Paneli</x-slot>

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
                    <h1 class="text-3xl font-bold text-black dark:text-white">Siparis Heatmap</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $startDate->format('d.m.Y') }} - {{ $endDate->format('d.m.Y') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <select onchange="applyFilters()" id="branchFilter" class="px-4 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg text-sm">
                    <option value="">Tum Subeler</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
                <select onchange="applyFilters()" id="periodFilter" class="px-4 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg text-sm">
                    <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Bu Hafta</option>
                    <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bu Ay</option>
                    <option value="quarter" {{ $period === 'quarter' ? 'selected' : '' }}>Bu Ceyrek</option>
                </select>
            </div>
        </div>

        <!-- Heatmap Grid -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <h3 class="font-semibold text-black dark:text-white mb-4">Gun/Saat Bazli Siparis Yogunlugu</h3>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="px-2 py-2 text-xs font-medium text-gray-500"></th>
                            @for($h = 0; $h < 24; $h++)
                                <th class="px-1 py-2 text-xs font-medium text-gray-500 text-center">{{ sprintf('%02d', $h) }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $days = ['Pazartesi', 'Sali', 'Carsamba', 'Persembe', 'Cuma', 'Cumartesi', 'Pazar'];
                            $maxCount = collect($heatmapData)->max('count') ?: 1;
                        @endphp
                        @foreach($days as $dayIndex => $dayName)
                            <tr>
                                <td class="px-2 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $dayName }}</td>
                                @for($h = 0; $h < 24; $h++)
                                    @php
                                        $cellData = collect($heatmapData)->first(fn($d) => $d['day'] === $dayIndex && $d['hour'] === $h);
                                        $count = $cellData['count'] ?? 0;
                                        $intensity = $maxCount > 0 ? ($count / $maxCount) : 0;
                                    @endphp
                                    <td class="px-1 py-1">
                                        <div class="w-8 h-8 rounded flex items-center justify-center text-xs font-medium cursor-pointer transition-transform hover:scale-110"
                                             style="background-color: rgba(59, 130, 246, {{ $intensity }}); color: {{ $intensity > 0.5 ? 'white' : 'inherit' }}"
                                             title="{{ $dayName }} {{ sprintf('%02d:00', $h) }} - {{ $count }} siparis">
                                            {{ $count > 0 ? $count : '' }}
                                        </div>
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Legend -->
            <div class="mt-6 flex items-center justify-center gap-4">
                <span class="text-sm text-gray-500">Az</span>
                <div class="flex gap-1">
                    @for($i = 0; $i <= 5; $i++)
                        <div class="w-6 h-6 rounded" style="background-color: rgba(59, 130, 246, {{ $i / 5 }})"></div>
                    @endfor
                </div>
                <span class="text-sm text-gray-500">Cok</span>
            </div>
        </div>

        <!-- Insights -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @php
                $peakData = collect($heatmapData)->sortByDesc('count')->first();
                $quietData = collect($heatmapData)->where('count', '>', 0)->sortBy('count')->first();
                $weekendTotal = collect($heatmapData)->whereIn('day', [5, 6])->sum('count');
                $weekdayTotal = collect($heatmapData)->whereIn('day', [0, 1, 2, 3, 4])->sum('count');
            @endphp

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <span class="text-gray-500">En Yogun Zaman</span>
                </div>
                @if($peakData)
                    <p class="text-xl font-bold text-black dark:text-white">{{ $days[$peakData['day']] }} {{ sprintf('%02d:00', $peakData['hour']) }}</p>
                    <p class="text-sm text-gray-500">{{ $peakData['count'] }} siparis</p>
                @else
                    <p class="text-gray-500">Veri yok</p>
                @endif
            </div>

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path>
                        </svg>
                    </div>
                    <span class="text-gray-500">En Sakin Zaman</span>
                </div>
                @if($quietData)
                    <p class="text-xl font-bold text-black dark:text-white">{{ $days[$quietData['day']] }} {{ sprintf('%02d:00', $quietData['hour']) }}</p>
                    <p class="text-sm text-gray-500">{{ $quietData['count'] }} siparis</p>
                @else
                    <p class="text-gray-500">Veri yok</p>
                @endif
            </div>

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <span class="text-gray-500">Hafta Ici vs Sonu</span>
                </div>
                <p class="text-xl font-bold text-black dark:text-white">
                    @if($weekdayTotal + $weekendTotal > 0)
                        %{{ round(($weekendTotal / ($weekdayTotal + $weekendTotal)) * 100) }} Hafta Sonu
                    @else
                        -
                    @endif
                </p>
                <p class="text-sm text-gray-500">{{ $weekendTotal }} / {{ $weekdayTotal + $weekendTotal }} siparis</p>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function applyFilters() {
            const branch = document.getElementById('branchFilter').value;
            const period = document.getElementById('periodFilter').value;
            let url = new URL(window.location.href);
            url.searchParams.set('period', period);
            if (branch) {
                url.searchParams.set('branch_id', branch);
            } else {
                url.searchParams.delete('branch_id');
            }
            window.location.href = url.toString();
        }
    </script>
    @endpush
</x-bayi-layout>
