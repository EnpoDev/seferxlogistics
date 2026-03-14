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
                <x-table.tr data-order-id="{{ $order->id }}" data-order-status="{{ $order->status }}">
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
                        <span class="{{ in_array($order->status, ['preparing', 'pending']) ? 'animate-pulse' : '' }}">
                            <x-data.status-badge :status="$order->status" entity="order" />
                        </span>
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

@push('styles')
<style>
    /* Dynamic status badge styles for real-time inserted rows */
    .status-badge-pending { background-color: rgb(254, 243, 199); color: rgb(146, 64, 14); }
    .dark .status-badge-pending { background-color: rgba(245, 158, 11, 0.2); color: rgb(252, 211, 77); }
    .status-badge-preparing { background-color: rgb(219, 234, 254); color: rgb(30, 64, 175); }
    .dark .status-badge-preparing { background-color: rgba(59, 130, 246, 0.2); color: rgb(147, 197, 253); }
    .status-badge-ready { background-color: rgb(220, 252, 231); color: rgb(22, 101, 52); }
    .dark .status-badge-ready { background-color: rgba(34, 197, 94, 0.2); color: rgb(134, 239, 172); }
    .status-badge-on_delivery { background-color: rgb(207, 250, 254); color: rgb(21, 94, 117); }
    .dark .status-badge-on_delivery { background-color: rgba(6, 182, 212, 0.2); color: rgb(103, 232, 249); }
    .status-badge-delivered { background-color: rgb(220, 252, 231); color: rgb(22, 101, 52); }
    .dark .status-badge-delivered { background-color: rgba(34, 197, 94, 0.2); color: rgb(134, 239, 172); }
    .status-badge-cancelled { background-color: rgb(254, 226, 226); color: rgb(153, 27, 27); }
    .dark .status-badge-cancelled { background-color: rgba(239, 68, 68, 0.2); color: rgb(252, 165, 165); }
</style>
@endpush

