@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Kurye Yönetimi</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Kuryelerinizi yönetin ve performanslarını takip edin</p>
        </div>
        <button onclick="showCreateCourierModal()" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
            + Yeni Kurye
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

    <!-- Kurye İstatistikleri -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Toplam Kurye</p>
            <p class="text-3xl font-bold text-black dark:text-white">{{ $couriers->count() }}</p>
        </div>
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Aktif</p>
            <p class="text-3xl font-bold text-black dark:text-white">{{ $couriers->where('status', 'active')->count() }}</p>
        </div>
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Teslimat</p>
            <p class="text-3xl font-bold text-black dark:text-white">{{ $couriers->where('status', 'delivering')->count() }}</p>
        </div>
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Müsait</p>
            <p class="text-3xl font-bold text-black dark:text-white">{{ $couriers->where('status', 'available')->count() }}</p>
        </div>
    </div>

    <!-- Kurye Listesi -->
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">AD SOYAD</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">TELEFON</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">DURUM</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">GÜNLÜK TESLİMAT</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">PUAN</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">İŞLEMLER</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($couriers as $courier)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                        <td class="px-6 py-4 text-sm text-black dark:text-white">{{ $courier->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $courier->phone }}</td>
                        <td class="px-6 py-4 text-sm">
                            @php
                                $statusMap = [
                                    'available' => ['text' => 'Müsait', 'class' => 'border border-gray-300 dark:border-gray-700'],
                                    'delivering' => ['text' => 'Teslimat', 'class' => 'bg-black dark:bg-white text-white dark:text-black'],
                                    'offline' => ['text' => 'Çevrimdışı', 'class' => 'border border-gray-300 dark:border-gray-700'],
                                    'active' => ['text' => 'Aktif', 'class' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'],
                                ];
                                $status = $statusMap[$courier->status] ?? ['text' => $courier->status, 'class' => 'border border-gray-300 dark:border-gray-700'];
                            @endphp
                            <span class="px-2 py-1 text-xs rounded {{ $status['class'] }}">
                                {{ $status['text'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">
                            {{ $courier->orders()->whereDate('created_at', today())->count() }}
                        </td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">-</td>
                        <td class="px-6 py-4 text-sm">
                            <button onclick="showEditCourierModal({{ $courier->id }}, '{{ $courier->name }}', '{{ $courier->phone }}', '{{ $courier->email }}', '{{ $courier->status }}')" 
                                class="text-black dark:text-white hover:opacity-60 mr-3">Düzenle</button>
                            <form action="{{ route('couriers.destroy', $courier) }}" method="POST" class="inline" 
                                onsubmit="return confirm('Bu kuryeyi silmek istediğinize emin misiniz?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 dark:text-red-400 hover:opacity-60">Sil</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-600 dark:text-gray-400">
                            Kurye bulunamadı
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Courier Modal -->
<div id="createCourierModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Yeni Kurye</h3>
        <form action="{{ route('couriers.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Ad Soyad *</label>
                    <input type="text" name="name" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Telefon *</label>
                    <input type="text" name="phone" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">E-posta</label>
                    <input type="email" name="email" 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Durum *</label>
                    <select name="status" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                        <option value="available">Müsait</option>
                        <option value="active">Aktif</option>
                        <option value="delivering">Teslimat</option>
                        <option value="offline">Çevrimdışı</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="submit" 
                    class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                    Oluştur
                </button>
                <button type="button" onclick="closeCreateCourierModal()" 
                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">
                    İptal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Courier Modal -->
<div id="editCourierModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Kurye Düzenle</h3>
        <form id="editCourierForm" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Ad Soyad *</label>
                    <input type="text" name="name" id="edit_courier_name" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Telefon *</label>
                    <input type="text" name="phone" id="edit_courier_phone" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">E-posta</label>
                    <input type="email" name="email" id="edit_courier_email" 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Durum *</label>
                    <select name="status" id="edit_courier_status" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                        <option value="available">Müsait</option>
                        <option value="active">Aktif</option>
                        <option value="delivering">Teslimat</option>
                        <option value="offline">Çevrimdışı</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="submit" 
                    class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                    Güncelle
                </button>
                <button type="button" onclick="closeEditCourierModal()" 
                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">
                    İptal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showCreateCourierModal() {
    document.getElementById('createCourierModal').classList.remove('hidden');
}

function closeCreateCourierModal() {
    document.getElementById('createCourierModal').classList.add('hidden');
}

function showEditCourierModal(id, name, phone, email, status) {
    document.getElementById('editCourierForm').action = `/isletmem/kuryeler/${id}`;
    document.getElementById('edit_courier_name').value = name;
    document.getElementById('edit_courier_phone').value = phone;
    document.getElementById('edit_courier_email').value = email || '';
    document.getElementById('edit_courier_status').value = status;
    document.getElementById('editCourierModal').classList.remove('hidden');
}

function closeEditCourierModal() {
    document.getElementById('editCourierModal').classList.add('hidden');
}
</script>
@endsection
