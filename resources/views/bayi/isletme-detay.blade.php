@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Header --}}
    <x-layout.page-header :backUrl="route('bayi.isletmelerim')">
        <h1 class="text-2xl font-bold text-black dark:text-white">{{ $branch->name }}</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">İşletme detayları</p>

        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('bayi.isletme-duzenle', $branch->id)">
                Düzenle
            </x-ui.button>
            <x-ui.button variant="danger" onclick="openDeleteModal()">
                Sil
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- İşletme Bilgileri --}}
    <x-layout.grid cols="1" mdCols="3" gap="6">
        {{-- İletişim Kartı --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">İletişim Bilgileri</h3>
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600 dark:text-blue-400">
                        <x-ui.icon name="phone" class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Telefon</p>
                        <p class="text-base font-medium text-black dark:text-white">
                            <x-data.phone :number="$branch->phone" />
                        </p>
                    </div>
                </div>

                @if($branch->email)
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-purple-600 dark:text-purple-400">
                        <x-ui.icon name="mail" class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">E-posta</p>
                        <p class="text-base font-medium text-black dark:text-white">{{ $branch->email }}</p>
                    </div>
                </div>
                @endif
            </div>
        </x-ui.card>

        {{-- Adres Kartı --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Adres Bilgisi</h3>
            <div class="flex items-start gap-3">
                <div class="p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg text-orange-600 dark:text-orange-400">
                    <x-ui.icon name="location" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-base text-black dark:text-white">{{ $branch->address }}</p>
                    @if($branch->lat && $branch->lng)
                        <a href="https://maps.google.com/?q={{ $branch->lat }},{{ $branch->lng }}" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">Haritada Göster &rarr;</a>
                    @endif
                </div>
            </div>
        </x-ui.card>

        {{-- Durum Kartı --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Durum</h3>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded-lg text-green-600 dark:text-green-400">
                    <x-ui.icon name="success" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">İşletme Durumu</p>
                    <x-data.status-badge :status="$branch->is_active ? 'active' : 'inactive'" entity="branch" />
                </div>
            </div>
        </x-ui.card>
    </x-layout.grid>
</div>

{{-- Silme Onay Modalı --}}
<div id="deleteModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeDeleteModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 relative">
            <button onclick="closeDeleteModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <x-ui.icon name="close" class="w-5 h-5" />
            </button>

            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <x-ui.icon name="warning" class="w-8 h-8 text-red-600 dark:text-red-400" />
                </div>
                <h3 class="text-xl font-bold text-black dark:text-white">İşletmeyi Sil</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    Bu işlem geri alınamaz. İşletmeyi silmek için aşağıya işletme adını yazın:
                </p>
                <p class="text-base font-bold text-red-600 dark:text-red-400 mt-2">{{ $branch->name }}</p>
            </div>

            <form action="{{ route('bayi.isletme-sil', $branch->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="mb-4">
                    <input
                        type="text"
                        id="confirmName"
                        name="confirm_name"
                        placeholder="İşletme adını yazın..."
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-black dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                        oninput="checkDeleteConfirmation()"
                        autocomplete="off"
                    >
                </div>
                <div class="flex gap-3">
                    <button
                        type="button"
                        onclick="closeDeleteModal()"
                        class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        İptal
                    </button>
                    <button
                        type="submit"
                        id="deleteButton"
                        disabled
                        class="flex-1 px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Kalıcı Olarak Sil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const branchName = @json($branch->name);

    function openDeleteModal() {
        document.getElementById('deleteModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.body.style.overflow = '';
        document.getElementById('confirmName').value = '';
        document.getElementById('deleteButton').disabled = true;
    }

    function checkDeleteConfirmation() {
        const input = document.getElementById('confirmName').value;
        const button = document.getElementById('deleteButton');
        button.disabled = input !== branchName;
    }

    // ESC tuşu ile kapatma
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    });
</script>
@endsection
