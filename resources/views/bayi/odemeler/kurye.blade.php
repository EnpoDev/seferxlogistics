@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn" x-data="{
    selectedCourier: null,
    showPaymentModal: false,
    paymentAmount: 0,
    paymentNotes: '',
    loading: false,

    openPaymentModal(courier) {
        this.selectedCourier = courier;
        this.paymentAmount = courier.estimated_earnings;
        this.paymentNotes = '';
        this.showPaymentModal = true;
    },

    async submitPayment() {
        if (!this.selectedCourier || this.paymentAmount <= 0) {
            showToast('Gecersiz tutar', 'error');
            return;
        }

        this.loading = true;

        try {
            const response = await fetch('{{ route('bayi.kurye-odemeler.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    courier_id: this.selectedCourier.id,
                    amount: this.paymentAmount,
                    notes: this.paymentNotes
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, 'success');
                this.showPaymentModal = false;
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Bir hata olustu', 'error');
            }
        } catch (error) {
            showToast('Bir hata olustu', 'error');
        } finally {
            this.loading = false;
        }
    }
}">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Kurye Odemeleri"
        subtitle="Kurye odeme islemlerini yonetin"
    >
        <x-slot name="icon">
            <x-ui.icon name="money" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <x-ui.button href="{{ route('bayi.nakit-odemeler') }}" variant="secondary">
                Nakit Islemler
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Ozet Kartlari --}}
    <x-layout.grid cols="1" mdCols="3" gap="4" class="mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-5 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Toplam Kurye</p>
                    <p class="text-3xl font-bold">{{ $couriers->count() }}</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <x-ui.icon name="users" class="w-8 h-8" />
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-5 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Toplam Teslimat</p>
                    <p class="text-3xl font-bold">{{ $couriers->sum(fn($c) => $c->orders->count()) }}</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <x-ui.icon name="truck" class="w-8 h-8" />
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-5 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Toplam Hakedis</p>
                    <p class="text-3xl font-bold">
                        @php
                            $totalEarnings = $couriers->sum(function($c) {
                                return $c->orders->sum(fn($o) => \App\Models\PricingPolicy::calculateCourierEarnings($o));
                            });
                        @endphp
                        <x-data.money :amount="$totalEarnings" />
                    </p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <x-ui.icon name="money" class="w-8 h-8" />
                </div>
            </div>
        </div>
    </x-layout.grid>

    {{-- Odeme Listesi --}}
    <x-ui.card>
        <x-table.table hoverable>
            <x-table.thead>
                <x-table.tr :hoverable="false">
                    <x-table.th>Kurye</x-table.th>
                    <x-table.th align="center">Teslimat (Bu Ay)</x-table.th>
                    <x-table.th align="right">Hakedis</x-table.th>
                    <x-table.th align="center">Durum</x-table.th>
                    <x-table.th align="right">Islemler</x-table.th>
                </x-table.tr>
            </x-table.thead>
            <x-table.tbody>
                @forelse($couriers as $courier)
                    @php
                        $deliveryCount = $courier->orders->count();
                        $estimatedEarnings = $courier->orders->sum(fn($o) => \App\Models\PricingPolicy::calculateCourierEarnings($o));
                        $paidAmount = $courier->cashTransactions()
                            ->where('type', 'advance_given')
                            ->where('status', 'completed')
                            ->whereMonth('created_at', now()->month)
                            ->sum('amount');
                        $remaining = $estimatedEarnings - $paidAmount;
                    @endphp
                    <x-table.tr>
                        <x-table.td>
                            <x-data.courier-avatar :courier="$courier" size="sm" :showStatus="false" />
                        </x-table.td>
                        <x-table.td align="center">
                            <span class="text-lg font-bold text-black dark:text-white">{{ $deliveryCount }}</span>
                        </x-table.td>
                        <x-table.td align="right">
                            <div>
                                <span class="text-lg font-bold text-green-600 dark:text-green-400">
                                    <x-data.money :amount="$estimatedEarnings" />
                                </span>
                                @if($paidAmount > 0)
                                    <p class="text-xs text-gray-500">Odenen: <x-data.money :amount="$paidAmount" /></p>
                                    <p class="text-xs font-medium text-orange-600 dark:text-orange-400">Kalan: <x-data.money :amount="$remaining" /></p>
                                @endif
                            </div>
                        </x-table.td>
                        <x-table.td align="center">
                            @if($remaining <= 0 && $estimatedEarnings > 0)
                                <x-ui.badge type="success" size="sm">Odendi</x-ui.badge>
                            @elseif($paidAmount > 0)
                                <x-ui.badge type="warning" size="sm">Kismen Odendi</x-ui.badge>
                            @elseif($estimatedEarnings > 0)
                                <x-ui.badge type="danger" size="sm">Bekliyor</x-ui.badge>
                            @else
                                <x-ui.badge type="default" size="sm">Islem Yok</x-ui.badge>
                            @endif
                        </x-table.td>
                        <x-table.td align="right">
                            @if($remaining > 0)
                                <x-ui.button
                                    variant="ghost"
                                    size="sm"
                                    @click="openPaymentModal({
                                        id: {{ $courier->id }},
                                        name: '{{ $courier->name }}',
                                        estimated_earnings: {{ $remaining }},
                                        delivery_count: {{ $deliveryCount }}
                                    })"
                                >
                                    Odeme Yap
                                </x-ui.button>
                            @endif
                            <a href="{{ route('bayi.kurye-detay', $courier->id) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm ml-2">
                                Detay
                            </a>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.empty colspan="5" icon="users" message="Bu ay henuz teslimat yapilmamis" />
                @endforelse
            </x-table.tbody>
        </x-table.table>
    </x-ui.card>

    {{-- Odeme Modal --}}
    <div x-show="showPaymentModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showPaymentModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white dark:bg-[#181818] rounded-xl shadow-xl w-full max-w-md" @click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-black dark:text-white">Kurye Odemesi</h3>
                    <button @click="showPaymentModal = false" class="text-gray-500 hover:text-black dark:hover:text-white">
                        <x-ui.icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Kurye</p>
                        <p class="text-lg font-bold text-black dark:text-white" x-text="selectedCourier?.name"></p>
                        <p class="text-sm text-gray-500" x-text="selectedCourier?.delivery_count + ' teslimat'"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Odeme Tutari</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">TL</span>
                            <input type="number"
                                   x-model="paymentAmount"
                                   step="0.01"
                                   min="0"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-black dark:text-white focus:ring-2 focus:ring-black dark:focus:ring-white"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Not (Opsiyonel)</label>
                        <textarea x-model="paymentNotes"
                                  rows="2"
                                  class="w-full px-4 py-3 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-black dark:text-white focus:ring-2 focus:ring-black dark:focus:ring-white"
                                  placeholder="Odeme notu..."
                        ></textarea>
                    </div>
                </div>

                <div class="flex gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-800">
                    <x-ui.button type="button" variant="secondary" @click="showPaymentModal = false" class="flex-1">
                        Iptal
                    </x-ui.button>
                    <x-ui.button type="button" @click="submitPayment()" x-bind:disabled="loading" class="flex-1">
                        <span x-show="!loading">Odemeyi Kaydet</span>
                        <span x-show="loading">Kaydediliyor...</span>
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
