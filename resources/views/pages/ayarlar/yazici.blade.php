@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn" x-data="{ showAddPrinter: false, editPrinter: null }">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Yazıcı Yönetimi"
        subtitle="Yazıcı ayarlarınızı yapılandırın"
    >
        <x-slot name="icon">
            <x-ui.icon name="printer" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <x-ui.button @click="showAddPrinter = true" icon="plus">Yazıcı Ekle</x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Mesajlar --}}
    @if(session('success'))
        <x-feedback.alert type="success" class="mb-6">{{ session('success') }}</x-feedback.alert>
    @endif

    <div class="max-w-2xl">
        {{-- Yazıcı Listesi --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Tanımlı Yazıcılar</h3>
            <div class="space-y-3">
                @forelse($printers as $printer)
                <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-800 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-black dark:text-white">{{ $printer->name }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            {{ $printer->model ?? $printer->getTypeLabel() }}
                            @if($printer->connection_type === 'network')
                                ({{ $printer->getConnectionString() }})
                            @else
                                ({{ $printer->getConnectionLabel() }})
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center space-x-3">
                        @if($printer->is_active)
                            <x-ui.badge type="success" size="sm">Aktif</x-ui.badge>
                        @else
                            <x-ui.badge type="default" size="sm">Pasif</x-ui.badge>
                        @endif
                        <x-ui.button variant="ghost" size="sm" onclick="testPrinter({{ $printer->id }})">Test</x-ui.button>
                        <x-ui.button variant="ghost" size="sm" @click="editPrinter = {{ $printer->toJson() }}">Düzenle</x-ui.button>
                        <form action="{{ route('ayarlar.yazici.destroy', $printer) }}" method="POST" class="inline"
                            onsubmit="return confirm('Bu yazıcıyı silmek istediğinizden emin misiniz?')">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="ghost" size="sm" class="text-red-600 dark:text-red-400">Sil</x-ui.button>
                        </form>
                    </div>
                </div>
                @empty
                <x-ui.empty-state
                    title="Yazıcı bulunamadı"
                    description="Henüz yazıcı eklenmemiş"
                    icon="printer"
                    actionText="İlk Yazıcınızı Ekleyin"
                    actionUrl="#"
                    @click="showAddPrinter = true"
                />
                @endforelse
            </div>
        </x-ui.card>
    </div>

    {{-- Yazıcı Ekleme Modal --}}
    <x-ui.modal name="addPrinterModal" title="Yeni Yazıcı Ekle" size="lg">
        <form action="{{ route('ayarlar.yazici.store') }}" method="POST" class="space-y-4">
            @csrf
            <x-form.input name="name" label="Yazıcı Adı" placeholder="Mutfak Yazıcısı" required />

            <x-form.select name="type" label="Yazıcı Tipi" required :options="[
                'kitchen' => 'Mutfak Yazıcısı',
                'receipt' => 'Fiş Yazıcısı',
                'label' => 'Etiket Yazıcısı',
            ]" />

            <x-form.select name="connection_type" label="Bağlantı Türü" required :options="[
                'network' => 'Ağ (Ethernet)',
                'usb' => 'USB',
                'bluetooth' => 'Bluetooth',
            ]" />

            <x-layout.grid cols="2" gap="4">
                <x-form.input name="ip_address" label="IP Adresi" placeholder="192.168.1.100" />
                <x-form.input type="number" name="port" label="Port" placeholder="9100" />
            </x-layout.grid>

            <x-form.input name="model" label="Model" placeholder="Epson TM-T88V" />

            <x-layout.grid cols="2" gap="4">
                <x-form.select name="paper_width" label="Kağıt Genişliği" :options="[
                    '80mm' => '80mm',
                    '58mm' => '58mm',
                ]" />
                <x-form.input type="number" name="copies" label="Kopya Sayısı" value="1" min="1" max="10" />
            </x-layout.grid>

            <div class="flex items-center space-x-4">
                <x-form.checkbox name="auto_print" label="Otomatik Yazdır" value="1" checked />
                <x-form.checkbox name="print_on_new_order" label="Yeni Siparişte Yazdır" value="1" checked />
            </div>

            <div class="flex gap-3 pt-4">
                <x-ui.button type="button" variant="secondary" @click="showAddPrinter = false" class="flex-1">İptal</x-ui.button>
                <x-ui.button type="submit" class="flex-1">Kaydet</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>

@push('scripts')
<script>
// Manual modal control for showAddPrinter
document.addEventListener('alpine:init', () => {
    const el = document.querySelector('[x-data]');
    if (el && el._x_dataStack) {
        Alpine.effect(() => {
            const data = Alpine.$data(el);
            if (data.showAddPrinter) {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'addPrinterModal' }));
            }
        });
    }
});

function testPrinter(printerId) {
    fetch(`/ayarlar/yazici/${printerId}/test`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        window.showToast(data.message, data.success ? 'success' : 'error');
    })
    .catch(error => {
        window.showToast('Bağlantı testi başarısız oldu.', 'error');
    });
}
</script>
@endpush
@endsection
