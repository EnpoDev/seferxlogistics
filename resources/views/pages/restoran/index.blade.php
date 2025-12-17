@extends('layouts.app')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Restoran Yönetimi</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Sistemdeki tüm restoranları yönetin</p>
        </div>
        <a href="{{ route('restoran.create') }}" 
            class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80 transition-opacity flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            <span>Yeni Restoran</span>
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-4 mb-6">
        <form method="GET" action="{{ route('restoran.index') }}" class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Restoran ara..."
                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
            </div>
            <select name="category" onchange="this.form.submit()"
                class="px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                <option value="">Tüm Kategoriler</option>
                @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()"
                class="px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                <option value="">Tüm Durumlar</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Pasif</option>
                <option value="featured" {{ request('status') == 'featured' ? 'selected' : '' }}>Öne Çıkan</option>
            </select>
            <button type="submit" class="px-4 py-2.5 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                Filtrele
            </button>
        </form>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <p class="text-sm text-gray-500">Toplam Restoran</p>
            <p class="text-2xl font-bold text-black dark:text-white">{{ \App\Models\Restaurant::count() }}</p>
        </div>
        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <p class="text-sm text-gray-500">Aktif Restoran</p>
            <p class="text-2xl font-bold text-green-600">{{ \App\Models\Restaurant::where('is_active', true)->count() }}</p>
        </div>
        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <p class="text-sm text-gray-500">Öne Çıkan</p>
            <p class="text-2xl font-bold text-yellow-600">{{ \App\Models\Restaurant::where('is_featured', true)->count() }}</p>
        </div>
        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-4">
            <p class="text-sm text-gray-500">Toplam Ürün</p>
            <p class="text-2xl font-bold text-blue-600">{{ \App\Models\Product::count() }}</p>
        </div>
    </div>

    <!-- Restaurant Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($restaurants as $restaurant)
        <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden hover:shadow-lg transition-shadow">
            <!-- Restaurant Banner -->
            <div class="h-40 relative bg-gradient-to-br from-orange-400 to-red-500">
                @if($restaurant->banner_image)
                <img src="{{ Storage::url($restaurant->banner_image) }}" alt="{{ $restaurant->name }}" class="w-full h-full object-cover">
                @endif
                
                <!-- Logo -->
                @if($restaurant->logo)
                <div class="absolute -bottom-8 left-4">
                    <img src="{{ Storage::url($restaurant->logo) }}" alt="{{ $restaurant->name }}" 
                        class="w-16 h-16 rounded-xl border-4 border-white dark:border-[#1a1a1a] object-cover bg-white">
                </div>
                @else
                <div class="absolute -bottom-8 left-4">
                    <div class="w-16 h-16 rounded-xl border-4 border-white dark:border-[#1a1a1a] bg-white dark:bg-gray-800 flex items-center justify-center">
                        <span class="text-2xl font-bold text-gray-400">{{ substr($restaurant->name, 0, 1) }}</span>
                    </div>
                </div>
                @endif

                <!-- Status Badges -->
                <div class="absolute top-3 right-3 flex space-x-2">
                    @if($restaurant->is_featured)
                    <span class="px-2 py-1 bg-yellow-500 text-white text-xs font-medium rounded-full flex items-center space-x-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span>Öne Çıkan</span>
                    </span>
                    @endif
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $restaurant->is_active ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' }}">
                        {{ $restaurant->is_active ? 'Açık' : 'Kapalı' }}
                    </span>
                </div>
            </div>

            <!-- Restaurant Info -->
            <div class="p-4 pt-10">
                <div class="flex items-start justify-between mb-2">
                    <h3 class="text-lg font-semibold text-black dark:text-white">{{ $restaurant->name }}</h3>
                    <div class="flex items-center space-x-1 text-yellow-500">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="text-sm font-medium text-black dark:text-white">{{ number_format($restaurant->rating, 1) }}</span>
                    </div>
                </div>

                <!-- Categories -->
                <div class="flex flex-wrap gap-1 mb-3">
                    @foreach($restaurant->categories->take(3) as $category)
                    <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs rounded">
                        {{ $category->name }}
                    </span>
                    @endforeach
                    @if($restaurant->categories->count() > 3)
                    <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs rounded">
                        +{{ $restaurant->categories->count() - 3 }}
                    </span>
                    @endif
                </div>

                <!-- Stats -->
                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                    <span>{{ $restaurant->products_count }} ürün</span>
                    <span>Min. ₺{{ number_format($restaurant->min_order_amount, 0) }}</span>
                    <span>{{ $restaurant->max_delivery_time }} dk</span>
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-2">
                    <a href="{{ route('restoran.edit', $restaurant) }}" 
                        class="flex-1 px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg text-center text-sm font-medium text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        Düzenle
                    </a>
                    <button onclick="toggleFeatured({{ $restaurant->id }})" 
                        class="p-2 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors {{ $restaurant->is_featured ? 'text-yellow-500' : 'text-gray-400' }}">
                        <svg class="w-5 h-5" fill="{{ $restaurant->is_featured ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </button>
                    <button onclick="deleteRestaurant({{ $restaurant->id }})" 
                        class="p-2 border border-gray-200 dark:border-gray-700 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="text-center py-16 bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-2xl">
                <svg class="w-16 h-16 text-gray-300 dark:text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Henüz restoran eklenmemiş</p>
                <a href="{{ route('restoran.create') }}" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                    İlk Restoranı Ekle
                </a>
            </div>
        </div>
        @endforelse
    </div>

    @if($restaurants->hasPages())
    <div class="mt-6">
        {{ $restaurants->links() }}
    </div>
    @endif
</div>

<script>
function toggleFeatured(id) {
    fetch(`/restoran/${id}/toggle-featured`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}

function deleteRestaurant(id) {
    if (confirm('Bu restoranı silmek istediğinizden emin misiniz?')) {
        fetch(`/restoran/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        }).then(() => window.location.reload());
    }
}
</script>
@endsection

