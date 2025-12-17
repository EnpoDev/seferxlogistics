@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Kullanıcı Yönetimi</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">İşletme kullanıcılarını yönetin</p>
        </div>
        <button onclick="showCreateModal()" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
            + Yeni Kullanıcı
        </button>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg">
            <p class="text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg">
            <p class="text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
    @endif

    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">AD SOYAD</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">E-POSTA</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">ROLLER</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">İŞLEMLER</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                        <td class="px-6 py-4 text-sm text-black dark:text-white">{{ $user->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $user->email }}</td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">
                            @foreach($user->roles ?? [] as $role)
                                <span class="px-2 py-1 text-xs border border-gray-300 dark:border-gray-700 rounded mr-1">
                                    {{ ucfirst($role) }}
                                </span>
                            @endforeach
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <button onclick="showEditModal({{ $user->id }}, '{{ $user->name }}', '{{ $user->email }}', {{ json_encode($user->roles ?? []) }})" 
                                class="text-black dark:text-white hover:opacity-60 mr-3">Düzenle</button>
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" 
                                onsubmit="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 dark:text-red-400 hover:opacity-60">Sil</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-600 dark:text-gray-400">
                            Kullanıcı bulunamadı
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($users->hasPages())
    <div class="mt-6">
        {{ $users->links() }}
    </div>
    @endif
</div>

<!-- Create User Modal -->
<div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Yeni Kullanıcı</h3>
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Ad Soyad *</label>
                    <input type="text" name="name" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">E-posta *</label>
                    <input type="email" name="email" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Şifre *</label>
                    <input type="password" name="password" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Şifre Tekrar *</label>
                    <input type="password" name="password_confirmation" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Roller *</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="roles[]" value="bayi" 
                                class="mr-2 rounded border-gray-300 dark:border-gray-700">
                            <span class="text-sm text-black dark:text-white">Bayi</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="roles[]" value="isletme" 
                                class="mr-2 rounded border-gray-300 dark:border-gray-700">
                            <span class="text-sm text-black dark:text-white">İşletme</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="submit" 
                    class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                    Oluştur
                </button>
                <button type="button" onclick="closeCreateModal()" 
                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">
                    İptal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Kullanıcı Düzenle</h3>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Ad Soyad *</label>
                    <input type="text" name="name" id="edit_name" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">E-posta *</label>
                    <input type="email" name="email" id="edit_email" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Şifre (boş bırakın değiştirmemek için)</label>
                    <input type="password" name="password" 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Şifre Tekrar</label>
                    <input type="password" name="password_confirmation" 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Roller *</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="roles[]" value="bayi" id="edit_role_bayi" 
                                class="mr-2 rounded border-gray-300 dark:border-gray-700">
                            <span class="text-sm text-black dark:text-white">Bayi</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="roles[]" value="isletme" id="edit_role_isletme" 
                                class="mr-2 rounded border-gray-300 dark:border-gray-700">
                            <span class="text-sm text-black dark:text-white">İşletme</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="submit" 
                    class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                    Güncelle
                </button>
                <button type="button" onclick="closeEditModal()" 
                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">
                    İptal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}

function showEditModal(id, name, email, roles) {
    document.getElementById('editForm').action = `/isletmem/kullanicilar/${id}`;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role_bayi').checked = roles.includes('bayi');
    document.getElementById('edit_role_isletme').checked = roles.includes('isletme');
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>
@endsection
