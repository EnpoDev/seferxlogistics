@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn" x-data="{ showCreateModal: false, showEditModal: false, editingUser: null }">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Kullanıcılar</h1>
            <p class="text-gray-600 dark:text-gray-400">Tüm sistem kullanıcılarını yönetin</p>
        </div>
        <button @click="showCreateModal = true" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Yeni Kullanıcı</span>
        </button>
    </div>

    @if(session('success'))
    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
        <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <p class="text-red-700 dark:text-red-400">{{ session('error') }}</p>
    </div>
    @endif

    <!-- Filters -->
    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-4 mb-6">
        <form action="{{ route('admin.kullanicilar.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Kullanıcı ara..."
                    class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
            </div>
            <div>
                <select name="role" class="px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    <option value="">Tüm Roller</option>
                    <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="bayi" {{ request('role') === 'bayi' ? 'selected' : '' }}>Bayi</option>
                    <option value="isletme" {{ request('role') === 'isletme' ? 'selected' : '' }}>İşletme</option>
                    <option value="kurye" {{ request('role') === 'kurye' ? 'selected' : '' }}>Kurye</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                Filtrele
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-black">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kullanıcı</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Roller</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kayıt Tarihi</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($kullanicilar as $kullanici)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                                    <span class="text-gray-600 dark:text-gray-400 font-medium">{{ strtoupper(substr($kullanici->name, 0, 2)) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-black dark:text-white">{{ $kullanici->name }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $kullanici->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach($kullanici->roles ?? [] as $role)
                                @php
                                    $roleColors = [
                                        'super_admin' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        'admin' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                                        'bayi' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'isletme' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'kurye' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded text-xs {{ $roleColors[$role] ?? 'bg-gray-100 text-gray-800' }}">{{ ucfirst($role) }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $kullanici->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <button @click="editingUser = {{ $kullanici->toJson() }}; showEditModal = true" class="p-2 text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                @if($kullanici->id !== auth()->id())
                                <form action="{{ route('admin.kullanicilar.destroy', $kullanici) }}" method="POST" class="inline" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">Kullanıcı bulunamadı</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($kullanicilar->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $kullanicilar->links() }}
        </div>
        @endif
    </div>

    <!-- Create Modal -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showCreateModal = false"></div>
            <div class="relative bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Yeni Kullanıcı</h3>
                <form action="{{ route('admin.kullanicilar.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ad Soyad</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-posta</label>
                        <input type="email" name="email" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şifre</label>
                        <input type="password" name="password" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şifre Tekrar</label>
                        <input type="password" name="password_confirmation" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Roller</label>
                        <div class="space-y-2">
                            @foreach(['super_admin' => 'Super Admin', 'admin' => 'Admin', 'bayi' => 'Bayi', 'isletme' => 'İşletme', 'kurye' => 'Kurye'] as $value => $label)
                            <label class="flex items-center">
                                <input type="checkbox" name="roles[]" value="{{ $value }}" class="rounded border-gray-300 dark:border-gray-600 text-black dark:text-white">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showCreateModal = false" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">İptal</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200">Oluştur</button>
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
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Kullanıcı Düzenle</h3>
                <form :action="'/admin/kullanicilar/' + (editingUser?.id || '')" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ad Soyad</label>
                        <input type="text" name="name" x-model="editingUser.name" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-posta</label>
                        <input type="email" name="email" x-model="editingUser.email" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Yeni Şifre</label>
                        <input type="password" name="password" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                        <p class="text-xs text-gray-500 mt-1">Boş bırakırsanız değişmez</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Şifre Tekrar</label>
                        <input type="password" name="password_confirmation" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Roller</label>
                        <div class="space-y-2">
                            @foreach(['super_admin' => 'Super Admin', 'admin' => 'Admin', 'bayi' => 'Bayi', 'isletme' => 'İşletme', 'kurye' => 'Kurye'] as $value => $label)
                            <label class="flex items-center">
                                <input type="checkbox" name="roles[]" value="{{ $value }}" :checked="editingUser?.roles?.includes('{{ $value }}')" class="rounded border-gray-300 dark:border-gray-600">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showEditModal = false" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">İptal</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
