<x-bayi-layout>
    <x-slot name="title">Siparis Analitik - Bayi Paneli</x-slot>

    <div class="space-y-6" x-data="analyticsApp()">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Siparis Analitik</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $startDate->format('d.m.Y') }} - {{ $endDate->format('d.m.Y') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <select id="branchFilter" onchange="applyFilters()" class="px-4 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg text-sm">
                    <option value="">Tum Subeler</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
                <select id="periodFilter" onchange="applyFilters()" class="px-4 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg text-sm">
                    <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Bugun</option>
                    <option value="yesterday" {{ $period === 'yesterday' ? 'selected' : '' }}>Dun</option>
                    <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Bu Hafta</option>
                    <option value="last_week" {{ $period === 'last_week' ? 'selected' : '' }}>Gecen Hafta</option>
                    <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bu Ay</option>
                    <option value="last_month" {{ $period === 'last_month' ? 'selected' : '' }}>Gecen Ay</option>
                    <option value="quarter" {{ $period === 'quarter' ? 'selected' : '' }}>Bu Ceyrek</option>
                    <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Bu Yil</option>
                </select>
            </div>
        </div>

        <!-- Real-time Stats Bar -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl p-4 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                    <span class="text-sm font-medium">Canli</span>
                </div>
                <div class="flex items-center gap-8">
                    <div class="text-center">
                        <p class="text-2xl font-bold">{{ $realTimeStats['pending'] }}</p>
                        <p class="text-xs text-white/70">Bekleyen</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold">{{ $realTimeStats['on_way'] }}</p>
                        <p class="text-xs text-white/70">Yolda</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold">{{ $realTimeStats['delivered'] }}</p>
                        <p class="text-xs text-white/70">Teslim</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold">{{ number_format($realTimeStats['revenue_today'], 0) }} TL</p>
                        <p class="text-xs text-white/70">Bugunun Geliri</p>
                    </div>
                    <div class="text-center border-l border-white/20 pl-8">
                        <p class="text-2xl font-bold">{{ $realTimeStats['last_hour'] }}</p>
                        <p class="text-xs text-white/70">Son 1 Saat</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-500 text-sm">Toplam Siparis</span>
                    @if($overviewStats['order_growth'] > 0)
                        <span class="text-green-500 text-xs">+{{ $overviewStats['order_growth'] }}%</span>
                    @elseif($overviewStats['order_growth'] < 0)
                        <span class="text-red-500 text-xs">{{ $overviewStats['order_growth'] }}%</span>
                    @endif
                </div>
                <p class="text-3xl font-bold text-black dark:text-white">{{ number_format($overviewStats['total_orders']) }}</p>
            </div>

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-500 text-sm">Toplam Gelir</span>
                    @if($overviewStats['revenue_growth'] > 0)
                        <span class="text-green-500 text-xs">+{{ $overviewStats['revenue_growth'] }}%</span>
                    @elseif($overviewStats['revenue_growth'] < 0)
                        <span class="text-red-500 text-xs">{{ $overviewStats['revenue_growth'] }}%</span>
                    @endif
                </div>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($overviewStats['total_revenue'], 0) }} TL</p>
            </div>

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <span class="text-gray-500 text-sm">Ort. Siparis Tutari</span>
                <p class="text-3xl font-bold text-black dark:text-white mt-2">{{ number_format($overviewStats['avg_order_value'], 0) }} TL</p>
            </div>

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <span class="text-gray-500 text-sm">Ort. Teslimat Suresi</span>
                <p class="text-3xl font-bold text-black dark:text-white mt-2">{{ $overviewStats['avg_delivery_time'] }} dk</p>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-green-500 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-green-800 dark:text-green-200">Teslimat Orani</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">%{{ $overviewStats['delivery_rate'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4 border border-red-200 dark:border-red-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-red-500 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-red-800 dark:text-red-200">Iptal Orani</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">%{{ $overviewStats['cancel_rate'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-500 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-blue-800 dark:text-blue-200">Teslim Edilen</p>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($overviewStats['delivered_orders']) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4 border border-orange-200 dark:border-orange-800">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-orange-500 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-orange-800 dark:text-orange-200">Iptal Edilen</p>
                        <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($overviewStats['cancelled_orders']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Daily Trend -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="font-semibold text-black dark:text-white mb-4">Gunluk Siparis Trendi</h3>
                <div class="h-72">
                    <canvas id="dailyTrendChart"></canvas>
                </div>
            </div>

            <!-- Hourly Distribution -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="font-semibold text-black dark:text-white mb-4">Saatlik Siparis Dagilimi</h3>
                <div class="h-72">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Second Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Payment Methods -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="font-semibold text-black dark:text-white mb-4">Odeme Yontemleri</h3>
                <div class="h-48 flex items-center justify-center">
                    <canvas id="paymentChart"></canvas>
                </div>
                <div class="mt-4 space-y-2">
                    @foreach($paymentMethods as $method)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full" style="background-color: {{ $method['method'] === 'cash' ? '#10B981' : ($method['method'] === 'credit_card' ? '#3B82F6' : '#8B5CF6') }}"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $method['label'] }}</span>
                        </div>
                        <span class="text-sm font-medium text-black dark:text-white">%{{ $method['percentage'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="font-semibold text-black dark:text-white mb-4">Siparis Durumlari</h3>
                <div class="space-y-3">
                    @foreach($statusDistribution as $status)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $status['label'] }}</span>
                            <span class="text-sm font-medium text-black dark:text-white">{{ $status['count'] }}</span>
                        </div>
                        <div class="h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div class="h-full rounded-full" style="width: {{ $status['percentage'] }}%; background-color: {{ $status['color'] }}"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Top Zones -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="font-semibold text-black dark:text-white mb-4">En Cok Siparis Alan Bolgeler</h3>
                <div class="space-y-3">
                    @foreach(array_slice($topZones, 0, 5) as $index => $zone)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 flex items-center justify-center text-xs font-medium rounded-full {{ $index === 0 ? 'bg-yellow-100 text-yellow-700' : ($index === 1 ? 'bg-gray-100 text-gray-700' : ($index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-gray-50 text-gray-500')) }}">
                                {{ $index + 1 }}
                            </span>
                            <span class="text-sm text-gray-700 dark:text-gray-300 truncate max-w-[120px]">{{ $zone['zone'] }}</span>
                        </div>
                        <span class="text-sm font-medium text-black dark:text-white">{{ $zone['orders'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Courier Performance -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-black dark:text-white">Kurye Performansi</h3>
                <a href="{{ route('bayi.vardiya.analytics') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Detayli Gorus &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kurye</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Teslimat</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Gelir</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ort. Sure</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse(array_slice($courierPerformance, 0, 5) as $index => $courier)
                        <tr>
                            <td class="px-4 py-3">
                                @if($index === 0)
                                    <span class="text-yellow-500 text-lg">&#x1F947;</span>
                                @elseif($index === 1)
                                    <span class="text-gray-400 text-lg">&#x1F948;</span>
                                @elseif($index === 2)
                                    <span class="text-orange-500 text-lg">&#x1F949;</span>
                                @else
                                    <span class="text-gray-500">{{ $index + 1 }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-black dark:text-white">{{ $courier['name'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full text-sm font-medium">
                                    {{ $courier['deliveries'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-green-600 dark:text-green-400 font-medium">
                                {{ number_format($courier['revenue'], 0) }} TL
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">
                                {{ $courier['avg_delivery_time'] }} dk
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">Veri bulunamadi</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('bayi.analytics.weekly') }}" class="block p-6 bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 hover:border-black dark:hover:border-white transition-colors">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-black dark:text-white">Haftalik Karsilastirma</h4>
                        <p class="text-sm text-gray-500">Bu hafta vs gecen hafta</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('bayi.analytics.branches') }}" class="block p-6 bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 hover:border-black dark:hover:border-white transition-colors">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-black dark:text-white">Sube Karsilastirma</h4>
                        <p class="text-sm text-gray-500">Sube bazli performans</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('bayi.analytics.heatmap') }}" class="block p-6 bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 hover:border-black dark:hover:border-white transition-colors">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-black dark:text-white">Siparis Heatmap</h4>
                        <p class="text-sm text-gray-500">Gun/saat bazli yogunluk</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function analyticsApp() {
            return {
                init() {
                    this.initCharts();
                }
            };
        }

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

        document.addEventListener('DOMContentLoaded', function() {
            // Daily Trend Chart
            const dailyData = @json($dailyTrend);
            const dailyCtx = document.getElementById('dailyTrendChart')?.getContext('2d');
            if (dailyCtx) {
                new Chart(dailyCtx, {
                    type: 'line',
                    data: {
                        labels: dailyData.map(d => d.display_date),
                        datasets: [{
                            label: 'Siparisler',
                            data: dailyData.map(d => d.orders),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y',
                        }, {
                            label: 'Gelir (TL)',
                            data: dailyData.map(d => d.revenue),
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'transparent',
                            tension: 0.4,
                            yAxisID: 'y1',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                beginAtZero: true,
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                beginAtZero: true,
                                grid: {
                                    drawOnChartArea: false,
                                },
                            }
                        }
                    }
                });
            }

            // Hourly Chart
            const hourlyData = @json($hourlyDistribution);
            const hourlyCtx = document.getElementById('hourlyChart')?.getContext('2d');
            if (hourlyCtx) {
                new Chart(hourlyCtx, {
                    type: 'bar',
                    data: {
                        labels: hourlyData.map(d => d.hour),
                        datasets: [{
                            label: 'Siparisler',
                            data: hourlyData.map(d => d.orders),
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
            }

            // Payment Methods Chart
            const paymentData = @json($paymentMethods);
            const paymentCtx = document.getElementById('paymentChart')?.getContext('2d');
            if (paymentCtx) {
                new Chart(paymentCtx, {
                    type: 'doughnut',
                    data: {
                        labels: paymentData.map(d => d.label),
                        datasets: [{
                            data: paymentData.map(d => d.count),
                            backgroundColor: ['#10B981', '#3B82F6', '#8B5CF6'],
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        cutout: '60%',
                    }
                });
            }
        });
    </script>
    @endpush
</x-bayi-layout>
