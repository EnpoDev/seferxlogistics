<x-bayi-layout>
    <x-slot name="title">Gelişmiş İstatistik - Bayi Paneli</x-slot>

    <div class="space-y-6">
        {{-- Page Header --}}
        <x-layout.page-header title="Gelişmiş İstatistikler" :subtitle="$stats['start_date'] . ' - ' . $stats['end_date']">
            <x-slot name="actions">
                <form method="GET" action="{{ route('bayi.gelismis-istatistik') }}">
                    <x-form.select name="period" onchange="this.form.submit()" :options="['7days' => 'Son 7 Gün', '30days' => 'Son 30 Gün', 'this_month' => 'Bu Ay', 'last_month' => 'Geçen Ay']" :selected="$stats['period']" />
                </form>
            </x-slot>
        </x-layout.page-header>

        {{-- KPI Cards --}}
        <x-layout.section title="Anahtar Performans Göstergeleri">
            <x-layout.grid cols="1" mdCols="2" lgCols="3" gap="4">
                <x-ui.stat-card title="Toplam Teslim Edilen" :value="number_format($stats['kpi']['total_delivered'], 0)" subtitle="Başarıyla teslim edilen bayi paketleri" color="blue" icon="check" />
                <x-ui.stat-card title="Toplam İşletme Paketleri" :value="number_format($stats['kpi']['total_business_orders'], 0)" subtitle="İşletmenin kendi kuryeleriyle teslim ettiği" color="purple" icon="package" />
                <x-ui.stat-card title="Toplam Mesafe" :value="number_format($stats['kpi']['total_distance'], 1) . ' km'" subtitle="Bayi paketlerinin toplam kilometresi" color="orange" icon="location" />
                <x-ui.stat-card title="Toplam Gelir" value="TL{{ number_format($stats['kpi']['total_revenue'], 2) }}" subtitle="Net teslimat ücreti geliri" color="green" icon="money" />
                <x-ui.stat-card title="Kurye Ödemesi" value="TL{{ number_format($stats['kpi']['courier_payment'], 2) }}" subtitle="Toplam kurye ödemesi" color="red" icon="wallet" />
                <x-ui.stat-card title="Net Kar" value="TL{{ number_format($stats['kpi']['net_profit'], 2) }}" subtitle="Gelir eksi kurye maliyetleri" color="blue" icon="chart" />
            </x-layout.grid>
        </x-layout.section>

        {{-- Operasyonel Ortalamalar --}}
        <x-layout.section title="Operasyonel Ortalamalar">
            <x-layout.grid cols="1" mdCols="3" gap="4">
                <x-ui.card>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Ort. Teslimat Süresi</p>
                    <p class="text-2xl font-bold text-black dark:text-white">{{ $stats['operational_averages']['avg_delivery_time'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">Teslimat için ortalama süre</p>
                </x-ui.card>
                <x-ui.card>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Ort. Paket Alma Süresi</p>
                    <p class="text-2xl font-bold text-black dark:text-white">{{ $stats['operational_averages']['avg_pickup_time'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">Kuryelerin paketleri işletmeden alma süresi</p>
                </x-ui.card>
                <x-ui.card>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Ort. Hazırlık Süresi</p>
                    <p class="text-2xl font-bold text-black dark:text-white">{{ $stats['operational_averages']['avg_prep_time'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">İşletmeler tarafından hazırlık süresi</p>
                </x-ui.card>
            </x-layout.grid>
        </x-layout.section>

        {{-- KM Ucreti Analizi --}}
        <x-layout.section title="KM Ücreti Analizi">
            <x-layout.grid cols="1" mdCols="3" gap="4">
                <x-ui.card>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">İşletme KM Ücreti</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-500">TL{{ number_format($stats['km_analysis']['business_km_fee'], 2) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Teslimat mesafesi politikalarından elde edilen komisyon</p>
                </x-ui.card>
                <x-ui.card>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Kurye KM Ücreti</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-500">TL{{ number_format($stats['km_analysis']['courier_km_fee'], 2) }}</p>
                    <p class="text-xs text-gray-400 mt-1">KM politikalarından kaynaklı kurye ödemeleri</p>
                </x-ui.card>
                <x-ui.card>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Toplam KM Kazancı</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-500">TL{{ number_format($stats['km_analysis']['total_km_earning'], 2) }}</p>
                    <p class="text-xs text-gray-400 mt-1">İşletme ve kurye KM ücretlerinin toplamı</p>
                </x-ui.card>
            </x-layout.grid>
        </x-layout.section>

        {{-- İptal Analizi --}}
        <x-layout.section title="İptal Analizi">
            <x-layout.grid cols="1" lgCols="2" gap="6">
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">İptal Dağılımı</h3>
                    <div class="h-64 flex items-center justify-center">
                        <canvas id="cancellationChart"></canvas>
                    </div>
                </x-ui.card>
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">İptal Detayları</h3>
                    <x-table.table>
                        <x-table.thead>
                            <x-table.tr :hoverable="false">
                                <x-table.th>Tür</x-table.th>
                                <x-table.th>Adet</x-table.th>
                                <x-table.th>Oran</x-table.th>
                            </x-table.tr>
                        </x-table.thead>
                        <x-table.tbody>
                            <x-table.tr>
                                <x-table.td>Standart İptaller</x-table.td>
                                <x-table.td>{{ $stats['cancellation_analysis']['standard_cancels'] }} adet</x-table.td>
                                <x-table.td>{{ $stats['cancellation_analysis']['standard_ratio'] }}%</x-table.td>
                            </x-table.tr>
                            <x-table.tr>
                                <x-table.td>Ücretli İptaller</x-table.td>
                                <x-table.td>{{ $stats['cancellation_analysis']['paid_cancels'] }} adet</x-table.td>
                                <x-table.td>{{ $stats['cancellation_analysis']['paid_ratio'] }}%</x-table.td>
                            </x-table.tr>
                            <x-table.tr class="font-semibold">
                                <x-table.td>Toplam</x-table.td>
                                <x-table.td>{{ $stats['cancellation_analysis']['total_cancels'] }} adet</x-table.td>
                                <x-table.td>100%</x-table.td>
                            </x-table.tr>
                        </x-table.tbody>
                    </x-table.table>
                </x-ui.card>
            </x-layout.grid>
        </x-layout.section>

        {{-- Zaman İçindeki Performans --}}
        <x-layout.section title="Zaman İçindeki Performans">
            <x-layout.grid cols="1" lgCols="2" gap="6">
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Saatlik Performans</h3>
                    <div class="h-64"><canvas id="hourlyChart"></canvas></div>
                </x-ui.card>
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Günlük Performans</h3>
                    <div class="h-64"><canvas id="dailyChart"></canvas></div>
                </x-ui.card>
            </x-layout.grid>

            {{-- Heatmap --}}
            <x-ui.card class="mt-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Yoğun Teslimat Saatleri</h3>
                <div class="overflow-x-auto">
                    <div class="inline-block min-w-full">
                        <div class="flex text-xs">
                            <div class="w-16"></div>
                            @for($h = 0; $h < 24; $h++)
                                <div class="w-8 text-center text-gray-500 dark:text-gray-400">{{ $h }}</div>
                            @endfor
                        </div>
                        @php
                            $dayNames = ['Pzt', 'Sal', 'Crs', 'Prs', 'Cum', 'Cmt', 'Pzr'];
                            $maxValue = 1;
                            foreach ($stats['time_performance']['heatmap'] as $dayData) {
                                $maxValue = max($maxValue, max($dayData));
                            }
                        @endphp
                        @foreach($stats['time_performance']['heatmap'] as $dayIndex => $dayData)
                            <div class="flex items-center">
                                <div class="w-16 text-xs text-gray-500 dark:text-gray-400">{{ $dayNames[$dayIndex] }}</div>
                                @foreach($dayData as $count)
                                    @php
                                        $intensity = $maxValue > 0 ? ($count / $maxValue) : 0;
                                        $color = $intensity == 0 ? 'bg-gray-100 dark:bg-gray-800' :
                                                ($intensity < 0.25 ? 'bg-blue-200 dark:bg-blue-900' :
                                                ($intensity < 0.5 ? 'bg-blue-400 dark:bg-blue-700' :
                                                ($intensity < 0.75 ? 'bg-blue-600 dark:bg-blue-500' : 'bg-blue-800 dark:bg-blue-300')));
                                    @endphp
                                    <div class="w-8 h-8 {{ $color }} border border-gray-200 dark:border-gray-700 flex items-center justify-center text-xs cursor-pointer hover:ring-2 hover:ring-blue-500" title="{{ $count }} sipariş">
                                        @if($count > 0)<span class="text-xs">{{ $count }}</span>@endif
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-ui.card>
        </x-layout.section>

        {{-- Performans Sıralamaları --}}
        <x-layout.grid cols="1" lgCols="2" gap="6">
            <x-ui.card>
                <h2 class="text-xl font-bold text-black dark:text-white mb-4">En İyi İşletmeler</h2>
                <div class="space-y-3">
                    @forelse($stats['top_branches'] as $index => $branch)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 text-white flex items-center justify-center font-bold text-sm">{{ $index + 1 }}</div>
                                <div>
                                    <p class="font-semibold text-black dark:text-white">{{ $branch->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $branch->orders_count }} sipariş</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-ui.empty-state title="Veri bulunamadı" />
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card>
                <h2 class="text-xl font-bold text-black dark:text-white mb-4">En İyi Kuryeler</h2>
                <div class="space-y-3">
                    @forelse($stats['top_couriers'] as $index => $courier)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-cyan-500 text-white flex items-center justify-center font-bold text-sm">{{ $index + 1 }}</div>
                                <div>
                                    <p class="font-semibold text-black dark:text-white">{{ $courier->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $courier->orders_count }} sipariş</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-ui.empty-state title="Veri bulunamadı" />
                    @endforelse
                </div>
            </x-ui.card>
        </x-layout.grid>

        {{-- Platform Analizi --}}
        <x-layout.section title="Platform Analizi">
            <x-layout.grid cols="1" lgCols="2" gap="6">
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Platform Performansı</h3>
                    <div class="h-64 flex items-center justify-center">
                        @if($stats['platform_analysis']['total_count'] > 0)
                            <canvas id="platformChart"></canvas>
                        @else
                            <p class="text-gray-500 dark:text-gray-400">Veri bulunamadı</p>
                        @endif
                    </div>
                </x-ui.card>
                <x-ui.card>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Platform Detayları</h3>
                    <x-table.table>
                        <x-table.thead>
                            <x-table.tr :hoverable="false">
                                <x-table.th>Tür</x-table.th>
                                <x-table.th>Adet</x-table.th>
                                <x-table.th>Ciro</x-table.th>
                                <x-table.th>Oran</x-table.th>
                            </x-table.tr>
                        </x-table.thead>
                        <x-table.tbody>
                            @forelse($stats['platform_analysis']['platforms'] as $platform)
                                <x-table.tr>
                                    <x-table.td>{{ $platform['platform'] }}</x-table.td>
                                    <x-table.td>{{ $platform['count'] }}</x-table.td>
                                    <x-table.td>TL{{ number_format($platform['revenue'], 2) }}</x-table.td>
                                    <x-table.td>{{ $platform['ratio'] }}%</x-table.td>
                                </x-table.tr>
                            @empty
                                <x-table.empty colspan="4" message="Veri bulunamadı" />
                            @endforelse
                        </x-table.tbody>
                    </x-table.table>
                </x-ui.card>
            </x-layout.grid>
        </x-layout.section>

        {{-- Ödeme Yöntemi Analizi --}}
        <x-ui.card>
            <h2 class="text-xl font-bold text-black dark:text-white mb-4">Ödeme Yöntemi Analizi</h2>
            <x-layout.grid cols="1" lgCols="2" gap="6">
                <div class="h-64 flex items-center justify-center">
                    @if(count($stats['payment_method_analysis']['methods']) > 0)
                        <canvas id="paymentMethodChart"></canvas>
                    @else
                        <p class="text-gray-500 dark:text-gray-400">Veri bulunamadı</p>
                    @endif
                </div>
                <div class="space-y-3">
                    @forelse($stats['payment_method_analysis']['methods'] as $method)
                        <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-black dark:text-white">{{ $method['method'] }}</span>
                                <span class="text-sm text-gray-500">{{ $method['ratio'] }}%</span>
                            </div>
                            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ $method['count'] }} sipariş - TL{{ number_format($method['revenue'], 2) }}
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">Ödeme verisi bulunamadı</p>
                    @endforelse
                </div>
            </x-layout.grid>
        </x-ui.card>

        {{-- İşletme Bazlı Kazanç Analizi --}}
        <x-ui.card>
            <h2 class="text-xl font-bold text-black dark:text-white mb-4">İşletme Bazlı Kazanç Analizi</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ count($stats['branch_earnings']) }} İşletme</p>
            <x-table.table hoverable>
                <x-table.thead>
                    <x-table.tr :hoverable="false">
                        <x-table.th>İşletme Adı</x-table.th>
                        <x-table.th>Paket Adedi</x-table.th>
                        <x-table.th>Brut Gelir</x-table.th>
                        <x-table.th>Net Gelir</x-table.th>
                        <x-table.th>Fiyatlandırma Politikası</x-table.th>
                    </x-table.tr>
                </x-table.thead>
                <x-table.tbody>
                    @forelse($stats['branch_earnings'] as $branch)
                        <x-table.tr>
                            <x-table.td class="font-medium">{{ $branch['branch_name'] }}</x-table.td>
                            <x-table.td>{{ $branch['order_count'] }}</x-table.td>
                            <x-table.td class="text-green-600 dark:text-green-400">TL{{ number_format($branch['gross_revenue'], 2) }}</x-table.td>
                            <x-table.td class="text-blue-600 dark:text-blue-400">TL{{ number_format($branch['net_revenue'], 2) }}</x-table.td>
                            <x-table.td class="text-gray-600 dark:text-gray-400">{{ $branch['pricing_policy'] }}</x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.empty colspan="5" message="İşletme verisi bulunmuyor" />
                    @endforelse
                </x-table.tbody>
            </x-table.table>
        </x-ui.card>

        {{-- Kurye Bazlı Kazanç Analizi --}}
        <x-ui.card>
            <h2 class="text-xl font-bold text-black dark:text-white mb-4">Kurye Bazlı Kazanç Analizi</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ count($stats['courier_earnings']) }} Kurye</p>
            <x-table.table hoverable>
                <x-table.thead>
                    <x-table.tr :hoverable="false">
                        <x-table.th>Kurye Adı</x-table.th>
                        <x-table.th>Paket Adedi</x-table.th>
                        <x-table.th>Temel Kazanç</x-table.th>
                        <x-table.th>Toplam Kazanç</x-table.th>
                        <x-table.th>Fiyatlandırma Politikası</x-table.th>
                    </x-table.tr>
                </x-table.thead>
                <x-table.tbody>
                    @forelse($stats['courier_earnings'] as $courier)
                        <x-table.tr>
                            <x-table.td class="font-medium">{{ $courier['courier_name'] }}</x-table.td>
                            <x-table.td>{{ $courier['order_count'] }}</x-table.td>
                            <x-table.td class="text-green-600 dark:text-green-400">TL{{ number_format($courier['base_earning'], 2) }}</x-table.td>
                            <x-table.td class="text-blue-600 dark:text-blue-400">TL{{ number_format($courier['total_earning'], 2) }}</x-table.td>
                            <x-table.td class="text-gray-600 dark:text-gray-400">{{ $courier['pricing_policy'] }}</x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.empty colspan="5" message="Kurye verisi bulunmuyor" />
                    @endforelse
                </x-table.tbody>
            </x-table.table>
        </x-ui.card>

        {{-- İşletme Paket Özeti --}}
        <x-layout.section title="İşletme Paket Özeti">
            <x-layout.grid cols="1" mdCols="3" gap="4">
                <x-ui.stat-card title="İşletme Paket Sayısı" :value="$stats['business_orders']['total_orders']" subtitle="Kendi kuryesiyle teslim edilen sipariş" color="blue" icon="package" />
                <x-ui.stat-card title="İşletme Paket Cirosu" value="TL{{ number_format($stats['business_orders']['total_revenue'], 2) }}" subtitle="İşletmenin kendi teslim ettiği paketlerden gelir" color="green" icon="money" />
                <x-ui.card>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Ödeme Yöntemi Dağılımı</p>
                    @if(count($stats['business_orders']['payment_methods']) > 0)
                        <div class="space-y-2">
                            @foreach($stats['business_orders']['payment_methods'] as $method)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">{{ $method['method'] }}</span>
                                    <span class="text-black dark:text-white font-semibold">{{ $method['count'] }} (TL{{ number_format($method['revenue'], 2) }})</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">Ödeme verisi bulunamadı</p>
                    @endif
                </x-ui.card>
            </x-layout.grid>
        </x-layout.section>

        {{-- Coğrafi Analiz --}}
        <x-ui.card>
            <h2 class="text-xl font-bold text-black dark:text-white mb-4">Coğrafi Analiz</h2>
            <x-layout.grid cols="1" mdCols="4" gap="4" class="mb-6">
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['geographic_analysis']['total_points'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Teslimat Noktaları</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ count($stats['geographic_analysis']['clusters']) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Kümeler</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['geographic_analysis']['coverage_area'] }} km2</p>
                    <p class="text-xs text-gray-500 mt-1">Kapsama Alanı</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ count($stats['geographic_analysis']['delivery_points']) }} adet</p>
                    <p class="text-xs text-gray-500 mt-1">Teslimat Noktaları</p>
                </div>
            </x-layout.grid>
            <div class="relative h-96 rounded-lg overflow-hidden" id="deliveryMap">
                <div class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-900">
                    <p class="text-gray-500 dark:text-gray-400">Harita yükleniyor...</p>
                </div>
            </div>
        </x-ui.card>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#9CA3AF' : '#6B7280';
        Chart.defaults.borderColor = document.documentElement.classList.contains('dark') ? '#374151' : '#E5E7EB';

        // Cancellation Chart
        const cancellationCtx = document.getElementById('cancellationChart');
        if (cancellationCtx) {
            new Chart(cancellationCtx, { type: 'doughnut', data: { labels: ['Standart İptaller', 'Ücretli İptaller'], datasets: [{ data: [{{ $stats['cancellation_analysis']['standard_cancels'] }}, {{ $stats['cancellation_analysis']['paid_cancels'] }}], backgroundColor: ['#EF4444', '#F59E0B'], borderWidth: 0 }] }, options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } } });
        }

        // Hourly Chart
        const hourlyCtx = document.getElementById('hourlyChart');
        if (hourlyCtx) {
            new Chart(hourlyCtx, { type: 'line', data: { labels: Array.from({length: 24}, (_, i) => i + ':00'), datasets: [{ label: 'Sipariş Sayısı', data: @json(array_values($stats['time_performance']['hourly'])), borderColor: '#3B82F6', backgroundColor: 'rgba(59, 130, 246, 0.1)', tension: 0.4, fill: true }] }, options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } } });
        }

        // Daily Chart
        const dailyCtx = document.getElementById('dailyChart');
        if (dailyCtx) {
            new Chart(dailyCtx, { type: 'bar', data: { labels: @json($stats['time_performance']['daily']['dates']), datasets: [{ label: 'Sipariş Sayısı', data: @json($stats['time_performance']['daily']['counts']), backgroundColor: '#10B981', borderRadius: 6 }] }, options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } } });
        }

        // Platform Chart
        const platformCtx = document.getElementById('platformChart');
        if (platformCtx && {{ $stats['platform_analysis']['total_count'] }} > 0) {
            new Chart(platformCtx, { type: 'pie', data: { labels: @json(array_column($stats['platform_analysis']['platforms'], 'platform')), datasets: [{ data: @json(array_column($stats['platform_analysis']['platforms'], 'count')), backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'], borderWidth: 0 }] }, options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } } });
        }

        // Payment Method Chart
        const paymentMethodCtx = document.getElementById('paymentMethodChart');
        if (paymentMethodCtx && {{ count($stats['payment_method_analysis']['methods']) }} > 0) {
            new Chart(paymentMethodCtx, { type: 'doughnut', data: { labels: @json(array_column($stats['payment_method_analysis']['methods'], 'method')), datasets: [{ data: @json(array_column($stats['payment_method_analysis']['methods'], 'count')), backgroundColor: ['#10B981', '#3B82F6', '#F59E0B'], borderWidth: 0 }] }, options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } } });
        }

        // Delivery Map
        const deliveryPoints = @json($stats['geographic_analysis']['delivery_points']);
        const clusters = @json($stats['geographic_analysis']['clusters']);

        if (deliveryPoints.length > 0) {
            const centerLat = deliveryPoints.reduce((sum, p) => sum + p.lat, 0) / deliveryPoints.length;
            const centerLng = deliveryPoints.reduce((sum, p) => sum + p.lng, 0) / deliveryPoints.length;
            const map = L.map('deliveryMap').setView([centerLat, centerLng], 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: 'OpenStreetMap' }).addTo(map);
            clusters.forEach(cluster => {
                const color = cluster.count <= 5 ? '#3B82F6' : cluster.count <= 15 ? '#10B981' : cluster.count <= 50 ? '#F59E0B' : '#EF4444';
                L.circleMarker([cluster.lat, cluster.lng], { radius: Math.min(8 + cluster.count * 0.5, 30), fillColor: color, color: '#fff', weight: 2, opacity: 1, fillOpacity: 0.7 }).bindPopup(`${cluster.count} teslimat`).addTo(map);
            });
        }
    </script>
    @endpush
</x-bayi-layout>
