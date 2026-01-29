@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn" x-data="{ showCreateModal: false, showEditModal: false, editingBranch: null }">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Şubeler</h1>
            <p class="text-gray-600 dark:text-gray-400">Tüm şubeleri yönetin</p>
        </div>
        <button @click="showCreateModal = true" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Yeni Şube</span>
        </button>
    </div>

    @if(session('success'))<div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg"><p class="text-green-700 dark:text-green-400">{{ session('success') }}</p></div>@endif
    @if(session('error'))<div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-700 dark:text-red-400">{{ session('error') }}</p></div>@endif

    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-black">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Şube</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Adres</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($subeler as $sube)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                        <td class="px-6 py-4">
                            <p class="font-medium text-black dark:text-white">{{ $sube->name }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $sube->phone ?? '-' }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ Str::limit($sube->address, 50) ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded text-xs {{ $sube->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                {{ $sube->is_active ? 'Aktif' : 'Pasif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <button @click="editingBranch = {{ $sube->toJson() }}; showEditModal = true" class="p-2 text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <form action="{{ route('admin.subeler.destroy', $sube) }}" method="POST" class="inline" onsubmit="return confirm('Bu şubeyi silmek istediğinize emin misiniz?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-red-600 dark:text-red-400 hover:text-red-800 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">Şube bulunamadı</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($subeler->hasPages())<div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">{{ $subeler->links() }}</div>@endif
    </div>

    <!-- Create Modal -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showCreateModal = false"></div>
            <div class="relative bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Yeni Şube</h3>
                <form action="{{ route('admin.subeler.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şube Adı</label><input type="text" name="name" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adres</label><textarea name="address" rows="2" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></textarea></div>
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefon</label><input type="text" name="phone" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-posta</label><input type="email" name="email" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showCreateModal = false" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg">İptal</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg">Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showEditModal = false"></div>
            <div class="relative bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Şube Düzenle</h3>
                <form :action="'/admin/subeler/' + (editingBranch?.id || '')" method="POST" class="space-y-4">
                    @csrf @method('PUT')
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şube Adı</label><input type="text" name="name" x-model="editingBranch.name" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adres</label><textarea name="address" rows="2" x-model="editingBranch.address" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></textarea></div>
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefon</label><input type="text" name="phone" x-model="editingBranch.phone" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                    <div><label class="flex items-center"><input type="checkbox" name="is_active" value="1" :checked="editingBranch?.is_active" class="rounded border-gray-300"><span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktif</span></label></div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showEditModal = false" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg">İptal</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
