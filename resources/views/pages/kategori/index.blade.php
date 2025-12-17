@extends('layouts.app')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Kategori Yönetimi</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Kategorileri ve restoran atamalarını yönetin</p>
        </div>
        <button onclick="openNewCategoryModal()" 
            class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80 transition-opacity flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            <span>Yeni Kategori</span>
        </button>
    </div>

    <!-- Category Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($categories as $category)
        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden hover:shadow-lg transition-shadow group">
            <!-- Category Image/Color Header -->
            <div class="h-32 relative overflow-hidden" style="background: {{ $category->color ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }}">
                @if($category->image)
                <img src="{{ Storage::url($category->image) }}" alt="{{ $category->name }}" class="w-full h-full object-cover">
                @else
                <div class="absolute inset-0 flex items-center justify-center">
                    @if($category->icon)
                    <span class="text-5xl">{{ $category->icon }}</span>
                    @else
                    <svg class="w-16 h-16 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    @endif
                </div>
                @endif
                
                <!-- Status Badge -->
                <div class="absolute top-3 right-3">
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $category->is_active ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' }}">
                        {{ $category->is_active ? 'Aktif' : 'Pasif' }}
                    </span>
                </div>

                <!-- Edit Button (Hidden by default, shown on hover) -->
                <div class="absolute top-3 left-3 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="openEditCategoryModal({{ $category->id }})" 
                        class="p-2 bg-white/90 dark:bg-black/90 rounded-lg hover:bg-white dark:hover:bg-black">
                        <svg class="w-4 h-4 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Category Info -->
            <div class="p-4">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-1">{{ $category->name }}</h3>
                @if($category->description)
                <p class="text-sm text-gray-500 line-clamp-2 mb-3">{{ $category->description }}</p>
                @endif

                <!-- Stats -->
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center space-x-4">
                        <span class="flex items-center space-x-1 text-gray-600 dark:text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span class="font-medium">{{ $category->restaurants_count }}</span>
                            <span class="text-gray-400">restoran</span>
                        </span>
                        <span class="flex items-center space-x-1 text-gray-600 dark:text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span class="font-medium">{{ $category->products_count }}</span>
                            <span class="text-gray-400">ürün</span>
                        </span>
                    </div>
                </div>

                <!-- Restaurant Assign Button -->
                <button onclick="openAssignRestaurantsModal({{ $category->id }}, '{{ $category->name }}', {{ json_encode($category->restaurants->pluck('id')) }})" 
                    class="w-full mt-4 px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors flex items-center justify-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <span>Restoran Ata</span>
                </button>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="text-center py-16 bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-2xl">
                <svg class="w-16 h-16 text-gray-300 dark:text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Henüz kategori oluşturulmamış</p>
                <button onclick="openNewCategoryModal()" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                    İlk Kategoriyi Oluştur
                </button>
            </div>
        </div>
        @endforelse
    </div>
</div>

<!-- New Category Modal -->
<div id="newCategoryModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeNewCategoryModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#1a1a1a] rounded-2xl shadow-xl w-full max-w-md animate-slideUp">
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-black dark:text-white">Yeni Kategori</h3>
                <button onclick="closeNewCategoryModal()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Kategori Adı *</label>
                    <input type="text" name="name" required
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Açıklama</label>
                    <textarea name="description" rows="2"
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Görsel</label>
                    <input type="file" name="image" accept="image/*"
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">İkon (Emoji)</label>
                        <input type="text" name="icon" placeholder="🍔"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Renk</label>
                        <input type="color" name="color" value="#667eea"
                            class="w-full h-11 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg">
                    </div>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active_new" value="1" checked class="mr-2">
                    <label for="is_active_new" class="text-sm text-gray-700 dark:text-gray-300">Aktif</label>
                </div>
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeNewCategoryModal()" 
                        class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">
                        İptal
                    </button>
                    <button type="submit" 
                        class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Restaurants Modal -->
<div id="assignRestaurantsModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeAssignRestaurantsModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#1a1a1a] rounded-2xl shadow-xl w-full max-w-lg animate-slideUp">
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800">
                <div>
                    <h3 class="text-lg font-semibold text-black dark:text-white">Restoran Ata</h3>
                    <p class="text-sm text-gray-500" id="assignCategoryName"></p>
                </div>
                <button onclick="closeAssignRestaurantsModal()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="assignRestaurantsForm" class="p-6">
                <input type="hidden" id="assignCategoryId">
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @foreach($restaurants as $restaurant)
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-black rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                        <input type="checkbox" name="restaurants[]" value="{{ $restaurant->id }}" class="restaurant-checkbox mr-3">
                        <div class="flex-1">
                            <p class="font-medium text-black dark:text-white">{{ $restaurant->name }}</p>
                            <p class="text-sm text-gray-500">{{ $restaurant->address ?? 'Adres belirtilmemiş' }}</p>
                        </div>
                        @if($restaurant->is_featured)
                        <span class="px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 text-xs rounded">Öne Çıkan</span>
                        @endif
                    </label>
                    @endforeach
                </div>
                @if($restaurants->isEmpty())
                <div class="text-center py-8">
                    <p class="text-gray-500">Henüz restoran eklenmemiş</p>
                    <a href="{{ route('restoran.create') }}" class="inline-block mt-2 text-blue-600 hover:underline">Restoran Ekle</a>
                </div>
                @endif
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-800 mt-4">
                    <button type="button" onclick="closeAssignRestaurantsModal()" 
                        class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">
                        İptal
                    </button>
                    <button type="submit" 
                        class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openNewCategoryModal() {
    document.getElementById('newCategoryModal').classList.remove('hidden');
}

function closeNewCategoryModal() {
    document.getElementById('newCategoryModal').classList.add('hidden');
}

function openEditCategoryModal(categoryId) {
    // Redirect to edit page or open modal with category data
    window.location.href = `/categories/${categoryId}/edit`;
}

function openAssignRestaurantsModal(categoryId, categoryName, currentRestaurants) {
    document.getElementById('assignCategoryId').value = categoryId;
    document.getElementById('assignCategoryName').textContent = categoryName + ' kategorisine restoran atayın';
    
    // Reset all checkboxes
    document.querySelectorAll('.restaurant-checkbox').forEach(cb => cb.checked = false);
    
    // Check current restaurants
    currentRestaurants.forEach(id => {
        const checkbox = document.querySelector(`.restaurant-checkbox[value="${id}"]`);
        if (checkbox) checkbox.checked = true;
    });
    
    document.getElementById('assignRestaurantsModal').classList.remove('hidden');
}

function closeAssignRestaurantsModal() {
    document.getElementById('assignRestaurantsModal').classList.add('hidden');
}

document.getElementById('assignRestaurantsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const categoryId = document.getElementById('assignCategoryId').value;
    const checkboxes = document.querySelectorAll('.restaurant-checkbox:checked');
    const restaurants = Array.from(checkboxes).map(cb => cb.value);
    
    fetch(`/kategori/${categoryId}/restaurants`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ restaurants })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
});
</script>
@endsection

