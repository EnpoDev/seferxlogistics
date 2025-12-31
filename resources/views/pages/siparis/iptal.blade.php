@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="İptal Edilen Siparişler"
        subtitle="İptal edilen ve reddedilen siparişler"
    >
        <x-slot name="icon">
            <x-ui.icon name="close" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>
    </x-layout.page-header>

    {{-- Filtreler --}}
    <x-ui.card class="mb-6">
        <form method="GET" action="{{ route('siparis.iptal') }}">
            <x-layout.grid cols="1" mdCols="4" gap="4">
                <x-form.select name="reason" label="İptal Nedeni" :selected="request('reason')" :options="[
                    '' => 'Tümü',
                    'customer_request' => 'Müşteri İstedi',
                    'out_of_stock' => 'Ürün Yok',
                    'delivery_issue' => 'Teslimat Sorunu',
                    'other' => 'Diğer',
                ]" />
                <x-form.input type="date" name="date" label="Tarih" :value="request('date')" />
                <x-form.input name="search" label="Arama" :value="request('search')" placeholder="Sipariş No..." />
                <div class="flex items-end">
                    <x-ui.button type="submit" icon="filter" class="w-full">
                        Filtrele
                    </x-ui.button>
                </div>
            </x-layout.grid>
        </form>
    </x-ui.card>

    {{-- İstatistik Kartları --}}
    <x-layout.grid cols="1" mdCols="3" gap="6" class="mb-6">
        <x-ui.stat-card
            title="Toplam İptal"
            :value="$cancelledOrders->total() ?? 0"
            icon="close"
            color="red"
        />
        <x-ui.stat-card
            title="Bu Ay"
            :value="$thisMonthCancelled ?? 0"
            icon="calendar"
            color="orange"
        />
        <x-ui.stat-card
            title="İptal Oranı"
            :value="($cancellationRate ?? 0) . '%'"
            icon="chart"
            color="purple"
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
                    <x-table.th>İptal Nedeni</x-table.th>
                    <x-table.th>İptal Eden</x-table.th>
                    <x-table.th>Tarih</x-table.th>
                </x-table.tr>
            </x-table.thead>

            <x-table.tbody>
                @forelse($cancelledOrders ?? [] as $order)
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
                        <span class="text-gray-600 dark:text-gray-400">{{ $order->cancellation_reason ?? '-' }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-gray-600 dark:text-gray-400">{{ $order->cancelled_by ?? '-' }}</span>
                    </x-table.td>
                    <x-table.td>
                        <x-data.date-time :date="$order->cancelled_at ?? $order->updated_at" />
                    </x-table.td>
                </x-table.tr>
                @empty
                <x-table.empty colspan="6" icon="close" message="İptal edilmiş sipariş bulunamadı" />
                @endforelse
            </x-table.tbody>
        </x-table.table>

        @if(isset($cancelledOrders) && $cancelledOrders->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $cancelledOrders->links() }}
        </div>
        @endif
    </x-ui.card>
</div>
@endsection
