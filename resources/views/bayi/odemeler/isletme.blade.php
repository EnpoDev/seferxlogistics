@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn" x-data="{
    selectedBranch: null,
    showCollectionModal: false,
    collectionAmount: 0,
    collectionNotes: '',
    loading: false,

    openCollectionModal(branch) {
        this.selectedBranch = branch;
        this.collectionAmount = branch.commission;
        this.collectionNotes = '';
        this.showCollectionModal = true;
    },

    async submitCollection() {
        if (!this.selectedBranch || this.collectionAmount <= 0) {
            showToast('Gecersiz tutar', 'error');
            return;
        }

        this.loading = true;

        try {
            const response = await fetch(`/bayi/odemeler/isletme/${this.selectedBranch.id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    amount: this.collectionAmount,
                    notes: this.collectionNotes
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, 'success');
                this.showCollectionModal = false;
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
        title="Isletme Odemeleri"
        subtitle="Isletme odeme islemlerini takip edin"
    >
        <x-slot name="icon">
            <x-ui.icon name="building" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <x-ui.button href="{{ route('bayi.isletme-odemeler.rapor') }}" variant="secondary">
                <x-ui.icon name="download" class="w-4 h-4 mr-2" />
                Rapor Al
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Ozet Kartlari --}}
    <x-layout.grid cols="1" mdCols="3" gap="4" class="mb-6">
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-5 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Toplam Isletme</p>
                    <p class="text-3xl font-bold">{{ $branches->count() }}</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <x-ui.icon name="building" class="w-8 h-8" />
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-5 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Toplam Siparis</p>
                    <p class="text-3xl font-bold">{{ $branches->sum(fn($b) => $b->orders->count()) }}</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <x-ui.icon name="list" class="w-8 h-8" />
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-5 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Toplam Komisyon</p>
                    <p class="text-3xl font-bold">
                        @php
                            $totalCommission = $branches->sum(function($b) {
                                return $b->orders->sum('total') * 0.1;
                            });
                        @endphp
                        <x-data.money :amount="$totalCommission" />
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
                    <x-table.th>Isletme</x-table.th>
                    <x-table.th align="center">Siparis</x-table.th>
                    <x-table.th align="right">Ciro</x-table.th>
                    <x-table.th align="right">Komisyon (%10)</x-table.th>
                    <x-table.th align="right">Islemler</x-table.th>
                </x-table.tr>
            </x-table.thead>
            <x-table.tbody>
                @forelse($branches as $branch)
                    @php
                        $orderCount = $branch->orders->count();
                        $totalRevenue = $branch->orders->sum('total');
                        $commission = $totalRevenue * 0.1;
                    @endphp
                    <x-table.tr>
                        <x-table.td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center text-purple-600 dark:text-purple-300 font-bold">
                                    {{ substr($branch->name, 0, 2) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-black dark:text-white">{{ $branch->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $branch->phone }}</p>
                                </div>
                            </div>
                        </x-table.td>
                        <x-table.td align="center">
                            <span class="text-lg font-bold text-black dark:text-white">{{ $orderCount }}</span>
                        </x-table.td>
                        <x-table.td align="right">
                            <span class="text-sm font-medium text-black dark:text-white">
                                <x-data.money :amount="$totalRevenue" />
                            </span>
                        </x-table.td>
                        <x-table.td align="right">
                            <span class="text-lg font-bold text-green-600 dark:text-green-400">
                                <x-data.money :amount="$commission" />
                            </span>
                        </x-table.td>
                        <x-table.td align="right">
                            @if($commission > 0)
                                <x-ui.button
                                    variant="ghost"
                                    size="sm"
                                    @click="openCollectionModal({
                                        id: {{ $branch->id }},
                                        name: '{{ $branch->name }}',
                                        order_count: {{ $orderCount }},
                                        revenue: {{ $totalRevenue }},
                                        commission: {{ $commission }}
                                    })"
                                >
                                    Tahsil Et
                                </x-ui.button>
                            @endif
                            <a href="{{ route('bayi.isletme-detay', $branch->id) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm ml-2">
                                Detay
                            </a>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.empty colspan="5" icon="building" message="Isletme odemesi bulunamadi" />
                @endforelse
            </x-table.tbody>
        </x-table.table>
    </x-ui.card>

    {{-- Tahsil Modal --}}
    <div x-show="showCollectionModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showCollectionModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white dark:bg-[#181818] rounded-xl shadow-xl w-full max-w-md" @click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-black dark:text-white">Komisyon Tahsilati</h3>
                    <button @click="showCollectionModal = false" class="text-gray-500 hover:text-black dark:hover:text-white">
                        <x-ui.icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Isletme</p>
                        <p class="text-lg font-bold text-black dark:text-white" x-text="selectedBranch?.name"></p>
                        <div class="flex gap-4 mt-2 text-sm text-gray-500">
                            <span x-text="selectedBranch?.order_count + ' siparis'"></span>
                            <span x-text="'₺' + selectedBranch?.revenue?.toFixed(2) + ' ciro'"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Tahsilat Tutari</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">TL</span>
                            <input type="number"
                                   x-model="collectionAmount"
                                   step="0.01"
                                   min="0"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-black dark:text-white focus:ring-2 focus:ring-black dark:focus:ring-white"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Not (Opsiyonel)</label>
                        <textarea x-model="collectionNotes"
                                  rows="2"
                                  class="w-full px-4 py-3 border border-gray-200 dark:border-gray-800 rounded-lg bg-white dark:bg-gray-950 text-black dark:text-white focus:ring-2 focus:ring-black dark:focus:ring-white"
                                  placeholder="Tahsilat notu..."
                        ></textarea>
                    </div>
                </div>

                <div class="flex gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-800">
                    <x-ui.button type="button" variant="secondary" @click="showCollectionModal = false" class="flex-1">
                        Iptal
                    </x-ui.button>
                    <x-ui.button type="button" @click="submitCollection()" x-bind:disabled="loading" class="flex-1">
                        <span x-show="!loading">Tahsilati Kaydet</span>
                        <span x-show="loading">Kaydediliyor...</span>
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
