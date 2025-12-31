@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Siparişler"
        subtitle="Tüm siparişlerinizi görüntüleyin ve yönetin"
    >
        <x-slot name="icon">
            <x-ui.icon name="order" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <x-ui.button href="{{ route('siparis.create') }}" icon="plus">
                Yeni Sipariş
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Filtreler --}}
    <x-ui.card class="mb-6">
        <form method="GET" action="{{ route('siparis.liste') }}">
            <x-layout.grid cols="1" mdCols="4" gap="4">
                <x-form.select name="status" label="Durum" :selected="request('status', 'all')" :options="[
                    'all' => 'Tümü',
                    'pending' => 'Beklemede',
                    'preparing' => 'Hazırlanıyor',
                    'on_delivery' => 'Yolda',
                    'delivered' => 'Teslim Edildi',
                ]" />

                <x-form.input type="date" name="date" label="Tarih" :value="request('date')" />

                <x-form.input name="search" label="Arama" :value="request('search')" placeholder="Sipariş No, Müşteri..." />

                <div class="flex items-end">
                    <x-ui.button type="submit" icon="filter" class="w-full">
                        Filtrele
                    </x-ui.button>
                </div>
            </x-layout.grid>
        </form>
    </x-ui.card>

    {{-- Sipariş Listesi --}}
    <x-ui.card>
        <x-table.table hoverable>
            <x-table.thead>
                <x-table.tr :hoverable="false">
                    <x-table.th>Sipariş No</x-table.th>
                    <x-table.th>Müşteri</x-table.th>
                    <x-table.th>Tutar</x-table.th>
                    <x-table.th>Durum</x-table.th>
                    <x-table.th>Kurye</x-table.th>
                    <x-table.th>Zaman</x-table.th>
                    <x-table.th align="right">İşlemler</x-table.th>
                </x-table.tr>
            </x-table.thead>

            <x-table.tbody>
                @forelse($orders as $order)
                <x-table.tr>
                    <x-table.td>
                        <span class="text-sm font-mono font-semibold text-black dark:text-white">{{ $order->order_number }}</span>
                    </x-table.td>
                    <x-table.td>
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">{{ $order->customer_name }}</p>
                            <x-data.phone :number="$order->customer_phone" :clickable="false" class="text-xs text-gray-500" />
                        </div>
                    </x-table.td>
                    <x-table.td>
                        <x-data.money :amount="$order->total" class="font-bold" />
                    </x-table.td>
                    <x-table.td>
                        <x-data.status-badge :status="$order->status" entity="order" />
                    </x-table.td>
                    <x-table.td>
                        @if($order->courier)
                            <x-data.courier-avatar :courier="$order->courier" size="xs" :showStatus="false" :showPhone="false" />
                        @else
                            <span class="text-sm text-gray-400">-</span>
                        @endif
                    </x-table.td>
                    <x-table.td>
                        <x-data.date-time :date="$order->created_at" relative />
                    </x-table.td>
                    <x-table.td align="right" nowrap>
                        <div class="flex items-center justify-end gap-2">
                            <x-ui.button href="{{ route('siparis.edit', $order) }}" variant="ghost" size="sm" icon="edit">
                                Düzenle
                            </x-ui.button>
                            @if(in_array($order->status, ['pending', 'cancelled']))
                            <x-ui.button type="button" variant="ghost" size="sm" onclick="confirmDelete({{ $order->id }})">
                                Sil
                            </x-ui.button>
                            <form id="delete-form-{{ $order->id }}" action="{{ route('siparis.destroy', $order) }}" method="POST" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                            @endif
                        </div>
                    </x-table.td>
                </x-table.tr>
                @empty
                <x-table.empty colspan="7" icon="order" message="Sipariş bulunamadı">
                    <x-slot name="action">
                        <x-ui.button href="{{ route('siparis.create') }}" icon="plus" size="sm">
                            Yeni Sipariş Oluştur
                        </x-ui.button>
                    </x-slot>
                </x-table.empty>
                @endforelse
            </x-table.tbody>
        </x-table.table>
    </x-ui.card>

    {{-- Pagination --}}
    @if($orders->hasPages())
    <div class="mt-6">
        {{ $orders->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
function confirmDelete(orderId) {
    showConfirmDialog({
        title: 'Siparişi Sil?',
        message: 'Bu sipariş kalıcı olarak silinecektir. Bu işlem geri alınamaz.',
        confirmText: 'Evet, Sil',
        type: 'danger',
        onConfirm: async () => {
            document.getElementById('delete-form-' + orderId).submit();
        }
    });
}
</script>
@endpush
@endsection
