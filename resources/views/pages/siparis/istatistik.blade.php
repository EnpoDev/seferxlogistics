@extends('layouts.app')

@section('content')
<div class="p-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-black dark:text-white">Siparis Istatistikleri</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Siparis ve satis grafikleri</p>
        </div>
        <form method="GET" action="{{ route('siparis.istatistik') }}">
            <select name="period" onchange="this.form.submit()" class="px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800 focus:outline-none">
                <option value="7days" {{ request('period', '7days') == '7days' ? 'selected' : '' }}>Son 7 Gun</option>
                <option value="30days" {{ request('period') == '30days' ? 'selected' : '' }}>Son 30 Gun</option>
                <option value="this_month" {{ request('period') == 'this_month' ? 'selected' : '' }}>Bu Ay</option>
                <option value="last_month" {{ request('period') == 'last_month' ? 'selected' : '' }}>Gecen Ay</option>
            </select>
        </form>
    </div>

    <!-- Özet Kartları -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Siparis</p>
            <p class="text-2xl font-bold text-black dark:text-white mt-1">{{ $stats['total_orders'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Bugun: {{ $stats['today_orders'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Toplam Gelir</p>
            <p class="text-2xl font-bold text-black dark:text-white mt-1">{{ number_format($stats['total_revenue'] ?? 0, 2) }} TL</p>
            <p class="text-xs text-gray-500 mt-1">Bugun: {{ number_format($stats['today_revenue'] ?? 0, 2) }} TL</p>
        </div>
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Beklemede</p>
            <p class="text-2xl font-bold text-black dark:text-white mt-1">{{ $stats['pending_orders'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Hazirlaniyor: {{ $stats['preparing_orders'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400">Yolda</p>
            <p class="text-2xl font-bold text-black dark:text-white mt-1">{{ $stats['on_delivery_orders'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 mt-1">Teslim Edildi: {{ $stats['delivered_orders'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Grafikler -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Günlük Sipariş Grafiği -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Gunluk Siparis Sayisi</h3>
            <div class="h-64">
                <canvas id="dailyOrdersChart"></canvas>
            </div>
        </div>

        <!-- Sipariş Durumu Dağılımı -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Siparis Durumu Dagilimi</h3>
            <div class="space-y-4">
                @php
                    $totalOrders = ($stats['delivered_orders'] ?? 0) + ($stats['cancelled_orders'] ?? 0) + ($stats['returned_orders'] ?? 0);
                    $completedPercent = $totalOrders > 0 ? round(($stats['delivered_orders'] / $totalOrders) * 100, 1) : 0;
                    $cancelledPercent = $totalOrders > 0 ? round(($stats['cancelled_orders'] / $totalOrders) * 100, 1) : 0;
                    $returnedPercent = $totalOrders > 0 ? round(($stats['returned_orders'] / $totalOrders) * 100, 1) : 0;
                @endphp
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-black dark:text-white">Tamamlandi</span>
                        <span class="text-sm font-semibold text-black dark:text-white">{{ $stats['delivered_orders'] ?? 0 }} ({{ $completedPercent }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $completedPercent }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-black dark:text-white">Iptal Edildi</span>
                        <span class="text-sm font-semibold text-black dark:text-white">{{ $stats['cancelled_orders'] ?? 0 }} ({{ $cancelledPercent }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2">
                        <div class="bg-red-500 h-2 rounded-full" style="width: {{ $cancelledPercent }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-black dark:text-white">Iade Edildi</span>
                        <span class="text-sm font-semibold text-black dark:text-white">{{ $stats['returned_orders'] ?? 0 }} ({{ $returnedPercent }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full h-2">
                        <div class="bg-orange-500 h-2 rounded-full" style="width: {{ $returnedPercent }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- En Çok Satılan Ürünler ve Saatlik Dağılım -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- En Çok Satılan Ürünler -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">En Cok Satan Urunler</h3>
            <div class="space-y-3">
                @forelse($stats['top_products'] ?? [] as $product)
                <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-800 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-black dark:text-white">{{ $product['name'] }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $product['quantity'] }} adet</p>
                    </div>
                    <span class="font-semibold text-black dark:text-white">{{ number_format($product['revenue'], 2) }} TL</span>
                </div>
                @empty
                <p class="text-sm text-gray-500 text-center py-4">Urun verisi bulunamadi</p>
                @endforelse
            </div>
        </div>

        <!-- Saatlik Dağılım -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Saatlik Siparis Dagilimi</h3>
            <div class="h-48">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>
    </div>
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
                labels: {!! json_encode($stats['daily_labels'] ?? ['Pzt', 'Sal', 'Car', 'Per', 'Cum', 'Cmt', 'Paz']) !!},
                datasets: [{
                    label: 'Siparis Sayisi',
                    data: {!! json_encode($stats['daily_orders'] ?? [0, 0, 0, 0, 0, 0, 0]) !!},
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
                labels: {!! json_encode($stats['hourly_labels'] ?? ['12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23']) !!},
                datasets: [{
                    label: 'Siparis',
                    data: {!! json_encode($stats['hourly_orders'] ?? [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]) !!},
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
