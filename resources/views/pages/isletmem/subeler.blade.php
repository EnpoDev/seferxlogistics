@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Şube Yönetimi"
        subtitle="İşletme şubelerinizi yönetin"
    >
        <x-slot name="icon">
            <x-ui.icon name="business" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <x-ui.button icon="plus" onclick="showCreateModal()">
                Yeni Şube
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Şube Kartları --}}
    <x-layout.grid cols="1" mdCols="2" lgCols="3" gap="6">
        @forelse($branches ?? [] as $branch)
        <x-ui.card>
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-black dark:text-white">{{ $branch->name }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $branch->is_main ? 'Ana Şube' : 'Şube' }}
                    </p>
                </div>
                <x-data.status-badge :status="$branch->is_active ? 'active' : 'inactive'" entity="branch" />
            </div>

            <div class="space-y-2 text-sm">
                @if($branch->address)
                <p class="text-gray-600 dark:text-gray-400 flex items-center gap-2">
                    <x-ui.icon name="location" class="w-4 h-4" />
                    {{ $branch->address }}
                </p>
                @endif
                @if($branch->phone)
                <p class="text-gray-600 dark:text-gray-400 flex items-center gap-2">
                    <x-ui.icon name="phone" class="w-4 h-4" />
                    {{ $branch->phone }}
                </p>
                @endif
            </div>

            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-800 flex gap-2">
                <x-ui.button variant="ghost" size="sm" href="{{ route('isletmem.subeler.edit', $branch) }}">
                    Düzenle
                </x-ui.button>
            </div>
        </x-ui.card>
        @empty
        <div class="col-span-full">
            <x-ui.empty-state
                title="Şube bulunamadı"
                description="Yeni bir şube ekleyerek başlayın"
                actionText="Şube Ekle"
                icon="business"
            />
        </div>
        @endforelse
    </x-layout.grid>
</div>

{{-- Yeni Şube Modal --}}
<x-ui.modal name="createBranchModal" title="Yeni Şube" size="md">
    <form action="{{ route('isletmem.subeler.store') }}" method="POST" class="space-y-4">
        @csrf
        <x-form.input name="name" label="Şube Adı" required />
        <x-form.input name="phone" label="Telefon" />
        <x-form.textarea name="address" label="Adres" :rows="2" />

        <div class="flex gap-3 pt-4">
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', 'createBranchModal')" class="flex-1">
                İptal
            </x-ui.button>
            <x-ui.button type="submit" class="flex-1">
                Oluştur
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>

@push('scripts')
<script>
function showCreateModal() {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'createBranchModal' }));
}
</script>
@endpush
@endsection
