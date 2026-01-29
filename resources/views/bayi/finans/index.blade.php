<x-bayi-layout>
    <x-slot name="title">Finansal Raporlar - Bayi Paneli</x-slot>

    <div class="space-y-6" x-data="financialDashboard()">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Finansal Raporlar</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Gelir, gider ve performans analizleri</p>
            </div>
            <div class="flex items-center gap-3">
                <!-- Period Selector -->
                <select x-model="period" @change="loadData()" class="px-4 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg text-sm">
                    <option value="today">Bugun</option>
                    <option value="yesterday">Dun</option>
                    <option value="week" selected>Bu Hafta</option>
                    <option value="month">Bu Ay</option>
                    <option value="last_month">Gecen Ay</option>
                    <option value="quarter">Bu Ceyrek</option>
                    <option value="year">Bu Yil</option>
                </select>
                <!-- Export Button -->
                <a :href="`{{ route('bayi.finans.export') }}?type=summary&period=${period}&format=csv`"
                   class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Disari Aktar
                </a>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Revenue -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Toplam Gelir</p>
                        <p class="text-2xl font-bold text-black dark:text-white mt-1">{{ number_format($summary['revenue']['total'], 2) }} TL</p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-center text-sm">
                    @if(($weeklyComparison['changes']['revenue_percent'] ?? 0) >= 0)
                        <span class="text-green-600 dark:text-green-400 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                            {{ $weeklyComparison['changes']['revenue_percent'] ?? 0 }}%
                        </span>
                    @else
                        <span class="text-red-600 dark:text-red-400 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                            {{ abs($weeklyComparison['changes']['revenue_percent'] ?? 0) }}%
                        </span>
                    @endif
                    <span class="text-gray-500 ml-2">gecen haftaya gore</span>
                </div>
            </div>

            <!-- Order Count -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Siparis Sayisi</p>
                        <p class="text-2xl font-bold text-black dark:text-white mt-1">{{ $summary['orders']['count'] }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 text-sm text-gray-500">
                    Ortalama: {{ number_format($summary['orders']['average_value'], 2) }} TL
                </div>
            </div>

            <!-- Delivery Fees -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Teslimat Geliri</p>
                        <p class="text-2xl font-bold text-black dark:text-white mt-1">{{ number_format($summary['revenue']['delivery_fees'], 2) }} TL</p>
                    </div>
                    <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Daily Average -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Gunluk Ortalama</p>
                        <p class="text-2xl font-bold text-black dark:text-white mt-1">{{ number_format($summary['revenue']['daily_average'], 2) }} TL</p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Daily Revenue Chart -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="font-semibold text-black dark:text-white mb-4">Gunluk Gelir</h3>
                <div class="h-64">
                    <canvas id="dailyRevenueChart"></canvas>
                </div>
            </div>

            <!-- Hourly Distribution -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="font-semibold text-black dark:text-white mb-4">Saatlik Siparis Dagilimi</h3>
                <div class="h-64">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Payment Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Payment Methods -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="font-semibold text-black dark:text-white mb-4">Odeme Yontemleri</h3>
                <div class="space-y-4">
                    @foreach($summary['payment_breakdown'] ?? [] as $method => $data)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full {{ $method === 'cash' ? 'bg-green-500' : ($method === 'card' ? 'bg-blue-500' : 'bg-purple-500') }}"></div>
                                <span class="text-gray-700 dark:text-gray-300">
                                    {{ $method === 'cash' ? 'Nakit' : ($method === 'card' ? 'Kart' : 'Online') }}
                                </span>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-black dark:text-white">{{ number_format($data['total'], 2) }} TL</p>
                                <p class="text-xs text-gray-500">{{ $data['count'] }} siparis</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="font-semibold text-black dark:text-white mb-4">Haftalik Karsilastirma</h3>
                <div class="space-y-4">
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <p class="text-sm text-gray-500 mb-1">Bu Hafta</p>
                        <p class="text-xl font-bold text-black dark:text-white">{{ number_format($weeklyComparison['this_week']['revenue']['total'] ?? 0, 2) }} TL</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <p class="text-sm text-gray-500 mb-1">Gecen Hafta</p>
                        <p class="text-xl font-bold text-black dark:text-white">{{ number_format($weeklyComparison['last_week']['revenue']['total'] ?? 0, 2) }} TL</p>
                    </div>
                </div>
            </div>

            <!-- Monthly Stats -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="font-semibold text-black dark:text-white mb-4">Aylik Karsilastirma</h3>
                <div class="space-y-4">
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <p class="text-sm text-gray-500 mb-1">Bu Ay</p>
                        <p class="text-xl font-bold text-black dark:text-white">{{ number_format($monthlyComparison['this_month']['revenue']['total'] ?? 0, 2) }} TL</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <p class="text-sm text-gray-500 mb-1">Gecen Ay</p>
                        <p class="text-xl font-bold text-black dark:text-white">{{ number_format($monthlyComparison['last_month']['revenue']['total'] ?? 0, 2) }} TL</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('bayi.finans.kurye-kazanc') }}" class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6 hover:border-orange-500 transition-colors group">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg group-hover:bg-orange-500 transition-colors">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-black dark:text-white">Kurye Kazanc Raporu</h4>
                        <p class="text-sm text-gray-500">Detayli kurye performansi</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('bayi.finans.sube-performans') }}" class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6 hover:border-blue-500 transition-colors group">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg group-hover:bg-blue-500 transition-colors">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-black dark:text-white">Sube Performansi</h4>
                        <p class="text-sm text-gray-500">Sube bazli analiz</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('bayi.finans.nakit-akis') }}" class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6 hover:border-green-500 transition-colors group">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg group-hover:bg-green-500 transition-colors">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-black dark:text-white">Nakit Akis</h4>
                        <p class="text-sm text-gray-500">Gelir-gider analizi</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function financialDashboard() {
            return {
                period: '{{ $period }}',
                init() {
                    this.initCharts();
                },
                initCharts() {
                    // Daily Revenue Chart
                    const dailyData = @json($dailyRevenue);
                    new Chart(document.getElementById('dailyRevenueChart'), {
                        type: 'line',
                        data: {
                            labels: dailyData.map(d => d.date),
                            datasets: [{
                                label: 'Gelir (TL)',
                                data: dailyData.map(d => d.total_revenue),
                                borderColor: '#f97316',
                                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });

                    // Hourly Distribution Chart
                    const hourlyData = @json($hourlyDistribution);
                    new Chart(document.getElementById('hourlyChart'), {
                        type: 'bar',
                        data: {
                            labels: hourlyData.map(d => d.hour),
                            datasets: [{
                                label: 'Siparis',
                                data: hourlyData.map(d => d.count),
                                backgroundColor: '#3b82f6'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });
                },
                loadData() {
                    window.location.href = `{{ route('bayi.finans.index') }}?period=${this.period}`;
                }
            }
        }
    </script>
    @endpush
</x-bayi-layout>
