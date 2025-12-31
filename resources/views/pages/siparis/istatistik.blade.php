@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Sipariş İstatistikleri"
        subtitle="Sipariş ve satış grafikleri"
    >
        <x-slot name="icon">
            <x-ui.icon name="chart" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <form method="GET" action="{{ route('siparis.istatistik') }}">
                <x-form.select name="period" onchange="this.form.submit()" :options="[
                    '7days' => 'Son 7 Gün',
                    '30days' => 'Son 30 Gün',
                    'this_month' => 'Bu Ay',
                    'last_month' => 'Geçen Ay',
                ]" :selected="request('period', '7days')" />
            </form>
        </x-slot>
    </x-layout.page-header>

    {{-- Özet Kartları --}}
    <x-layout.grid cols="1" mdCols="4" gap="6" class="mb-6">
        <x-ui.stat-card
            title="Toplam Sipariş"
            :value="$stats['total_orders']"
            :subtitle="'Bugün: ' . $stats['today_orders']"
            icon="order"
            color="blue"
        />
        <x-ui.stat-card
            title="Toplam Gelir"
            :value="'₺' . number_format($stats['total_revenue'], 2)"
            :subtitle="'Bugün: ₺' . number_format($stats['today_revenue'], 2)"
            icon="money"
            color="green"
        />
        <x-ui.stat-card
            title="Beklemede"
            :value="$stats['pending_orders']"
            :subtitle="'Hazırlanıyor: ' . $stats['preparing_orders']"
            icon="clock"
            color="yellow"
        />
        <x-ui.stat-card
            title="Yolda"
            :value="$stats['on_delivery_orders']"
            :subtitle="'Teslim Edildi: ' . $stats['delivered_orders']"
            icon="truck"
            color="purple"
        />
    </x-layout.grid>

    {{-- Grafikler --}}
    <x-layout.grid cols="1" lgCols="2" gap="6" class="mb-6">
        {{-- Günlük Sipariş Grafiği --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Günlük Sipariş Sayısı</h3>
            <div class="h-64">
                <canvas id="dailyOrdersChart"></canvas>
            </div>
        </x-ui.card>

        {{-- Sipariş Durumu Dağılımı --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Sipariş Durumu Dağılımı</h3>
            <div class="space-y-4">
                @php
                    $totalOrders = $stats['delivered_orders'] + $stats['cancelled_orders'] + $stats['returned_orders'];
                    $completedPercent = $totalOrders > 0 ? round(($stats['delivered_orders'] / $totalOrders) * 100, 1) : 0;
                    $cancelledPercent = $totalOrders > 0 ? round(($stats['cancelled_orders'] / $totalOrders) * 100, 1) : 0;
                    $returnedPercent = $totalOrders > 0 ? round(($stats['returned_orders'] / $totalOrders) * 100, 1) : 0;
                @endphp
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-black dark:text-white">Tamamlandı</span>
                        <span class="text-sm font-semibold text-black dark:text-white">{{ $stats['delivered_orders'] }} ({{ $completedPercent }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $completedPercent }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-black dark:text-white">İptal Edildi</span>
                        <span class="text-sm font-semibold text-black dark:text-white">{{ $stats['cancelled_orders'] }} ({{ $cancelledPercent }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2">
                        <div class="bg-red-500 h-2 rounded-full" style="width: {{ $cancelledPercent }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-black dark:text-white">İade Edildi</span>
                        <span class="text-sm font-semibold text-black dark:text-white">{{ $stats['returned_orders'] }} ({{ $returnedPercent }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2">
                        <div class="bg-orange-500 h-2 rounded-full" style="width: {{ $returnedPercent }}%"></div>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </x-layout.grid>

    {{-- En Çok Satılan Ürünler ve Saatlik Dağılım --}}
    <x-layout.grid cols="1" lgCols="2" gap="6">
        {{-- En Çok Satılan Ürünler --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">En Çok Satılan Ürünler</h3>
            <div class="space-y-3">
                @forelse($stats['top_products'] ?? [] as $product)
                <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-800 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-black dark:text-white">{{ $product['name'] }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $product['quantity'] }} adet</p>
                    </div>
                    <x-data.money :amount="$product['revenue']" class="font-semibold" />
                </div>
                @empty
                <p class="text-sm text-gray-500 text-center py-4">Ürün verisi bulunamadı</p>
                @endforelse
            </div>
        </x-ui.card>

        {{-- Saatlik Dağılım --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Saatlik Sipariş Dağılımı</h3>
            <div class="h-48">
                <canvas id="hourlyChart"></canvas>
            </div>
        </x-ui.card>
    </x-layout.grid>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#fff' : '#000';
    const gridColor = isDark ? '#333' : '#e5e7eb';

    // Günlük Sipariş Grafiği
    const dailyCtx = document.getElementById('dailyOrdersChart');
    if (dailyCtx) {
        new Chart(dailyCtx, {
            type: 'bar',
            data: {
                labels: @json($stats['daily_labels'] ?? ['Pzt', 'Sal', 'Car', 'Per', 'Cum', 'Cmt', 'Paz']),
                datasets: [{
                    label: 'Sipariş Sayısı',
                    data: @json($stats['daily_orders'] ?? [0, 0, 0, 0, 0, 0, 0]),
                    backgroundColor: isDark ? '#fff' : '#000',
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { color: textColor }, grid: { color: gridColor } },
                    x: { ticks: { color: textColor }, grid: { display: false } }
                }
            }
        });
    }

    // Saatlik Dağılım Grafiği
    const hourlyCtx = document.getElementById('hourlyChart');
    if (hourlyCtx) {
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: @json($stats['hourly_labels'] ?? ['12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23']),
                datasets: [{
                    label: 'Sipariş',
                    data: @json($stats['hourly_orders'] ?? [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]),
                    backgroundColor: isDark ? '#fff' : '#000',
                    borderRadius: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { color: textColor }, grid: { color: gridColor } },
                    x: { ticks: { color: textColor }, grid: { display: false } }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
