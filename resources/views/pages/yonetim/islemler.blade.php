@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="İşlemlerim"
        subtitle="Ödeme geçmişinizi görüntüleyin"
    >
        <x-slot name="icon">
            <x-ui.icon name="document" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>
    </x-layout.page-header>

    {{-- Filtreler --}}
    <x-ui.card class="mb-6">
        <form method="GET" action="{{ route('yonetim.islemler') }}">
            <x-layout.grid cols="1" mdCols="3" gap="4">
                <x-form.input type="date" name="start_date" label="Başlangıç Tarihi" :value="request('start_date')" />
                <x-form.input type="date" name="end_date" label="Bitiş Tarihi" :value="request('end_date')" />
                <div class="flex items-end gap-2">
                    <x-ui.button type="submit" class="flex-1">Filtrele</x-ui.button>
                    @if(request('start_date') || request('end_date'))
                        <x-ui.button href="{{ route('yonetim.islemler') }}" variant="secondary">Temizle</x-ui.button>
                    @endif
                </div>
            </x-layout.grid>
        </form>
    </x-ui.card>

    {{-- İşlem Listesi --}}
    <x-ui.card>
        <x-table.table hoverable>
            <x-table.thead>
                <x-table.tr :hoverable="false">
                    <x-table.th>Tarih</x-table.th>
                    <x-table.th>Açıklama</x-table.th>
                    <x-table.th>Tutar</x-table.th>
                    <x-table.th>Durum</x-table.th>
                    <x-table.th>Fatura</x-table.th>
                </x-table.tr>
            </x-table.thead>

            <x-table.tbody>
                @forelse($transactions as $transaction)
                <x-table.tr>
                    <x-table.td>
                        <x-data.date-time :date="$transaction->paid_at ?? $transaction->created_at" />
                    </x-table.td>
                    <x-table.td>
                        <p class="text-black dark:text-white">{{ $transaction->description }}</p>
                        @if($transaction->invoice_number)
                            <p class="text-xs text-gray-500">{{ $transaction->invoice_number }}</p>
                        @endif
                    </x-table.td>
                    <x-table.td>
                        <span class="font-medium text-black dark:text-white">{{ $transaction->getFormattedAmount() }}</span>
                        @if($transaction->refund_amount)
                            <br><span class="text-xs text-red-500">İade: -₺{{ number_format($transaction->refund_amount, 2, ',', '.') }}</span>
                        @endif
                    </x-table.td>
                    <x-table.td>
                        @php
                            $statusTypes = [
                                'completed' => 'success',
                                'pending' => 'warning',
                                'failed' => 'danger',
                                'refunded' => 'info',
                                'partially_refunded' => 'info',
                            ];
                        @endphp
                        <x-ui.badge :type="$statusTypes[$transaction->status] ?? 'default'">
                            {{ $transaction->getStatusLabel() }}
                        </x-ui.badge>
                    </x-table.td>
                    <x-table.td>
                        @if($transaction->invoice_number && $transaction->status === 'completed')
                            <x-ui.button href="{{ route('billing.invoice.download', $transaction) }}" variant="ghost" size="sm" icon="download">
                                İndir
                            </x-ui.button>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </x-table.td>
                </x-table.tr>
                @empty
                <x-table.empty colspan="5" icon="document" message="İşlem bulunamadı" />
                @endforelse
            </x-table.tbody>
        </x-table.table>

        @if($transactions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $transactions->withQueryString()->links() }}
        </div>
        @endif
    </x-ui.card>
</div>
@endsection
