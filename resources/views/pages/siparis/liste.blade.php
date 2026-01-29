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
                    <x-table.th>Teslimat Kanıtı</x-table.th>
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
                        @if($order->status === 'delivered' && $order->hasPod())
                            <button type="button"
                                    onclick="showPodModal('{{ $order->getPodUrl() }}', '{{ $order->pod_timestamp?->format('d.m.Y H:i') }}', '{{ $order->pod_note ?? '' }}')"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/30 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Görüntüle
                            </button>
                        @elseif($order->status === 'delivered')
                            <span class="text-xs text-gray-400 italic">Kanıt yok</span>
                        @else
                            <span class="text-sm text-gray-400">-</span>
                        @endif
                    </x-table.td>
                    <x-table.td>
                        <x-data.date-time :date="$order->created_at" relative />
                    </x-table.td>
                    <x-table.td align="right" nowrap>
                        <div class="flex items-center justify-end gap-2">
                            @if($order->status === 'pending')
                            <x-ui.button type="button" variant="primary" size="sm" onclick="markAsReady({{ $order->id }})">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Hazır
                            </x-ui.button>
                            @endif
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
                <x-table.empty colspan="8" icon="order" message="Sipariş bulunamadı">
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

{{-- POD Modal --}}
<div id="pod-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4" onclick="closePodModal(event)">
    <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-lg w-full max-h-[90vh] overflow-hidden shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-black dark:text-white">Teslimat Kanıtı</h3>
            <button onclick="closePodModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-4">
            <img id="pod-image" src="" alt="Teslimat Kanıtı" class="w-full rounded-xl object-cover max-h-[50vh]">
            <div class="mt-4 space-y-2">
                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span id="pod-timestamp"></span>
                </div>
                <div id="pod-note-container" class="hidden">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">Not:</span>
                        <span id="pod-note"></span>
                    </p>
                </div>
            </div>
        </div>
    </div>
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

function markAsReady(orderId) {
    showConfirmDialog({
        title: 'Siparişi Hazır İşaretle?',
        message: 'Sipariş "Hazırlanıyor" durumuna alınacak ve hazırlanmaya başlandı olarak işaretlenecek.',
        confirmText: 'Evet, Hazır İşaretle',
        type: 'info',
        onConfirm: async () => {
            try {
                const response = await fetch(`/siparis/${orderId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ status: 'preparing' })
                });

                const data = await response.json();

                if (data.success) {
                    showToast('success', 'Sipariş hazırlanıyor olarak işaretlendi');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast('error', data.message || 'Bir hata oluştu');
                }
            } catch (error) {
                showToast('error', 'Bağlantı hatası');
            }
        }
    });
}

function showPodModal(imageUrl, timestamp, note) {
    const modal = document.getElementById('pod-modal');
    const image = document.getElementById('pod-image');
    const timestampEl = document.getElementById('pod-timestamp');
    const noteContainer = document.getElementById('pod-note-container');
    const noteEl = document.getElementById('pod-note');

    image.src = imageUrl;
    timestampEl.textContent = timestamp;

    if (note && note.trim() !== '') {
        noteEl.textContent = note;
        noteContainer.classList.remove('hidden');
    } else {
        noteContainer.classList.add('hidden');
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closePodModal(event) {
    if (event && event.target !== event.currentTarget) return;
    const modal = document.getElementById('pod-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

// ESC tuşu ile modal kapatma
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePodModal();
    }
});
</script>
@endpush
@endsection
