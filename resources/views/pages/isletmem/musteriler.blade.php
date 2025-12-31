@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Müşteri Yönetimi"
        subtitle="Müşterilerinizi görüntüleyin ve yönetin"
    >
        <x-slot name="icon">
            <x-ui.icon name="users" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <x-ui.button icon="plus">
                Yeni Müşteri
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Arama --}}
    <x-ui.card class="mb-6">
        <form method="GET" action="{{ route('isletmem.musteriler') }}" class="flex gap-4">
            <x-form.search-input name="search" :value="request('search')" placeholder="Müşteri ara..." class="flex-1" />
            <x-ui.button type="submit" icon="search">Ara</x-ui.button>
        </form>
    </x-ui.card>

    {{-- Müşteri Listesi --}}
    <x-ui.card>
        <x-table.table hoverable>
            <x-table.thead>
                <x-table.tr :hoverable="false">
                    <x-table.th>Ad Soyad</x-table.th>
                    <x-table.th>Telefon</x-table.th>
                    <x-table.th>Adres</x-table.th>
                    <x-table.th>Sipariş Sayısı</x-table.th>
                    <x-table.th>Son Sipariş</x-table.th>
                    <x-table.th align="right">İşlemler</x-table.th>
                </x-table.tr>
            </x-table.thead>

            <x-table.tbody>
                @forelse($customers as $customer)
                <x-table.tr>
                    <x-table.td>
                        <span class="text-black dark:text-white">{{ $customer->customer_name ?? 'İsimsiz Müşteri' }}</span>
                    </x-table.td>
                    <x-table.td>
                        <x-data.phone :number="$customer->customer_phone" />
                    </x-table.td>
                    <x-table.td>
                        <span class="text-gray-600 dark:text-gray-400 max-w-xs truncate block" title="{{ $customer->customer_address }}">
                            {{ $customer->customer_address ?? '-' }}
                        </span>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-black dark:text-white">{{ $customer->order_count }}</span>
                    </x-table.td>
                    <x-table.td>
                        @if($customer->last_order_date)
                            <x-data.date-time :date="$customer->last_order_date" />
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </x-table.td>
                    <x-table.td align="right">
                        <x-ui.button variant="ghost" size="sm">Görüntüle</x-ui.button>
                    </x-table.td>
                </x-table.tr>
                @empty
                <x-table.empty colspan="6" icon="users" message="Müşteri bulunamadı" />
                @endforelse
            </x-table.tbody>
        </x-table.table>

        @if($customers->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $customers->links() }}
        </div>
        @endif
    </x-ui.card>
</div>
@endsection
