@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn" x-data="courierDetailApp()">
    {{-- Header --}}
    <x-layout.page-header :title="$courier->name" :subtitle="'Kurye ID: #' . $courier->id" :backUrl="route('bayi.kuryelerim')">
        <x-slot name="icon">
            @if($courier->photo_path)
                <img src="{{ asset('storage/' . $courier->photo_path) }}" alt="{{ $courier->name }}" class="w-16 h-16 rounded-full object-cover border-2 border-gray-200 dark:border-gray-700">
            @else
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-2xl font-bold">
                    {{ substr($courier->name, 0, 1) }}
                </div>
            @endif
        </x-slot>
    </x-layout.page-header>

    {{-- Hızlı İstatistik Kartları --}}
    <x-layout.grid cols="1" mdCols="2" lgCols="4" gap="4" class="mb-6">
        <x-ui.stat-card title="Toplam Teslimat" :value="$totalOrders" color="blue" icon="package" />
        <x-ui.stat-card title="Başarı Oranı" :value="'%' . $successRate" color="green" icon="success" />
        <x-ui.stat-card title="Ort. Teslimat" :value="number_format($courier->average_delivery_time ?? 0, 0) . 'dk'" color="orange" icon="clock" />
        <x-ui.stat-card title="İptal Edilen" :value="$cancelledOrders" color="red" icon="x" />
    </x-layout.grid>

    {{-- Tabs Container --}}
    <x-ui.card :padding="false">
        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200 dark:border-gray-800">
            <nav class="flex overflow-x-auto gap-2 p-4">
                <button @click="activeTab = 'past_orders'" :class="{'bg-black dark:bg-white text-white dark:text-black': activeTab === 'past_orders', 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800': activeTab !== 'past_orders'}"
                    class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-all">
                    Geçmiş Siparişler
                </button>
                <button @click="activeTab = 'statistics'" :class="{'bg-black dark:bg-white text-white dark:text-black': activeTab === 'statistics', 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800': activeTab !== 'statistics'}"
                    class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-all">
                    İstatistikler
                </button>
                <button @click="activeTab = 'work_logs'" :class="{'bg-black dark:bg-white text-white dark:text-black': activeTab === 'work_logs', 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800': activeTab !== 'work_logs'}"
                    class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-all">
                    Mesai-Mola
                </button>
                <button @click="activeTab = 'pricing'" :class="{'bg-black dark:bg-white text-white dark:text-black': activeTab === 'pricing', 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800': activeTab !== 'pricing'}"
                    class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-all">
                    Fiyatlandırma
                </button>
                <button @click="activeTab = 'settings'" :class="{'bg-black dark:bg-white text-white dark:text-black': activeTab === 'settings', 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800': activeTab !== 'settings'}"
                    class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-all">
                    Ayarlar
                </button>
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="p-6 sm:p-8">
            {{-- Tab 1: Geçmiş Siparişler --}}
            <div x-show="activeTab === 'past_orders'" x-cloak>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Geçmiş Siparişler</h3>

                {{-- Filtre --}}
                <div class="flex flex-wrap gap-4 mb-6">
                    <x-form.input type="date" name="start_date" label="Başlangıç Tarihi" x-model="pastOrdersStartDate" size="sm" />
                    <x-form.input type="date" name="end_date" label="Bitiş Tarihi" x-model="pastOrdersEndDate" size="sm" />
                    <div class="flex items-end">
                        <x-ui.button @click="loadPastOrders()">Filtrele</x-ui.button>
                    </div>
                </div>

                {{-- Tablo --}}
                <x-table.table hoverable>
                    <x-table.thead>
                        <x-table.tr :hoverable="false">
                            <x-table.th>Sipariş No</x-table.th>
                            <x-table.th>Müşteri</x-table.th>
                            <x-table.th>Telefon</x-table.th>
                            <x-table.th>Tutar</x-table.th>
                            <x-table.th>Ödeme</x-table.th>
                            <x-table.th>Durum</x-table.th>
                            <x-table.th>Tarih</x-table.th>
                        </x-table.tr>
                    </x-table.thead>
                    <x-table.tbody>
                        <template x-for="order in pastOrders" :key="order.id">
                            <x-table.tr>
                                <x-table.td x-text="order.order_number"></x-table.td>
                                <x-table.td x-text="order.customer_name"></x-table.td>
                                <x-table.td class="text-gray-600 dark:text-gray-400" x-text="order.customer_phone"></x-table.td>
                                <x-table.td class="font-medium" x-text="'₺' + order.total"></x-table.td>
                                <x-table.td class="text-gray-600 dark:text-gray-400" x-text="order.payment_method"></x-table.td>
                                <x-table.td>
                                    <span class="px-2 py-1 text-xs rounded-full" :class="order.status === 'delivered' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300'" x-text="order.status_label"></span>
                                </x-table.td>
                                <x-table.td class="text-gray-600 dark:text-gray-400" x-text="order.created_at"></x-table.td>
                            </x-table.tr>
                        </template>
                        <template x-if="pastOrders.length === 0">
                            <x-table.empty colspan="7" message="Sipariş bulunamadı" icon="package" />
                        </template>
                    </x-table.tbody>
                </x-table.table>
            </div>

            {{-- Tab 2: İstatistikler --}}
            <div x-show="activeTab === 'statistics'" x-cloak>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">İstatistikler</h3>

                {{-- Periyot Seçimi --}}
                <div class="mb-6">
                    <x-form.select name="period" x-model="statisticsPeriod" @change="loadStatistics()" :options="['day' => 'Bugün', 'week' => 'Bu Hafta', 'month' => 'Bu Ay']" selected="week" />
                </div>

                {{-- Özet Kartlar --}}
                <x-layout.grid cols="1" mdCols="3" gap="4" class="mb-6">
                    <div class="bg-gray-50 dark:bg-black/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Toplam Sipariş</p>
                        <p class="text-2xl font-bold text-black dark:text-white" x-text="statistics.summary?.total_orders ?? 0"></p>
                    </div>
                    <div class="bg-gray-50 dark:bg-black/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Başarı Oranı</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="'%' + (statistics.summary?.success_rate ?? 0)"></p>
                    </div>
                    <div class="bg-gray-50 dark:bg-black/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Toplam Kazanç</p>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="'₺' + (statistics.summary?.total_earnings ?? '0.00')"></p>
                    </div>
                </x-layout.grid>

                {{-- Saatlik Dağılım --}}
                <div class="mb-6">
                    <h4 class="text-md font-semibold text-black dark:text-white mb-3">Saatlik Teslimat Dağılımı</h4>
                    <div class="bg-gray-50 dark:bg-black/50 p-6 rounded-lg border border-gray-200 dark:border-gray-700 text-center text-gray-500 dark:text-gray-400">
                        Grafik buraya gelecek
                    </div>
                </div>

                {{-- Günlük İstatistikler --}}
                <div class="mb-6">
                    <h4 class="text-md font-semibold text-black dark:text-white mb-3">Günlük İstatistikler</h4>
                    <x-table.table hoverable>
                        <x-table.thead>
                            <x-table.tr :hoverable="false">
                                <x-table.th>Tarih</x-table.th>
                                <x-table.th>Gün</x-table.th>
                                <x-table.th>Sipariş</x-table.th>
                                <x-table.th>Teslim</x-table.th>
                                <x-table.th>Gelir</x-table.th>
                            </x-table.tr>
                        </x-table.thead>
                        <x-table.tbody>
                            <template x-for="stat in statistics.daily_stats" :key="stat.date">
                                <x-table.tr>
                                    <x-table.td x-text="stat.date"></x-table.td>
                                    <x-table.td class="text-gray-600 dark:text-gray-400" x-text="stat.day"></x-table.td>
                                    <x-table.td x-text="stat.orders"></x-table.td>
                                    <x-table.td class="text-green-600 dark:text-green-400" x-text="stat.delivered"></x-table.td>
                                    <x-table.td class="font-medium" x-text="'₺' + stat.revenue"></x-table.td>
                                </x-table.tr>
                            </template>
                        </x-table.tbody>
                    </x-table.table>
                </div>

                {{-- Bölge Performansı --}}
                <div>
                    <h4 class="text-md font-semibold text-black dark:text-white mb-3">Bölge Performansı</h4>
                    <x-layout.grid cols="1" mdCols="2" lgCols="3" gap="4">
                        <template x-for="zone in statistics.zone_stats" :key="zone.zone_name">
                            <div class="bg-gray-50 dark:bg-black/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="zone.zone_name"></p>
                                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-2" x-text="zone.orders + ' sipariş'"></p>
                            </div>
                        </template>
                    </x-layout.grid>
                </div>
            </div>

            {{-- Tab 3: Mesai-Mola --}}
            <div x-show="activeTab === 'work_logs'" x-cloak>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Mesai ve Mola İstatistikleri</h3>

                {{-- Filtre --}}
                <div class="flex flex-wrap gap-4 mb-6">
                    <x-form.input type="date" name="work_start_date" label="Başlangıç Tarihi" x-model="workLogsStartDate" size="sm" />
                    <x-form.input type="date" name="work_end_date" label="Bitiş Tarihi" x-model="workLogsEndDate" size="sm" />
                    <div class="flex items-end">
                        <x-ui.button @click="loadWorkLogs()">Filtrele</x-ui.button>
                    </div>
                </div>

                {{-- Özet Kartlar --}}
                <x-layout.grid cols="1" mdCols="2" lgCols="4" gap="4" class="mb-6">
                    <div class="bg-gray-50 dark:bg-black/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Toplam Çalışma</p>
                        <p class="text-2xl font-bold text-black dark:text-white" x-text="(workLogs.summary?.total_work_hours ?? 0) + ' saat'"></p>
                    </div>
                    <div class="bg-gray-50 dark:bg-black/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Toplam Mola</p>
                        <p class="text-2xl font-bold text-orange-600 dark:text-orange-400" x-text="(workLogs.summary?.total_break_hours ?? 0) + ' saat'"></p>
                    </div>
                    <div class="bg-gray-50 dark:bg-black/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Ortalama/Gün</p>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="(workLogs.summary?.avg_daily_hours ?? 0) + ' saat'"></p>
                    </div>
                    <div class="bg-gray-50 dark:bg-black/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Çalışılan Gün</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="(workLogs.summary?.days_worked ?? 0) + ' gün'"></p>
                    </div>
                </x-layout.grid>

                {{-- Günlük Tablo --}}
                <x-table.table hoverable>
                    <x-table.thead>
                        <x-table.tr :hoverable="false">
                            <x-table.th>Tarih</x-table.th>
                            <x-table.th>Gun</x-table.th>
                            <x-table.th>Giriş</x-table.th>
                            <x-table.th>Çıkış</x-table.th>
                            <x-table.th>Toplam</x-table.th>
                            <x-table.th>Mola</x-table.th>
                            <x-table.th>Net</x-table.th>
                        </x-table.tr>
                    </x-table.thead>
                    <x-table.tbody>
                        <template x-for="log in workLogs.daily_stats" :key="log.date">
                            <x-table.tr>
                                <x-table.td x-text="log.date"></x-table.td>
                                <x-table.td class="text-gray-600 dark:text-gray-400" x-text="log.day"></x-table.td>
                                <x-table.td class="text-green-600 dark:text-green-400" x-text="log.clock_in"></x-table.td>
                                <x-table.td class="text-red-600 dark:text-red-400" x-text="log.clock_out"></x-table.td>
                                <x-table.td x-text="log.total_work_hours + ' saat'"></x-table.td>
                                <x-table.td class="text-orange-600 dark:text-orange-400" x-text="log.break_hours + ' saat'"></x-table.td>
                                <x-table.td class="font-medium text-blue-600 dark:text-blue-400" x-text="log.net_work_hours + ' saat'"></x-table.td>
                            </x-table.tr>
                        </template>
                        <template x-if="!workLogs.daily_stats || workLogs.daily_stats.length === 0">
                            <x-table.empty colspan="7" message="Mesai kaydı bulunamadı" icon="clock" />
                        </template>
                    </x-table.tbody>
                </x-table.table>
            </div>

            {{-- Tab 4: Fiyatlandırma --}}
            <div x-show="activeTab === 'pricing'" x-cloak>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Fiyatlandırma Politikası</h3>

                {{-- Mevcut Politika --}}
                <div x-show="currentPolicy" class="mb-8">
                    <h4 class="text-md font-semibold text-black dark:text-white mb-3">Atanmış Politika</h4>
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-xl p-6 text-white shadow-lg">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h5 class="text-xl font-bold mb-2" x-text="currentPolicy?.name"></h5>
                                <p class="text-sm opacity-90 mb-4" x-text="currentPolicy?.description"></p>
                                <div class="space-y-2">
                                    <template x-for="rule in currentPolicy?.rules" :key="rule.id">
                                        <div class="bg-white/20 px-3 py-2 rounded-lg text-sm">
                                            <span x-text="`Min: ${rule.min_value ?? '-'} | Max: ${rule.max_value ?? '-'} | Fiyat: ₺${rule.price ?? '-'} | Yüzde: %${rule.percentage ?? '-'}`"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <x-ui.button variant="danger" @click="removePricingPolicy()" class="ml-4">Kaldır</x-ui.button>
                        </div>
                    </div>
                </div>

                {{-- Mevcut Politikalar --}}
                <div class="mb-8">
                    <h4 class="text-md font-semibold text-black dark:text-white mb-3">Mevcut Politikalar</h4>
                    <x-layout.grid cols="1" mdCols="2" lgCols="3" gap="4">
                        <template x-for="policy in availablePolicies" :key="policy.id">
                            <div class="relative bg-gray-50 dark:bg-black/50 p-6 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-lg transition-all">
                                {{-- Silme butonu --}}
                                <button @click.stop="deletePolicy(policy.id)" class="absolute top-3 right-3 p-1.5 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors" title="Politikayı Sil">
                                    <x-ui.icon name="trash" class="w-4 h-4" />
                                </button>

                                <div @click="assignPricingPolicy(policy.id)" class="cursor-pointer">
                                    <h5 class="text-lg font-bold text-black dark:text-white mb-2 pr-6" x-text="policy.name"></h5>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4" x-text="policy.description"></p>
                                    <div class="flex items-center justify-between">
                                        <x-ui.badge type="info" x-text="(policy.rules?.length ?? 0) + ' kural'" />
                                        <span class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Seç</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="availablePolicies.length === 0">
                            <div class="col-span-3 text-center text-gray-500 dark:text-gray-400 py-8">
                                Henüz politika oluşturulmamış
                            </div>
                        </template>
                    </x-layout.grid>
                </div>

                {{-- Yeni Politika Oluştur --}}
                <div>
                    <h4 class="text-md font-semibold text-black dark:text-white mb-3">Yeni Politika Oluştur</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Kuryeniz için özel fiyatlandırma politikası oluşturun.</p>

                    <x-layout.grid cols="1" mdCols="2" gap="6">
                        @php
                            $policyTypes = [
                                'fixed' => ['title' => 'Sabit Fiyat & Sabit Yüzdelik Politikası', 'desc' => 'Sabit fiyat ve sabit yüzdelik politikası ile alınan komisyonlar sabitlenir.'],
                                'package_based' => ['title' => 'Paket Tutarına Göre Değişen Politika', 'desc' => 'Paket tutarına göre değişen politika ile alınan komisyonlar paket tutarına göre değişir.'],
                                'distance_based' => ['title' => 'Teslimat Mesafesine Göre Değişen Politika', 'desc' => 'Teslimat mesafesine göre değişen politika ile işletme ile teslimat noktası arasındaki mesafeye göre komisyonlar değişir.'],
                                'periodic' => ['title' => 'Periyodik Politika', 'desc' => 'Periyodik politika ile belirlediğiniz periyotlardaki toplam paket sayısına göre alınan komisyonlar değişir.'],
                                'unit_price' => ['title' => 'Teslimat Mesafesi Birim Fiyat Politikası', 'desc' => 'Teslimat mesafesine göre belirlenen birim fiyat politikası ile işletme ile teslimat noktası arasındaki mesafeye göre komisyonlar değişir.'],
                                'consecutive_discount' => ['title' => 'Ardışık Paket İndirimi', 'desc' => 'Kurye aynı işletmeden belirli süre içinde aldığı ardışık paketler için kademeli bonus/kesinti uygulanır.'],
                            ];
                        @endphp
                        @foreach($policyTypes as $type => $info)
                        <div @click="openPolicyModal('{{ $type }}')" class="p-6 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl hover:border-blue-500 dark:hover:border-blue-500 transition-colors cursor-pointer">
                            <h4 class="text-md font-semibold text-black dark:text-white mb-2">{{ $info['title'] }}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ $info['desc'] }}</p>
                            <x-ui.button size="sm">Seç</x-ui.button>
                        </div>
                        @endforeach
                    </x-layout.grid>
                </div>
            </div>

            {{-- Tab 5: Ayarlar --}}
            <div x-show="activeTab === 'settings'" x-cloak>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Kurye Ayarları</h3>

                <form action="{{ route('bayi.kurye-ayarlar-guncelle', $courier->id) }}" method="POST" class="space-y-6 max-w-xl">
                    @csrf
                    @method('PUT')

                    <x-form.input name="name" label="Kurye Ad Soyad" :value="$courier->name" required />
                    <x-form.input type="email" name="email" label="E-posta Adresi" :value="$courier->email" />
                    <x-form.input type="tel" name="phone" label="Telefon Numarası" :value="$courier->phone" required />

                    <div class="flex gap-4">
                        <x-ui.button type="submit">Kaydet</x-ui.button>
                        <x-ui.button type="button" variant="danger" @click="showDeleteConfirm = true">Kuryeyi Sil</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    </x-ui.card>

    {{-- Pricing Policy Modal --}}
    <x-ui.modal name="policyModal" title="Yeni Politika Oluştur" size="lg">
        <div x-show="showPolicyModal">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4" x-text="'Politika Tipi: ' + getPolicyTypeLabel(selectedPolicyType)"></p>

            <form @submit.prevent="createPolicy" class="space-y-4">
                <x-form.input name="policy_name" label="Politika Adı" x-model="policyForm.name" required />
                <x-form.textarea name="policy_description" label="Açıklama" x-model="policyForm.description" :rows="2" />

                {{-- Sabit Fiyat Politikası --}}
                <div x-show="selectedPolicyType === 'fixed'" class="space-y-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <h4 class="text-sm font-semibold text-black dark:text-white">Sabit Değerler</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sabit Fiyat (TL)</label>
                            <input type="number" step="0.01" x-model="policyForm.fixedPrice" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-950 text-black dark:text-white" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sabit Yüzde (%)</label>
                            <input type="number" step="0.1" x-model="policyForm.fixedPercentage" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-950 text-black dark:text-white" placeholder="0">
                        </div>
                    </div>
                </div>

                {{-- Birim Fiyat Politikası --}}
                <div x-show="selectedPolicyType === 'unit_price'" class="space-y-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <h4 class="text-sm font-semibold text-black dark:text-white">KM Birim Fiyat</h4>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">KM Başına Fiyat (TL)</label>
                        <input type="number" step="0.01" x-model="policyForm.pricePerKm" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-950 text-black dark:text-white" placeholder="2.50">
                    </div>
                </div>

                {{-- Mesafe/Paket/Periyodik Bazlı Kurallar --}}
                <div x-show="['distance_based', 'package_based', 'periodic', 'consecutive_discount'].includes(selectedPolicyType)" class="space-y-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-black dark:text-white">
                            <span x-show="selectedPolicyType === 'distance_based'">Mesafe Kuralları (KM)</span>
                            <span x-show="selectedPolicyType === 'package_based'">Paket Tutarı Kuralları (TL)</span>
                            <span x-show="selectedPolicyType === 'periodic'">Periyodik Kurallar (Paket Sayısı)</span>
                            <span x-show="selectedPolicyType === 'consecutive_discount'">Ardışık Paket Kuralları</span>
                        </h4>
                        <button type="button" @click="addRule()" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">+ Kural Ekle</button>
                    </div>

                    <template x-for="(rule, index) in policyForm.rules" :key="index">
                        <div class="flex gap-2 items-end">
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">Min</label>
                                <input type="number" step="0.01" x-model="rule.min_value" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-950 text-black dark:text-white" placeholder="0">
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">Max</label>
                                <input type="number" step="0.01" x-model="rule.max_value" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-950 text-black dark:text-white" placeholder="5">
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">Fiyat (TL)</label>
                                <input type="number" step="0.01" x-model="rule.price" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-950 text-black dark:text-white" placeholder="10">
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">Yüzde (%)</label>
                                <input type="number" step="0.1" x-model="rule.percentage" class="w-full px-2 py-1.5 text-sm border border-gray-300 dark:border-gray-700 rounded bg-white dark:bg-gray-950 text-black dark:text-white" placeholder="0">
                            </div>
                            <button type="button" @click="removeRule(index)" class="p-1.5 text-red-500 hover:text-red-700">
                                <x-ui.icon name="x" class="w-4 h-4" />
                            </button>
                        </div>
                    </template>

                    <p x-show="policyForm.rules.length === 0" class="text-sm text-gray-500 text-center py-2">Henüz kural eklenmedi</p>
                </div>

                <x-form.checkbox name="policy_is_active" label="Aktif" x-model="policyForm.is_active" />

                <div class="flex gap-3 pt-4">
                    <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', 'policyModal')" class="flex-1">İptal</x-ui.button>
                    <x-ui.button type="submit" class="flex-1">Oluştur</x-ui.button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    {{-- Delete Confirmation Modal --}}
    <div x-show="showDeleteConfirm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click="showDeleteConfirm = false">
        <div @click.stop class="bg-white dark:bg-[#181818] rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
            <h3 class="text-xl font-bold text-black dark:text-white mb-4">Kuryeyi Sil</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Bu kuryeyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.</p>
            <div class="flex gap-3">
                <form action="{{ route('bayi.kurye-sil', $courier->id) }}" method="POST" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" fullWidth>Evet, Sil</x-ui.button>
                </form>
                <x-ui.button variant="secondary" @click="showDeleteConfirm = false" class="flex-1">İptal</x-ui.button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function courierDetailApp() {
    return {
        activeTab: 'past_orders',
        pastOrders: [],
        pastOrdersStartDate: '{{ now()->subDays(30)->format("Y-m-d") }}',
        pastOrdersEndDate: '{{ now()->format("Y-m-d") }}',
        statistics: {},
        statisticsPeriod: 'week',
        workLogs: {},
        workLogsStartDate: '{{ now()->subDays(30)->format("Y-m-d") }}',
        workLogsEndDate: '{{ now()->format("Y-m-d") }}',
        currentPolicy: @json($courier->pricingPolicy),
        availablePolicies: @json($availablePolicies ?? []),
        showDeleteConfirm: false,
        showPolicyModal: false,
        selectedPolicyType: '',
        policyForm: {
            name: '',
            description: '',
            is_active: true,
            fixedPrice: null,
            fixedPercentage: null,
            pricePerKm: null,
            rules: []
        },

        init() {
            this.loadPastOrders();
        },

        addRule() {
            this.policyForm.rules.push({
                min_value: null,
                max_value: null,
                price: null,
                percentage: null
            });
        },

        removeRule(index) {
            this.policyForm.rules.splice(index, 1);
        },

        async loadPastOrders() {
            try {
                const response = await fetch(`{{ route('bayi.kurye-past-orders', $courier->id) }}?start_date=${this.pastOrdersStartDate}&end_date=${this.pastOrdersEndDate}`);
                this.pastOrders = await response.json();
            } catch (error) {
                console.error('Sipariş yükleme hatası:', error);
            }
        },

        async loadStatistics() {
            try {
                const response = await fetch(`{{ route('bayi.kurye-statistics', $courier->id) }}?period=${this.statisticsPeriod}`);
                this.statistics = await response.json();
            } catch (error) {
                console.error('İstatistik yükleme hatası:', error);
            }
        },

        async loadWorkLogs() {
            try {
                const response = await fetch(`{{ route('bayi.kurye-mesai-logs', $courier->id) }}?start_date=${this.workLogsStartDate}&end_date=${this.workLogsEndDate}`);
                this.workLogs = await response.json();
            } catch (error) {
                console.error('Mesai kayıtları yükleme hatası:', error);
            }
        },

        async assignPricingPolicy(policyId) {
            if (!confirm('Bu politikayı kuryeye atamak istediğinizden emin misiniz?')) return;

            try {
                const response = await fetch(`{{ route('bayi.kurye-pricing-policy-ata', $courier->id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ pricing_policy_id: policyId })
                });

                const result = await response.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    location.reload();
                }
            } catch (error) {
                console.error('Politika atama hatasi:', error);
                showToast('Bir hata olustu', 'error');
            }
        },

        async removePricingPolicy() {
            if (!confirm('Politikayi kaldirmak istediginizden emin misiniz?')) return;

            try {
                const response = await fetch(`{{ route('bayi.kurye-pricing-policy-ata', $courier->id) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ pricing_policy_id: null })
                });

                const result = await response.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    location.reload();
                }
            } catch (error) {
                console.error('Politika kaldirma hatasi:', error);
                showToast('Bir hata olustu', 'error');
            }
        },

        async deletePolicy(policyId) {
            if (!confirm('Bu politikayı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) return;

            try {
                const response = await fetch(`/bayi/pricing-policy/${policyId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const result = await response.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    this.availablePolicies = this.availablePolicies.filter(p => p.id !== policyId);
                } else {
                    showToast(result.message || 'Bir hata olustu', 'error');
                }
            } catch (error) {
                console.error('Politika silme hatasi:', error);
                showToast('Bir hata olustu', 'error');
            }
        },

        openPolicyModal(policyType) {
            this.selectedPolicyType = policyType;
            this.policyForm = {
                name: '',
                description: '',
                is_active: true,
                fixedPrice: null,
                fixedPercentage: null,
                pricePerKm: null,
                rules: []
            };
            this.showPolicyModal = true;
            this.$dispatch('open-modal', 'policyModal');
        },

        async createPolicy() {
            // Validasyon
            if (!this.policyForm.name) {
                showToast('Politika adı zorunludur', 'error');
                return;
            }

            // Kural bazlı politikalar için en az 1 kural gerekli
            const ruleBasedTypes = ['distance_based', 'package_based', 'periodic', 'consecutive_discount'];
            if (ruleBasedTypes.includes(this.selectedPolicyType) && this.policyForm.rules.length === 0) {
                showToast('En az bir kural eklemeniz gerekiyor', 'error');
                return;
            }

            try {
                const response = await fetch(`{{ route('bayi.kurye-pricing-policy-olustur') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        policy_type: this.selectedPolicyType,
                        name: this.policyForm.name,
                        description: this.policyForm.description,
                        is_active: this.policyForm.is_active,
                        fixed_price: this.policyForm.fixedPrice,
                        fixed_percentage: this.policyForm.fixedPercentage,
                        price_per_km: this.policyForm.pricePerKm,
                        rules: this.policyForm.rules
                    })
                });

                const result = await response.json();
                if (result.success) {
                    showToast(result.message, 'success');
                    this.availablePolicies.push(result.policy);
                    this.showPolicyModal = false;
                    this.$dispatch('close-modal', 'policyModal');
                } else {
                    showToast(result.message || 'Bir hata olustu', 'error');
                }
            } catch (error) {
                console.error('Politika olusturma hatasi:', error);
                showToast('Bir hata olustu', 'error');
            }
        },

        getPolicyTypeLabel(type) {
            const labels = {
                'fixed': 'Sabit Fiyat & Sabit Yuzdelik',
                'package_based': 'Paket Tutarina Gore',
                'distance_based': 'Teslimat Mesafesine Gore',
                'periodic': 'Periyodik',
                'unit_price': 'Mesafe Birim Fiyat',
                'consecutive_discount': 'Ardisik Paket Indirimi'
            };
            return labels[type] || type;
        }
    }
}
</script>
@endpush
@endsection
