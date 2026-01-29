@extends('layouts.app')

@section('content')
<div class="p-6" x-data="productManager()">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-black dark:text-white">Urunler</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Urunlerinizi yonetin</p>
        </div>
        <button @click="openModal('create')" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
            Yeni Urun
        </button>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg mb-6">
            <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg mb-6">
            <p class="text-red-700 dark:text-red-400">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Filtreler -->
    <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-4 mb-6">
        <form method="GET" action="{{ route('yonetim.urunler') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Urun ara..."
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
            </div>
            <div class="w-48">
                <select name="category_id" class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <option value="">Tum Kategoriler</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <select name="status" class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <option value="">Tum Durum</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="passive" {{ request('status') === 'passive' ? 'selected' : '' }}>Pasif</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
                Filtrele
            </button>
            @if(request()->hasAny(['search', 'category_id', 'status']))
                <a href="{{ route('yonetim.urunler') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
                    Temizle
                </a>
            @endif
        </form>
    </div>

    <!-- Urun Listesi -->
    <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Urun Adi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kategori</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fiyat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stok</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Durum</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Islemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($products as $product)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="w-10 h-10 rounded-lg object-cover mr-3">
                            @else
                                <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                            <span class="text-black dark:text-white font-medium">{{ $product->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-gray-600 dark:text-gray-400">{{ $product->category->name ?? '-' }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-black dark:text-white font-medium">{{ number_format($product->price, 2) }} TL</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($product->in_stock)
                            <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">Stokta</span>
                        @else
                            <span class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full">Tukendi</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($product->is_active)
                            <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">Aktif</span>
                        @else
                            <span class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full">Pasif</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <div class="flex justify-end gap-2">
                            <button @click="openModal('edit', {{ $product->toJson() }})" class="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white">
                                Duzenle
                            </button>
                            <form action="{{ route('yonetim.urunler.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Bu urunu silmek istediginizden emin misiniz?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700">Sil</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">Urun bulunamadi</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($products->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $products->withQueryString()->links() }}
        </div>
        @endif
    </div>

    <!-- Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
            <div class="relative bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 w-full max-w-lg p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4" x-text="modalMode === 'create' ? 'Yeni Urun Ekle' : 'Urunu Duzenle'"></h3>

                <form :action="modalMode === 'create' ? '{{ route('yonetim.urunler.store') }}' : '/yonetim/urunler/' + (formData.id || '')" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <template x-if="modalMode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Urun Adi *</label>
                        <input type="text" name="name" x-model="formData.name" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori *</label>
                        <select name="category_id" x-model="formData.category_id" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                            <option value="">Secin</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fiyat (TL) *</label>
                        <input type="number" name="price" x-model="formData.price" step="0.01" min="0" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Aciklama</label>
                        <textarea name="description" x-model="formData.description" rows="3"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gorsel</label>
                        <input type="file" name="image" accept="image/*"
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                        <p class="text-xs text-gray-500 mt-1">Max 2MB, JPG/PNG</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="formData.is_active" :checked="formData.is_active"
                                class="rounded border-gray-300 dark:border-gray-700">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                        </label>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="in_stock" value="1" x-model="formData.in_stock" :checked="formData.in_stock"
                                class="rounded border-gray-300 dark:border-gray-700">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Stokta</span>
                        </label>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showModal = false" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
                            Iptal
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
                            <span x-text="modalMode === 'create' ? 'Ekle' : 'Guncelle'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function productManager() {
    return {
        showModal: false,
        modalMode: 'create',
        formData: {
            id: null,
            name: '',
            category_id: '',
            price: '',
            description: '',
            is_active: true,
            in_stock: true
        },

        openModal(mode, product = null) {
            this.modalMode = mode;
            if (mode === 'edit' && product) {
                this.formData = {
                    id: product.id,
                    name: product.name,
                    category_id: product.category_id,
                    price: product.price,
                    description: product.description || '',
                    is_active: product.is_active,
                    in_stock: product.in_stock
                };
            } else {
                this.formData = {
                    id: null,
                    name: '',
                    category_id: '',
                    price: '',
                    description: '',
                    is_active: true,
                    in_stock: true
                };
            }
            this.showModal = true;
        }
    }
}
</script>
@endpush
@endsection
