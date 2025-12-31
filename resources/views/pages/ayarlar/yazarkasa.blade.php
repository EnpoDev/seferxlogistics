@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Yazarkasa Ayarları"
        subtitle="Mali entegrasyon ayarlarını yapılandırın"
    >
        <x-slot name="icon">
            <x-ui.icon name="calculator" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>
    </x-layout.page-header>

    {{-- Mesajlar --}}
    @if(session('success'))
        <x-feedback.alert type="success" class="mb-6">{{ session('success') }}</x-feedback.alert>
    @endif

    <form action="{{ route('ayarlar.yazarkasa.update') }}" method="POST">
        @csrf
        <div class="max-w-2xl space-y-6">
            {{-- Yazarkasa Entegrasyonu --}}
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Yazarkasa Bağlantısı</h3>
                <div class="space-y-4">
                    <x-form.toggle
                        name="is_enabled"
                        label="Yazarkasa Entegrasyonu"
                        description="Siparişleri otomatik yazarkasaya aktar"
                        :checked="$settings->is_enabled"
                    />
                    <x-form.toggle
                        name="auto_send_orders"
                        label="Otomatik Sipariş Gönderimi"
                        description="Sipariş tamamlandığında otomatik olarak yazarkasaya gönder"
                        :checked="$settings->auto_send_orders"
                    />
                </div>
            </x-ui.card>

            {{-- Cihaz Ayarları --}}
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Cihaz Ayarları</h3>
                <div class="space-y-4">
                    <x-form.select name="model" label="Yazarkasa Modeli" :options="[
                        '' => 'Seçiniz...',
                        'hugin' => 'Hugin',
                        'olivetti' => 'Olivetti',
                        'ingenico' => 'Ingenico',
                        'custom' => 'Diğer',
                    ]" :selected="$settings->model" />

                    <x-form.select name="connection_type" label="Bağlantı Türü" :options="[
                        'serial' => 'Seri Port (COM)',
                        'ethernet' => 'Ethernet',
                        'usb' => 'USB',
                    ]" :selected="$settings->connection_type" />

                    <x-layout.grid cols="2" gap="4">
                        <x-form.input
                            name="port"
                            label="Port / IP Adresi"
                            :value="$settings->port"
                            placeholder="COM1 veya 192.168.1.50"
                        />
                        <x-form.select name="baud_rate" label="Baud Rate" :options="[
                            '' => 'Varsayılan',
                            '9600' => '9600',
                            '19200' => '19200',
                            '38400' => '38400',
                            '57600' => '57600',
                            '115200' => '115200',
                        ]" :selected="(string)$settings->baud_rate" />
                    </x-layout.grid>
                </div>
            </x-ui.card>

            {{-- KDV Ayarları --}}
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">KDV Ayarları</h3>
                <x-form.select name="default_vat_rate" label="Varsayılan KDV Oranı" :options="[
                    '20' => '%20',
                    '18' => '%18',
                    '10' => '%10',
                    '8' => '%8',
                    '1' => '%1',
                ]" :selected="(string)$settings->default_vat_rate" />
            </x-ui.card>

            <div class="flex gap-3">
                <x-ui.button type="button" variant="secondary" onclick="testCashRegister()" class="flex-1">
                    Bağlantıyı Test Et
                </x-ui.button>
                <x-ui.button type="submit" class="flex-1">Kaydet</x-ui.button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function testCashRegister() {
    fetch('{{ route("ayarlar.yazarkasa.test") }}', {
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
