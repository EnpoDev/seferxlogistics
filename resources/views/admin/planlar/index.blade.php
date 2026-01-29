@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn" x-data="{ showCreateModal: false, showEditModal: false, editingPlan: null }">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Planlar</h1>
            <p class="text-gray-600 dark:text-gray-400">Abonelik planlarını yönetin</p>
        </div>
        <button @click="showCreateModal = true" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Yeni Plan</span>
        </button>
    </div>

    @if(session('success'))<div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg"><p class="text-green-700 dark:text-green-400">{{ session('success') }}</p></div>@endif
    @if(session('error'))<div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"><p class="text-red-700 dark:text-red-400">{{ session('error') }}</p></div>@endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($planlar as $plan)
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6 {{ $plan->is_featured ? 'ring-2 ring-black dark:ring-white' : '' }}">
            @if($plan->is_featured)
            <span class="inline-block px-2 py-1 bg-black dark:bg-white text-white dark:text-black text-xs rounded mb-4">Öne Çıkan</span>
            @endif
            <h3 class="text-xl font-bold text-black dark:text-white">{{ $plan->name }}</h3>
            <p class="text-3xl font-bold text-black dark:text-white mt-2">
                {{ number_format($plan->price, 2) }} TL
                <span class="text-sm font-normal text-gray-600 dark:text-gray-400">/{{ $plan->billing_period === 'monthly' ? 'ay' : 'yıl' }}</span>
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">{{ $plan->description }}</p>

            @if($plan->features)
            <ul class="mt-4 space-y-2">
                @foreach($plan->features as $feature)
                <li class="flex items-center text-sm text-gray-700 dark:text-gray-300">
                    <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ $feature }}
                </li>
                @endforeach
            </ul>
            @endif

            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <span class="px-2 py-1 rounded text-xs {{ $plan->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-400' }}">
                    {{ $plan->is_active ? 'Aktif' : 'Pasif' }}
                </span>
                <div class="flex space-x-2">
                    <button @click="editingPlan = {{ $plan->toJson() }}; showEditModal = true" class="p-2 text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <form action="{{ route('admin.planlar.destroy', $plan) }}" method="POST" class="inline" onsubmit="return confirm('Bu planı silmek istediğinize emin misiniz?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-2 text-red-600 dark:text-red-400 hover:text-red-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-12 text-gray-500">
            <p>Plan bulunamadi</p>
            <button @click="showCreateModal = true" class="mt-4 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg">İlk Planı Oluştur</button>
        </div>
        @endforelse
    </div>

    <!-- Create Modal -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showCreateModal = false"></div>
            <div class="relative bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 w-full max-w-lg p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Yeni Plan</h3>
                <form action="{{ route('admin.planlar.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Plan Adı</label><input type="text" name="name" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label><input type="text" name="slug" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label><textarea name="description" rows="2" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></textarea></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fiyat (TL)</label><input type="number" name="price" step="0.01" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Periyot</label><select name="billing_period" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"><option value="monthly">Aylık</option><option value="yearly">Yıllık</option></select></div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Kullanıcı</label><input type="number" name="max_users" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Sipariş</label><input type="number" name="max_orders" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Şube</label><input type="number" name="max_branches" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                    </div>
                    <div class="flex space-x-4">
                        <label class="flex items-center"><input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300"><span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktif</span></label>
                        <label class="flex items-center"><input type="checkbox" name="is_featured" value="1" class="rounded border-gray-300"><span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Öne Çıkan</span></label>
                    </div>
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
            <div class="relative bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 w-full max-w-lg p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Plan Düzenle</h3>
                <form :action="'/admin/planlar/' + (editingPlan?.id || '')" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Plan Adı</label><input type="text" name="name" x-model="editingPlan.name" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label><input type="text" name="slug" x-model="editingPlan.slug" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label><textarea name="description" rows="2" x-model="editingPlan.description" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></textarea></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fiyat (TL)</label><input type="number" name="price" step="0.01" x-model="editingPlan.price" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Periyot</label><select name="billing_period" x-model="editingPlan.billing_period" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"><option value="monthly">Aylık</option><option value="yearly">Yıllık</option></select></div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Kullanıcı</label><input type="number" name="max_users" x-model="editingPlan.max_users" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Sipariş</label><input type="number" name="max_orders" x-model="editingPlan.max_orders" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                        <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Şube</label><input type="number" name="max_branches" x-model="editingPlan.max_branches" class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
                    </div>
                    <div class="flex space-x-4">
                        <label class="flex items-center"><input type="checkbox" name="is_active" value="1" :checked="editingPlan?.is_active" class="rounded border-gray-300"><span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Aktif</span></label>
                        <label class="flex items-center"><input type="checkbox" name="is_featured" value="1" :checked="editingPlan?.is_featured" class="rounded border-gray-300"><span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Öne Çıkan</span></label>
                    </div>
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
