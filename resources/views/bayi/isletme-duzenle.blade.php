@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn" x-data="branchEditApp()">
    {{-- Header --}}
    <x-layout.page-header
        title="İşletme Düzenle"
        :subtitle="$branch->name . ' işletme bilgilerini yönetin'"
        :backUrl="$branch->parent_id ? route('bayi.isletme-detay', $branch->parent_id) : route('bayi.isletmelerim')"
    />

    {{-- Tabs Container --}}
    <x-ui.card :padding="false" class="max-w-7xl mx-auto">
        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200 dark:border-gray-800">
            <nav class="flex overflow-x-auto gap-2 p-4">
                @foreach(['settings' => 'Ayarlar', 'past_orders' => 'Geçmiş Siparişler', 'cancelled_orders' => 'İptal Siparişler', 'statistics' => 'İstatistikler', 'detailed_statistics' => 'Detaylı İstatistikler', 'pricing' => 'Fiyatlandırma'] as $key => $label)
                <button @click="activeTab = '{{ $key }}'" :class="{'bg-black dark:bg-white text-white dark:text-black': activeTab === '{{ $key }}', 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800': activeTab !== '{{ $key }}'}"
                    class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-all">
                    {{ $label }}
                </button>
                @endforeach
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="p-6 sm:p-8">
            {{-- Settings Tab --}}
            <div x-show="activeTab === 'settings'" x-cloak>
                <form action="{{ route('bayi.isletme.ayarlar', $branch->id) }}" method="POST" class="space-y-8">
                    @csrf

                    {{-- Kurye Entegrasyonu Ayarları --}}
                    <x-layout.section title="Kurye Entegrasyonu Ayarları" description="İşletmenin kurye çağırma durumu ve bakiye ayarlarını yönetin." border>
                        <div class="space-y-4">
                            <x-form.toggle name="courier_enabled" label="Kurye Çağırma Durumu" description="İşletmenin kurye çağırma özelliğinin aktif olup olmadığını belirler." :checked="$branch->settings?->courier_enabled ?? false" />
                            <x-form.toggle name="balance_tracking" label="Bakiye Takibi" description="İşletmenin bakiye takibine tabi olup olmadığını belirler." :checked="$branch->settings?->balance_tracking ?? false" />
                            <x-form.toggle name="cash_balance_tracking" label="Nakit Bakiye Takibi" description="İşletmenin nakit bakiye takibine tabi olup olmadığını belirler." :checked="$branch->settings?->cash_balance_tracking ?? false" />

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-4 bg-gray-50 dark:bg-black/50 rounded-lg">
                                    <label class="text-sm font-medium text-black dark:text-white block mb-2">Mevcut Bakiye</label>
                                    <div class="text-2xl font-bold text-black dark:text-white">
                                        <x-data.money :amount="$branch->settings?->current_balance ?? 0" />
                                    </div>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-black/50 rounded-lg">
                                    <label class="text-sm font-medium text-black dark:text-white block mb-2">Mevcut Nakit Bakiye</label>
                                    <div class="text-2xl font-bold text-black dark:text-white">
                                        <x-data.money :amount="$branch->settings?->current_cash_balance ?? 0" />
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Salt okunur</p>
                                </div>
                            </div>
                        </div>
                    </x-layout.section>

                    {{-- Harita Ayarları --}}
                    <x-layout.section title="Harita Ayarları" border>
                        <x-form.toggle name="map_display" label="Harita Gösterimi" description="İşletmenin harita görüntüleme özelliğinin aktif olup olmadığını belirler." :checked="$branch->settings?->map_display ?? true" />
                    </x-layout.section>

                    {{-- İşletme Ayarları --}}
                    <x-layout.section title="İşletme Ayarları" description="İşletme ayarlarınızı buradan yönetebilirsiniz." border>
                        <x-layout.grid cols="1" mdCols="2">
                            <x-form.input name="nickname" label="İşletme Takma Adı" hint="İşletmenizin kendinizin görebileceği takma adını girin." :value="old('nickname', $branch->settings?->nickname)" />
                            <x-form.input name="name" label="İşletme Adı" :value="old('name', $branch->name)" required />
                            <x-form.input name="phone" label="İşletme Telefon Numarası" :value="old('phone', $branch->phone)" required />
                        </x-layout.grid>
                    </x-layout.section>

                    <div class="flex justify-end">
                        <x-ui.button type="submit">Kaydet</x-ui.button>
                    </div>
                </form>

                {{-- Bakiye Ekle Formu --}}
                <form action="{{ route('bayi.isletme.bakiye-ekle', $branch->id) }}" method="POST" class="mt-6">
                    @csrf
                    <x-feedback.alert type="info" title="Bakiye Ekle">
                        <p class="text-sm mb-4">Girdiğiniz miktar mevcut bakiyeye eklenecektir.</p>
                        <div class="flex gap-4 items-end">
                            <div class="flex-1">
                                <x-form.input type="number" name="amount" label="Miktar" suffix="TL" required />
                            </div>
                            <x-ui.button type="submit">Bakiye Ekle</x-ui.button>
                        </div>
                    </x-feedback.alert>
                </form>
            </div>

            {{-- Past Orders Tab --}}
            <div x-show="activeTab === 'past_orders'" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <h3 class="text-lg font-semibold text-black dark:text-white">Geçmiş Siparişler</h3>
                    <div class="flex flex-wrap gap-2">
                        <x-form.input type="date" name="past_start" x-model="pastOrdersStartDate" size="sm" />
                        <x-form.input type="date" name="past_end" x-model="pastOrdersEndDate" size="sm" />
                        <x-ui.button @click="loadPastOrders()" size="sm">Filtrele</x-ui.button>
                    </div>
                </div>

                <x-table.table hoverable>
                    <x-table.thead>
                        <x-table.tr :hoverable="false">
                            <x-table.th>Sipariş No</x-table.th>
                            <x-table.th>Müşteri</x-table.th>
                            <x-table.th>Telefon</x-table.th>
                            <x-table.th>Tutar</x-table.th>
                            <x-table.th>Ödeme</x-table.th>
                            <x-table.th>Kurye</x-table.th>
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
                                <x-table.td class="text-gray-600 dark:text-gray-400" x-text="order.courier_name"></x-table.td>
                                <x-table.td class="text-gray-600 dark:text-gray-400" x-text="order.delivered_at"></x-table.td>
                            </x-table.tr>
                        </template>
                        <template x-if="pastOrders.length === 0">
                            <x-table.empty colspan="7" message="Sipariş bulunamadı" icon="package" />
                        </template>
                    </x-table.tbody>
                </x-table.table>
            </div>

            {{-- Cancelled Orders Tab --}}
            <div x-show="activeTab === 'cancelled_orders'" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <h3 class="text-lg font-semibold text-black dark:text-white">İptal Edilen Siparişler</h3>
                    <div class="flex flex-wrap gap-2">
                        <x-form.input type="date" name="cancelled_start" x-model="cancelledOrdersStartDate" size="sm" />
                        <x-form.input type="date" name="cancelled_end" x-model="cancelledOrdersEndDate" size="sm" />
                        <x-ui.button @click="loadCancelledOrders()" size="sm">Filtrele</x-ui.button>
                    </div>
                </div>

                <x-table.table hoverable>
                    <x-table.thead>
                        <x-table.tr :hoverable="false">
                            <x-table.th>Sipariş No</x-table.th>
                            <x-table.th>Müşteri</x-table.th>
                            <x-table.th>Telefon</x-table.th>
                            <x-table.th>Tutar</x-table.th>
                            <x-table.th>İptal Tarihi</x-table.th>
                        </x-table.tr>
                    </x-table.thead>
                    <x-table.tbody>
                        <template x-for="order in cancelledOrders" :key="order.id">
                            <x-table.tr>
                                <x-table.td x-text="order.order_number"></x-table.td>
                                <x-table.td x-text="order.customer_name"></x-table.td>
                                <x-table.td class="text-gray-600 dark:text-gray-400" x-text="order.customer_phone"></x-table.td>
                                <x-table.td class="font-medium" x-text="'₺' + order.total"></x-table.td>
                                <x-table.td class="text-gray-600 dark:text-gray-400" x-text="order.cancelled_at"></x-table.td>
                            </x-table.tr>
                        </template>
                        <template x-if="cancelledOrders.length === 0">
                            <x-table.empty colspan="5" message="İptal edilen sipariş bulunamadı" icon="x" />
                        </template>
                    </x-table.tbody>
                </x-table.table>
            </div>

            {{-- Statistics Tab --}}
            <div x-show="activeTab === 'statistics'" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <h3 class="text-lg font-semibold text-black dark:text-white">İstatistikler</h3>
                    <div class="flex flex-wrap gap-2">
                        <x-form.input type="date" name="stats_start" x-model="statsStartDate" size="sm" />
                        <x-form.input type="date" name="stats_end" x-model="statsEndDate" size="sm" />
                        <x-ui.button @click="loadStatistics()" size="sm">Filtrele</x-ui.button>
                    </div>
                </div>

                <x-layout.grid cols="1" mdCols="3" gap="6" class="mb-6">
                    <div class="p-6 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                        <div class="text-sm text-blue-600 dark:text-blue-400 font-medium mb-2">Toplam Sipariş</div>
                        <div class="text-3xl font-bold text-black dark:text-white mb-1" x-text="statistics.total_orders || 0"></div>
                    </div>
                    <div class="p-6 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 border border-red-200 dark:border-red-800 rounded-xl">
                        <div class="text-sm text-red-600 dark:text-red-400 font-medium mb-2">Toplam İptal</div>
                        <div class="text-3xl font-bold text-black dark:text-white mb-1" x-text="statistics.cancelled_orders || 0"></div>
                    </div>
                    <div class="p-6 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border border-green-200 dark:border-green-800 rounded-xl">
                        <div class="text-sm text-green-600 dark:text-green-400 font-medium mb-2">Toplam Tutar</div>
                        <div class="text-3xl font-bold text-black dark:text-white mb-1" x-text="'₺' + (statistics.total_amount || 0).toFixed(2)"></div>
                    </div>
                </x-layout.grid>

                <div class="p-6 bg-gray-50 dark:bg-black/50 rounded-xl">
                    <h4 class="text-md font-semibold text-black dark:text-white mb-4">Ödeme Metotlarına Göre Toplam Tutarlar</h4>
                    <div class="space-y-3">
                        <template x-for="method in statistics.payment_methods || []" :key="method.method">
                            <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800/50 rounded-lg">
                                <span class="text-sm font-medium text-black dark:text-white" x-text="method.method_label"></span>
                                <div class="text-right">
                                    <div class="text-sm font-bold text-black dark:text-white" x-text="'₺' + method.total.toFixed(2)"></div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400" x-text="method.count + ' adet'"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Detailed Statistics Tab --}}
            <div x-show="activeTab === 'detailed_statistics'" x-cloak>
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <h3 class="text-lg font-semibold text-black dark:text-white">Detaylı İstatistikler</h3>
                    <div class="flex flex-wrap gap-2">
                        <x-form.input type="date" name="detailed_start" x-model="detailedStatsStartDate" size="sm" />
                        <x-form.input type="date" name="detailed_end" x-model="detailedStatsEndDate" size="sm" />
                        <x-ui.button @click="loadDetailedStatistics()" size="sm">Filtrele</x-ui.button>
                    </div>
                </div>

                @foreach(['overall' => 'Genel İstatistikler', 'dealer' => 'Bayi İstatistikleri', 'business' => 'İşletme İstatistikleri'] as $key => $title)
                <div class="@if($key !== 'overall') pt-6 border-t border-gray-200 dark:border-gray-800 @endif mb-6">
                    <h4 class="text-md font-semibold text-black dark:text-white mb-4">{{ $title }}</h4>
                    <x-layout.grid cols="1" mdCols="4" gap="4">
                        <div class="p-4 bg-gray-50 dark:bg-black/50 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Toplam Sipariş</div>
                            <div class="text-2xl font-bold text-black dark:text-white" x-text="detailedStatistics.{{ $key }}?.total_orders || 0"></div>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-black/50 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">İptal Sipariş</div>
                            <div class="text-2xl font-bold text-black dark:text-white" x-text="detailedStatistics.{{ $key }}?.cancelled_orders || 0"></div>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-black/50 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Bedelli İptal</div>
                            <div class="text-2xl font-bold text-black dark:text-white" x-text="detailedStatistics.{{ $key }}?.paid_cancellations || 0"></div>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-black/50 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Toplam Tutar</div>
                            <div class="text-2xl font-bold text-black dark:text-white" x-text="'₺' + (detailedStatistics.{{ $key }}?.total_amount || 0).toFixed(2)"></div>
                        </div>
                    </x-layout.grid>
                </div>
                @endforeach
            </div>

            {{-- Pricing Tab --}}
            <div x-show="activeTab === 'pricing'" x-cloak>
                {{-- Existing Policies --}}
                <div x-show="existingPolicies.length > 0" class="mb-8">
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Mevcut Politikalar</h3>
                    <div class="space-y-3">
                        <template x-for="policy in existingPolicies" :key="policy.id">
                            <div class="p-4 bg-gray-50 dark:bg-black/50 border border-gray-200 dark:border-gray-800 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-md font-semibold text-black dark:text-white" x-text="policy.name"></h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1" x-text="policy.description"></p>
                                        <div class="flex items-center gap-4 mt-2">
                                            <x-ui.badge type="info" x-text="policy.type === 'business' ? 'İşletme' : 'Kurye'" />
                                            <span class="text-xs text-gray-600 dark:text-gray-400" x-text="getPolicyTypeLabel(policy.policy_type)"></span>
                                            <x-ui.badge :type="'success'" x-bind:class="policy.is_active ? '' : 'bg-gray-100 text-gray-600'" x-text="policy.is_active ? 'Aktif' : 'Pasif'" />
                                        </div>
                                    </div>
                                    <x-ui.button variant="danger" size="sm" @click="deletePolicy(policy.id)">Sil</x-ui.button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Add New Policy --}}
                <div>
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Yeni Politika Ekle</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">İşletme ve kurye fiyatlandırma politikalarınızı buradan yönetebilirsiniz.</p>

                    <x-layout.grid cols="1" mdCols="2" gap="6">
                        @php
                            $policyTypes = [
                                'fixed' => ['title' => 'Sabit Fiyat & Sabit Yüzdelik', 'desc' => 'Sabit fiyat ve sabit yüzdelik politikası ile alınan komisyonlar sabitlenir.'],
                                'package_based' => ['title' => 'Paket Tutarına Göre', 'desc' => 'Paket tutarına göre değişen politika ile alınan komisyonlar paket tutarına göre değişir.'],
                                'distance_based' => ['title' => 'Teslimat Mesafesine Göre', 'desc' => 'Teslimat mesafesine göre değişen politika ile komisyonlar mesafeye göre değişir.'],
                                'periodic' => ['title' => 'Periyodik Politika', 'desc' => 'Belirlediğiniz periyotlardaki toplam paket sayısına göre komisyonlar değişir.'],
                                'unit_price' => ['title' => 'Mesafe Birim Fiyat', 'desc' => 'Teslimat mesafesine göre belirlenen birim fiyat politikası.'],
                                'consecutive_discount' => ['title' => 'Ardışık Paket İndirimi', 'desc' => 'Peşpeşe paket çıkarttığında komisyon kademeli olarak azalır.'],
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
        </div>
    </x-ui.card>

    {{-- Pricing Policy Modal --}}
    <x-ui.modal name="policyModal" title="Yeni Politika Oluştur" size="md">
        <div x-show="showPolicyModal">
            <form @submit.prevent="createPolicy" class="space-y-4">
                <x-form.select name="policy_type" label="Politika Tipi" x-model="policyForm.type" :options="['business' => 'İşletme', 'courier' => 'Kurye']" required />
                <x-form.input name="policy_name" label="Politika Adı" x-model="policyForm.name" required />
                <x-form.textarea name="policy_description" label="Açıklama" x-model="policyForm.description" :rows="3" />
                <x-form.checkbox name="policy_is_active" label="Aktif" x-model="policyForm.is_active" />

                <div class="flex gap-3 pt-4">
                    <x-ui.button type="button" variant="secondary" @click="showPolicyModal = false" class="flex-1">İptal</x-ui.button>
                    <x-ui.button type="submit" class="flex-1">Oluştur</x-ui.button>
                </div>
            </form>
        </div>
    </x-ui.modal>
</div>

{{-- Confirm Modal --}}
<x-ui.confirm-modal name="deletePolicyModal" title="Politika Sil" type="danger" />

@push('scripts')
<script>
function branchEditApp() {
    return {
        activeTab: 'settings',
        pastOrders: [],
        cancelledOrders: [],
        statistics: {},
        detailedStatistics: {},
        pastOrdersStartDate: new Date().toISOString().split('T')[0],
        pastOrdersEndDate: new Date().toISOString().split('T')[0],
        cancelledOrdersStartDate: new Date().toISOString().split('T')[0],
        cancelledOrdersEndDate: new Date().toISOString().split('T')[0],
        statsStartDate: new Date().toISOString().split('T')[0],
        statsEndDate: new Date().toISOString().split('T')[0],
        detailedStatsStartDate: new Date().toISOString().split('T')[0],
        detailedStatsEndDate: new Date().toISOString().split('T')[0],
        existingPolicies: @json($branch->pricingPolicies ?? []),
        showPolicyModal: false,
        selectedPolicyType: '',
        policyForm: { type: 'business', policy_type: '', name: '', description: '', is_active: true },

        async loadPastOrders() {
            try {
                const response = await fetch(`/bayi/isletmelerim/{{ $branch->id }}/siparisler?type=past&start_date=${this.pastOrdersStartDate}&end_date=${this.pastOrdersEndDate}`);
                this.pastOrders = await response.json();
            } catch (error) { console.error('Failed to load past orders:', error); }
        },

        async loadCancelledOrders() {
            try {
                const response = await fetch(`/bayi/isletmelerim/{{ $branch->id }}/siparisler?type=cancelled&start_date=${this.cancelledOrdersStartDate}&end_date=${this.cancelledOrdersEndDate}`);
                this.cancelledOrders = await response.json();
            } catch (error) { console.error('Failed to load cancelled orders:', error); }
        },

        async loadStatistics() {
            try {
                const response = await fetch(`/bayi/isletmelerim/{{ $branch->id }}/istatistikler?start_date=${this.statsStartDate}&end_date=${this.statsEndDate}`);
                this.statistics = await response.json();
            } catch (error) { console.error('Failed to load statistics:', error); }
        },

        async loadDetailedStatistics() {
            try {
                const response = await fetch(`/bayi/isletmelerim/{{ $branch->id }}/detayli-istatistikler?start_date=${this.detailedStatsStartDate}&end_date=${this.detailedStatsEndDate}`);
                this.detailedStatistics = await response.json();
            } catch (error) { console.error('Failed to load detailed statistics:', error); }
        },

        openPolicyModal(policyType) {
            this.selectedPolicyType = policyType;
            this.policyForm.policy_type = policyType;
            this.policyForm.name = this.getPolicyTypeLabel(policyType);
            this.showPolicyModal = true;
        },

        async createPolicy() {
            try {
                const response = await fetch('/bayi/isletmelerim/{{ $branch->id }}/pricing-policies', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify(this.policyForm)
                });
                const data = await response.json();
                if (data.success) {
                    this.existingPolicies.push(data.policy);
                    this.showPolicyModal = false;
                    showToast(data.message, 'success');
                }
            } catch (error) { showToast('Politika oluşturulurken bir hata oluştu.', 'error'); }
        },

        deletePolicy(policyId) {
            const self = this;
            window.dispatchEvent(new CustomEvent('open-confirm', {
                detail: {
                    title: 'Politika Sil',
                    message: 'Bu politikayı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.',
                    confirmText: 'Sil',
                    cancelText: 'Vazgeç',
                    onConfirm: async () => {
                        try {
                            const response = await fetch(`/bayi/isletmelerim/{{ $branch->id }}/pricing-policies/${policyId}`, {
                                method: 'DELETE',
                                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                            });
                            const data = await response.json();
                            if (data.success) {
                                self.existingPolicies = self.existingPolicies.filter(p => p.id !== policyId);
                                showToast(data.message, 'success');
                            }
                        } catch (error) { showToast('Politika silinirken bir hata oluştu.', 'error'); }
                    }
                }
            }));
        },

        getPolicyTypeLabel(type) {
            const labels = { 'fixed': 'Sabit Fiyat & Sabit Yüzdelik', 'package_based': 'Paket Tutarına Göre', 'distance_based': 'Teslimat Mesafesine Göre', 'periodic': 'Periyodik Politika', 'unit_price': 'Mesafe Birim Fiyat', 'consecutive_discount': 'Ardışık Paket İndirimi' };
            return labels[type] || type;
        }
    }
}
</script>
@endpush
@endsection
