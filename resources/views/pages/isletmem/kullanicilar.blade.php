@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Kullanıcı Yönetimi"
        subtitle="İşletme kullanıcılarını yönetin"
    >
        <x-slot name="icon">
            <x-ui.icon name="users" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <x-ui.button icon="plus" onclick="showCreateModal()">
                Yeni Kullanıcı
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Mesajlar --}}
    @if(session('success'))
        <x-feedback.alert type="success" class="mb-6">
            {{ session('success') }}
        </x-feedback.alert>
    @endif
    @if(session('error'))
        <x-feedback.alert type="danger" class="mb-6">
            {{ session('error') }}
        </x-feedback.alert>
    @endif

    {{-- Kullanıcı Listesi --}}
    <x-ui.card>
        <x-table.table hoverable>
            <x-table.thead>
                <x-table.tr :hoverable="false">
                    <x-table.th>Ad Soyad</x-table.th>
                    <x-table.th>E-posta</x-table.th>
                    <x-table.th>Roller</x-table.th>
                    <x-table.th align="right">İşlemler</x-table.th>
                </x-table.tr>
            </x-table.thead>

            <x-table.tbody>
                @forelse($users as $user)
                <x-table.tr>
                    <x-table.td>
                        <span class="text-black dark:text-white font-medium">{{ $user->name }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-gray-600 dark:text-gray-400">{{ $user->email }}</span>
                    </x-table.td>
                    <x-table.td>
                        @foreach($user->roles ?? [] as $role)
                            <x-ui.badge type="default" size="sm">
                                {{ ucfirst($role) }}
                            </x-ui.badge>
                        @endforeach
                    </x-table.td>
                    <x-table.td align="right">
                        <div class="flex items-center justify-end gap-2">
                            <x-ui.button variant="ghost" size="sm" onclick="showEditModal({{ $user->id }}, '{{ $user->name }}', '{{ $user->email }}', {{ json_encode($user->roles ?? []) }})">
                                Düzenle
                            </x-ui.button>
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline"
                                onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm">
                                    Sil
                                </x-ui.button>
                            </form>
                        </div>
                    </x-table.td>
                </x-table.tr>
                @empty
                <x-table.empty colspan="4" icon="users" message="Kullanıcı bulunamadı" />
                @endforelse
            </x-table.tbody>
        </x-table.table>

        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $users->links() }}
        </div>
        @endif
    </x-ui.card>
</div>

{{-- Yeni Kullanıcı Modal --}}
<x-ui.modal name="createUserModal" title="Yeni Kullanıcı" size="md">
    <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
        @csrf
        <x-form.input name="name" label="Ad Soyad" required />
        <x-form.input type="email" name="email" label="E-posta" required />
        <x-form.input type="password" name="password" label="Şifre" required />
        <x-form.input type="password" name="password_confirmation" label="Şifre Tekrar" required />

        <x-form.form-group label="Roller">
            <div class="space-y-2">
                <x-form.checkbox name="roles[]" value="bayi" label="Bayi" />
                <x-form.checkbox name="roles[]" value="isletme" label="İşletme" />
            </div>
        </x-form.form-group>

        <div class="flex gap-3 pt-4">
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', 'createUserModal')" class="flex-1">
                İptal
            </x-ui.button>
            <x-ui.button type="submit" class="flex-1">
                Oluştur
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>

{{-- Düzenle Modal --}}
<x-ui.modal name="editUserModal" title="Kullanıcı Düzenle" size="md">
    <form id="editForm" method="POST" class="space-y-4">
        @csrf
        @method('PUT')
        <x-form.input name="name" id="edit_name" label="Ad Soyad" required />
        <x-form.input type="email" name="email" id="edit_email" label="E-posta" required />
        <x-form.input type="password" name="password" label="Şifre" hint="Boş bırakın değiştirmemek için" />
        <x-form.input type="password" name="password_confirmation" label="Şifre Tekrar" />

        <x-form.form-group label="Roller">
            <div class="space-y-2">
                <x-form.checkbox name="roles[]" value="bayi" id="edit_role_bayi" label="Bayi" />
                <x-form.checkbox name="roles[]" value="isletme" id="edit_role_isletme" label="İşletme" />
            </div>
        </x-form.form-group>

        <div class="flex gap-3 pt-4">
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', 'editUserModal')" class="flex-1">
                İptal
            </x-ui.button>
            <x-ui.button type="submit" class="flex-1">
                Güncelle
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>

@push('scripts')
<script>
function showCreateModal() {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'createUserModal' }));
}

function showEditModal(id, name, email, roles) {
    document.getElementById('editForm').action = `/isletmem/kullanicilar/${id}`;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role_bayi').checked = roles.includes('bayi');
    document.getElementById('edit_role_isletme').checked = roles.includes('isletme');
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'editUserModal' }));
}
</script>
@endpush
@endsection