@push('scripts')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
// =============================================
// Real-time Order Updates via Pusher
// =============================================
(function() {
    const pusher = new Pusher('{{ config("broadcasting.connections.pusher.key") }}', {
        cluster: '{{ config("broadcasting.connections.pusher.options.cluster") }}',
        forceTLS: true
    });

    const channel = pusher.subscribe('orders');
    let knownOrderIds = new Set();

    // Collect existing order IDs
    document.querySelectorAll('[data-order-id]').forEach(row => {
        knownOrderIds.add(parseInt(row.dataset.orderId));
    });

    // New order created
    channel.bind('order.created', function(data) {
        if (knownOrderIds.has(data.id)) return;
        knownOrderIds.add(data.id);

        // Play notification sound
        playNotificationSound();

        // Show toast
        if (typeof showToast === 'function') {
            showToast('Yeni siparis: #' + data.order_number + ' - ' + data.customer_name, 'info');
        }

        // Add row to table (prepend to tbody)
        const tbody = document.querySelector('table tbody');
        if (tbody) {
            const newRow = createOrderRow(data);
            const firstRow = tbody.querySelector('tr');
            if (firstRow) {
                tbody.insertBefore(newRow, firstRow);
            } else {
                tbody.appendChild(newRow);
            }
            // Flash animation
            newRow.classList.add('animate-pulse', 'bg-green-50', 'dark:bg-green-900/10');
            setTimeout(() => {
                newRow.classList.remove('animate-pulse', 'bg-green-50', 'dark:bg-green-900/10');
            }, 5000);
        }
    });

    // Order status updated
    channel.bind('order.status.updated', function(data) {
        const row = document.querySelector(`[data-order-id="${data.id}"]`);
        if (!row) return;

        // Update status attribute
        row.dataset.orderStatus = data.new_status;

        // Find and update status badge cell (4th td)
        const cells = row.querySelectorAll('td');
        if (cells[3]) {
            const isPulsing = ['preparing', 'pending'].includes(data.new_status);
            cells[3].innerHTML = `<span class="${isPulsing ? 'animate-pulse' : ''}"><span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full status-badge-${data.new_status}">${data.status_label}</span></span>`;
        }

        // Update courier cell (5th td) if courier info changed
        if (data.courier && cells[4]) {
            cells[4].innerHTML = `<span class="text-sm text-black dark:text-white">${data.courier.name}</span>`;
        }

        // Flash row to indicate update
        row.classList.add('bg-blue-50', 'dark:bg-blue-900/10');
        setTimeout(() => {
            row.classList.remove('bg-blue-50', 'dark:bg-blue-900/10');
        }, 3000);

        // Play sound for important status changes
        if (['delivered', 'cancelled'].includes(data.new_status)) {
            playNotificationSound();
        }
    });

    // Connection status logging
    pusher.connection.bind('connected', () => console.log('Pusher connected (orders)'));
    pusher.connection.bind('error', (err) => console.error('Pusher error:', err));

    function createOrderRow(data) {
        const tr = document.createElement('tr');
        tr.dataset.orderId = data.id;
        tr.dataset.orderStatus = data.status;
        tr.className = 'border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors';

        const timeAgo = getTimeAgo(new Date(data.created_at));
        const courierHtml = data.courier ? `<span class="text-sm text-black dark:text-white">${data.courier.name}</span>` : '<span class="text-sm text-gray-400">-</span>';

        tr.innerHTML = `
            <td class="px-4 py-3"><span class="text-sm font-mono font-semibold text-black dark:text-white">${data.order_number}</span></td>
            <td class="px-4 py-3"><div><p class="text-sm font-medium text-black dark:text-white">${escapeHtml(data.customer_name)}</p><span class="text-xs text-gray-500">${data.customer_phone}</span></div></td>
            <td class="px-4 py-3"><span class="font-bold text-black dark:text-white">${parseFloat(data.total).toFixed(2)} TL</span></td>
            <td class="px-4 py-3"><span class="animate-pulse"><span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full status-badge-${data.status}">${data.status_label}</span></span></td>
            <td class="px-4 py-3">${courierHtml}</td>
            <td class="px-4 py-3"><span class="text-sm text-gray-400">-</span></td>
            <td class="px-4 py-3"><span class="text-sm text-gray-500">${timeAgo}</span></td>
            <td class="px-4 py-3 text-right"><a href="/siparis/${data.id}/edit" class="text-sm text-gray-500 hover:text-black dark:hover:text-white">Duzenle</a></td>
        `;
        return tr;
    }

    function getTimeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        if (seconds < 60) return 'Az once';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return minutes + ' dk once';
        const hours = Math.floor(minutes / 60);
        return hours + ' saat once';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();

// =============================================
// Sound Notification
// =============================================
let notificationSoundEnabled = false;

function playNotificationSound() {
    if (!notificationSoundEnabled) return;
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = ctx.createOscillator();
        const gainNode = ctx.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(ctx.destination);
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        gainNode.gain.setValueAtTime(0.3, ctx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
        oscillator.start(ctx.currentTime);
        oscillator.stop(ctx.currentTime + 0.5);
    } catch (e) {
        console.log('Sound notification failed:', e);
    }
}

// Request notification permission and enable sound on first interaction
document.addEventListener('click', function enableSound() {
    notificationSoundEnabled = true;
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
    document.removeEventListener('click', enableSound);
}, { once: true });

// =============================================
// Filter Persistence (URL query string)
// =============================================
(function() {
    const form = document.querySelector('form[action*="siparis"]');
    if (!form) return;

    // On filter change, update URL without page reload (for select/date inputs)
    form.querySelectorAll('select, input[type="date"]').forEach(input => {
        input.addEventListener('change', function() {
            // Auto-submit on filter change
            form.submit();
        });
    });
})();

// =============================================
// Existing Functions
// =============================================
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

// ESC tusu ile modal kapatma
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePodModal();
    }
});

// Print mode handling after order creation
(function() {
    const printMode = @json(session('print_mode'));
    const printOrderId = @json(session('print_order_id'));

    if (!printOrderId || !printMode) return;

    if (printMode === 'auto') {
        // Auto print: open print dialog for the order
        const printUrl = '/siparis/' + printOrderId + '/print';
        const printWindow = window.open(printUrl, '_blank', 'width=400,height=600');
        if (printWindow) {
            printWindow.addEventListener('load', function() {
                printWindow.print();
            });
        }
    } else if (printMode === 'manual') {
        // Manual print: show print button in success toast
        const toastEl = document.querySelector('.toast-success, [data-toast="success"]');
        if (toastEl) {
            const printBtn = document.createElement('a');
            printBtn.href = '/siparis/' + printOrderId + '/print';
            printBtn.target = '_blank';
            printBtn.className = 'inline-block mt-2 px-3 py-1 bg-white text-black rounded text-sm font-medium';
            printBtn.textContent = 'Yazdir';
            printBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const pw = window.open(printBtn.href, '_blank', 'width=400,height=600');
                if (pw) pw.addEventListener('load', function() { pw.print(); });
            });
            toastEl.appendChild(printBtn);
        }
    }
    // printMode === 'none' -> do nothing
})();
</script>
@endpush
@endsection
