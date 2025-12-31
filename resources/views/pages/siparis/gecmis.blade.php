@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Sipariş Geçmişi"
        subtitle="Tamamlanan tüm siparişler"
    >
        <x-slot name="icon">
            <x-ui.icon name="history" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>
    </x-layout.page-header>

    {{-- Filtreler --}}
    <x-ui.card class="mb-6">
        <form method="GET" action="{{ route('siparis.gecmis') }}">
            <x-layout.grid cols="1" mdCols="4" gap="4">
                <x-form.input type="date" name="start_date" label="Başlangıç Tarihi" :value="request('start_date')" />
                <x-form.input type="date" name="end_date" label="Bitiş Tarihi" :value="request('end_date')" />
                <x-form.input name="search" label="Arama" :value="request('search')" placeholder="Sipariş No, Müşteri..." />
                <div class="flex items-end">
                    <x-ui.button type="submit" icon="filter" class="w-full">
                        Filtrele
                    </x-ui.button>
                </div>
            </x-layout.grid>
        </form>
    </x-ui.card>

    {{-- İstatistik Kartları --}}
    <x-layout.grid cols="1" mdCols="4" gap="6" class="mb-6">
        <x-ui.stat-card
            title="Toplam Sipariş"
            :value="$orders->total()"
            icon="order"
            color="blue"
        />
        <x-ui.stat-card
            title="Toplam Gelir"
            :value="'₺' . number_format($orders->sum('total'), 2)"
            icon="money"
            color="green"
        />
        <x-ui.stat-card
            title="Ortalama Sepet"
            :value="'₺' . ($orders->count() > 0 ? number_format($orders->avg('total'), 2) : '0.00')"
            icon="chart"
            color="purple"
        />
        <x-ui.stat-card
            title="Bu Sayfa"
            :value="$orders->count()"
            icon="list"
            color="orange"
        />
    </x-layout.grid>

    {{-- Sipariş Listesi --}}
    <x-ui.card>
        <x-table.table hoverable>
            <x-table.thead>
                <x-table.tr :hoverable="false">
                    <x-table.th>Sipariş No</x-table.th>
                    <x-table.th>Müşteri</x-table.th>
                    <x-table.th>Tutar</x-table.th>
                    <x-table.th>Kurye</x-table.th>
                    <x-table.th>Tarih</x-table.th>
                    <x-table.th>Süre</x-table.th>
                </x-table.tr>
            </x-table.thead>

            <x-table.tbody>
                @forelse($orders as $order)
                <x-table.tr>
                    <x-table.td>
                        <span class="font-mono font-semibold text-black dark:text-white">{{ $order->order_number }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-black dark:text-white">{{ $order->customer_name }}</span>
                    </x-table.td>
                    <x-table.td>
                        <x-data.money :amount="$order->total" />
                    </x-table.td>
                    <x-table.td>
                        @if($order->courier)
                            <span class="text-gray-600 dark:text-gray-400">{{ $order->courier->name }}</span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </x-table.td>
                    <x-table.td>
                        <x-data.date-time :date="$order->created_at" />
                    </x-table.td>
                    <x-table.td>
                        @if($order->delivered_at && $order->accepted_at)
                            <span class="text-gray-600 dark:text-gray-400">{{ $order->accepted_at->diffInMinutes($order->delivered_at) }} dk</span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </x-table.td>
                </x-table.tr>
                @empty
                <x-table.empty colspan="6" icon="history" message="Geçmiş sipariş bulunamadı" />
                @endforelse
            </x-table.tbody>
        </x-table.table>

        @if($orders->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $orders->links() }}
        </div>
        @endif
    </x-ui.card>
</div>
@endsection
