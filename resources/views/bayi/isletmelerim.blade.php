@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="İşletmelerim"
        subtitle="İşletmelerinizi yönetin ve takip edin"
    >
        <x-slot name="icon">
            <x-ui.icon name="building" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <form action="{{ route('bayi.isletmelerim') }}" method="GET">
                <x-form.search-input
                    name="search"
                    placeholder="İşletme ara..."
                    :value="request('search')"
                    :autoSubmit="true"
                />
            </form>

            <x-ui.button href="{{ route('bayi.isletme-ekle') }}" icon="plus">
                İşletme Ekle
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- İstatistik Kartları --}}
    <x-layout.grid cols="1" mdCols="2" gap="4" class="mb-6">
        <x-ui.stat-card title="Toplam İşletme" :value="$branches->count()" color="blue" icon="building" />
        <x-ui.stat-card title="Aktif İşletme" :value="$branches->where('is_active', true)->count()" color="green" icon="success" />
    </x-layout.grid>

    {{-- Şube Listesi --}}
    <x-table.table hoverable>
        <x-table.thead>
            <x-table.tr :hoverable="false">
                <x-table.th>İşletme Adı</x-table.th>
                <x-table.th>İletişim</x-table.th>
                <x-table.th>Durum</x-table.th>
                <x-table.th align="right">İşlemler</x-table.th>
            </x-table.tr>
        </x-table.thead>

        <x-table.tbody>
            @forelse($branches as $branch)
                <x-table.tr class="cursor-pointer" onclick="window.location='{{ route('bayi.isletme-detay', $branch->id) }}'">
                    {{-- İşletme Adı --}}
                    <x-table.td>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold overflow-hidden">
                                {{ substr($branch->name, 0, 2) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-black dark:text-white">{{ $branch->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">ID: #{{ $branch->id }}</p>
                            </div>
                        </div>
                    </x-table.td>

                    {{-- İletişim --}}
                    <x-table.td>
                        <div>
                            <p class="text-sm text-black dark:text-white flex items-center gap-1.5">
                                <x-ui.icon name="phone" class="w-4 h-4 text-gray-400" />
                                <x-data.phone :number="$branch->phone" :clickable="false" />
                            </p>
                            @if($branch->email)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1.5">
                                <x-ui.icon name="mail" class="w-3.5 h-3.5" />
                                {{ $branch->email }}
                            </p>
                            @endif
                        </div>
                    </x-table.td>

                    {{-- Durum --}}
                    <x-table.td>
                        <x-data.status-badge :status="$branch->is_active ? 'active' : 'inactive'" entity="branch" />
                    </x-table.td>

                    {{-- İşlemler --}}
                    <x-table.td align="right" nowrap onclick="event.stopPropagation()">
                        <div class="flex items-center justify-end gap-2">
                            <form action="{{ route('bayi.isletme.giris', $branch->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium bg-black dark:bg-white text-white dark:text-black hover:bg-gray-800 dark:hover:bg-gray-200 rounded-lg transition-colors">
                                    <x-ui.icon name="login" class="w-4 h-4" />
                                    Geçiş Yap
                                </button>
                            </form>
                            <a href="{{ route('bayi.isletme-duzenle', $branch->id) }}"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                                <x-ui.icon name="edit" class="w-4 h-4" />
                                Düzenle
                            </a>
                            <button type="button"
                                onclick="openDeleteModal({{ $branch->id }}, '{{ addslashes($branch->name) }}')"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                <x-ui.icon name="trash" class="w-4 h-4" />
                            </button>
                        </div>
                    </x-table.td>
                </x-table.tr>
            @empty
                <x-table.empty
                    colspan="4"
                    icon="building"
                    message="Henüz işletme eklenmemiş"
                >
                    <x-slot name="action">
                        <x-ui.button href="{{ route('bayi.isletme-ekle') }}" icon="plus" size="sm">
                            İşletme Ekle
                        </x-ui.button>
                    </x-slot>
                </x-table.empty>
            @endforelse
        </x-table.tbody>
    </x-table.table>
</div>

{{-- Silme Onay Modalı --}}
<div id="deleteModal" class="fixed inset-0 z-50 hidden">
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeDeleteModal()"></div>

    {{-- Modal Content --}}
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-md w-full p-6 relative">
            {{-- Close Button --}}
            <button type="button" onclick="closeDeleteModal()"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <x-ui.icon name="x" class="w-5 h-5" />
            </button>

            {{-- Icon --}}
            <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mx-auto mb-4">
                <x-ui.icon name="warning" class="w-6 h-6 text-red-600 dark:text-red-400" />
            </div>

            {{-- Title --}}
            <h3 class="text-lg font-semibold text-center text-black dark:text-white mb-2">
                İşletmeyi Sil
            </h3>

            {{-- Description --}}
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-4">
                <span class="font-medium text-black dark:text-white" id="deleteBranchName"></span> işletmesini silmek üzeresiniz.
                Bu işlem geri alınamaz ve tüm veriler silinecektir.
            </p>

            {{-- Confirmation Input --}}
            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Onaylamak için işletme adını yazın
                    </label>
                    <input type="text"
                        id="confirmName"
                        name="confirm_name"
                        autocomplete="off"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-black dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                        placeholder="İşletme adını yazın..."
                        oninput="validateDeleteInput()"
                    />
                </div>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button type="button" onclick="closeDeleteModal()"
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        İptal
                    </button>
                    <button type="submit"
                        id="deleteButton"
                        disabled
                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Kalıcı Olarak Sil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let currentBranchId = null;
    let currentBranchName = '';

    function openDeleteModal(branchId, branchName) {
        currentBranchId = branchId;
        currentBranchName = branchName;

        document.getElementById('deleteBranchName').textContent = branchName;
        document.getElementById('deleteForm').action = '{{ url("bayi/isletmelerim") }}/' + branchId;
        document.getElementById('confirmName').value = '';
        document.getElementById('deleteButton').disabled = true;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        currentBranchId = null;
        currentBranchName = '';
    }

    function validateDeleteInput() {
        const input = document.getElementById('confirmName').value;
        const deleteBtn = document.getElementById('deleteButton');
        deleteBtn.disabled = input !== currentBranchName;
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    });
</script>
@endsection
