@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Kurye Yönetimi"
        subtitle="Kuryelerinizi yönetin ve performanslarını takip edin"
    >
        <x-slot name="icon">
            <x-ui.icon name="users" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <x-ui.button icon="plus" onclick="showCreateCourierModal()">
                Yeni Kurye
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Mesajlar --}}
    @if(session('success'))
        <x-feedback.alert type="success" class="mb-6">{{ session('success') }}</x-feedback.alert>
    @endif
    @if(session('error'))
        <x-feedback.alert type="danger" class="mb-6">{{ session('error') }}</x-feedback.alert>
    @endif

    {{-- Kurye İstatistikleri --}}
    <x-layout.grid cols="1" mdCols="4" gap="6" class="mb-6">
        <x-ui.stat-card
            title="Toplam Kurye"
            :value="$couriers->count()"
            icon="users"
            color="blue"
        />
        <x-ui.stat-card
            title="Aktif"
            :value="$couriers->where('status', 'active')->count()"
            icon="success"
            color="green"
        />
        <x-ui.stat-card
            title="Teslimat"
            :value="$couriers->where('status', 'delivering')->count()"
            icon="truck"
            color="orange"
        />
        <x-ui.stat-card
            title="Müsait"
            :value="$couriers->where('status', 'available')->count()"
            icon="check"
            color="purple"
        />
    </x-layout.grid>

    {{-- Kurye Listesi --}}
    <x-ui.card>
        <x-table.table hoverable>
            <x-table.thead>
                <x-table.tr :hoverable="false">
                    <x-table.th>Ad Soyad</x-table.th>
                    <x-table.th>Telefon</x-table.th>
                    <x-table.th>Durum</x-table.th>
                    <x-table.th>Günlük Teslimat</x-table.th>
                    <x-table.th>Puan</x-table.th>
                    <x-table.th align="right">İşlemler</x-table.th>
                </x-table.tr>
            </x-table.thead>

            <x-table.tbody>
                @forelse($couriers as $courier)
                <x-table.tr>
                    <x-table.td>
                        <span class="text-black dark:text-white">{{ $courier->name }}</span>
                    </x-table.td>
                    <x-table.td>
                        <x-data.phone :number="$courier->phone" />
                    </x-table.td>
                    <x-table.td>
                        <x-data.status-badge :status="$courier->status" entity="courier" />
                    </x-table.td>
                    <x-table.td>
                        <span class="text-black dark:text-white">{{ $courier->orders()->whereDate('created_at', today())->count() }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-black dark:text-white">-</span>
                    </x-table.td>
                    <x-table.td align="right">
                        <div class="flex items-center justify-end gap-2">
                            <x-ui.button variant="ghost" size="sm"
                                onclick="showEditCourierModal({{ $courier->id }}, '{{ $courier->name }}', '{{ $courier->phone }}', '{{ $courier->email }}', '{{ $courier->status }}')">
                                Düzenle
                            </x-ui.button>
                            <form action="{{ route('couriers.destroy', $courier) }}" method="POST" class="inline"
                                onsubmit="return confirm('Bu kuryeyi silmek istediğinize emin misiniz?')">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm">Sil</x-ui.button>
                            </form>
                        </div>
                    </x-table.td>
                </x-table.tr>
                @empty
                <x-table.empty colspan="6" icon="users" message="Kurye bulunamadı" />
                @endforelse
            </x-table.tbody>
        </x-table.table>
    </x-ui.card>
</div>

{{-- Kurye Oluştur Modal --}}
<x-ui.modal name="createCourierModal" title="Yeni Kurye" size="md">
    <form action="{{ route('couriers.store') }}" method="POST" class="space-y-4">
        @csrf
        <x-form.input name="name" label="Ad Soyad" required />
        <x-form.input name="phone" label="Telefon" required />
        <x-form.input type="email" name="email" label="E-posta" />
        <x-form.select name="status" label="Durum" required :options="[
            'available' => 'Müsait',
            'active' => 'Aktif',
            'delivering' => 'Teslimat',
            'offline' => 'Çevrimdışı',
        ]" />

        <div class="flex gap-3 pt-4">
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', 'createCourierModal')" class="flex-1">İptal</x-ui.button>
            <x-ui.button type="submit" class="flex-1">Oluştur</x-ui.button>
        </div>
    </form>
</x-ui.modal>

{{-- Kurye Düzenle Modal --}}
<x-ui.modal name="editCourierModal" title="Kurye Düzenle" size="md">
    <form id="editCourierForm" method="POST" class="space-y-4">
        @csrf
        @method('PUT')
        <x-form.input name="name" id="edit_courier_name" label="Ad Soyad" required />
        <x-form.input name="phone" id="edit_courier_phone" label="Telefon" required />
        <x-form.input type="email" name="email" id="edit_courier_email" label="E-posta" />
        <x-form.select name="status" id="edit_courier_status" label="Durum" required :options="[
            'available' => 'Müsait',
            'active' => 'Aktif',
            'delivering' => 'Teslimat',
            'offline' => 'Çevrimdışı',
        ]" />

        <div class="flex gap-3 pt-4">
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', 'editCourierModal')" class="flex-1">İptal</x-ui.button>
            <x-ui.button type="submit" class="flex-1">Güncelle</x-ui.button>
        </div>
    </form>
</x-ui.modal>

@push('scripts')
<script>
function showCreateCourierModal() {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'createCourierModal' }));
}

function showEditCourierModal(id, name, phone, email, status) {
    document.getElementById('editCourierForm').action = `/isletmem/kuryeler/${id}`;
    document.getElementById('edit_courier_name').value = name;
    document.getElementById('edit_courier_phone').value = phone;
    document.getElementById('edit_courier_email').value = email || '';
    document.getElementById('edit_courier_status').value = status;
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'editCourierModal' }));
}
</script>
@endpush
@endsection
