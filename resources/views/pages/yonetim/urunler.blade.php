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

                    <!-- Varyasyon Gruplari -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="flex items-center justify-between mb-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Varyasyon Gruplari</label>
                            <button type="button" @click="addOptionGroup()"
                                class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                                + Grup Ekle
                            </button>
                        </div>

                        <template x-for="(group, gIndex) in formData.option_groups" :key="gIndex">
                            <div class="mb-4 p-4 bg-gray-50 dark:bg-black rounded-lg border border-gray-200 dark:border-gray-700">
                                <!-- Grup Basligi -->
                                <div class="flex items-start gap-3 mb-3">
                                    <div class="flex-1">
                                        <input type="text" :name="'option_groups[' + gIndex + '][name]'" x-model="group.name" placeholder="Grup adi (Porsiyon, Ekstralar...)"
                                            class="w-full px-3 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white text-sm">
                                    </div>
                                    <select :name="'option_groups[' + gIndex + '][type]'" x-model="group.type"
                                        class="px-3 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white text-sm">
                                        <option value="single">Tekli Secim</option>
                                        <option value="multiple">Coklu Secim</option>
                                    </select>
                                    <button type="button" @click="removeOptionGroup(gIndex)" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>

                                <!-- Zorunlu/Opsiyonel Toggle -->
                                <div class="flex items-center gap-4 mb-3">
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" :name="'option_groups[' + gIndex + '][is_required]'" value="1" x-model="group.is_required"
                                            class="rounded border-gray-300 dark:border-gray-700">
                                        <span class="text-xs text-gray-600 dark:text-gray-400">Zorunlu</span>
                                    </label>
                                    <div class="flex items-center gap-2" x-show="group.type === 'multiple'">
                                        <span class="text-xs text-gray-500">Max secim:</span>
                                        <input type="number" :name="'option_groups[' + gIndex + '][max_selections]'" x-model="group.max_selections" min="0" placeholder="0=sinirsiz"
                                            class="w-16 px-2 py-1 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded text-black dark:text-white text-xs">
                                    </div>
                                </div>

                                <!-- Secenekler -->
                                <div class="space-y-2">
                                    <template x-for="(option, oIndex) in group.options" :key="oIndex">
                                        <div class="flex items-center gap-2">
                                            <input type="text" :name="'option_groups[' + gIndex + '][options][' + oIndex + '][name]'" x-model="option.name" placeholder="Secenek adi"
                                                class="flex-1 px-3 py-1.5 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white text-sm">
                                            <div class="flex items-center gap-1">
                                                <span class="text-xs text-gray-500">+/-</span>
                                                <input type="number" :name="'option_groups[' + gIndex + '][options][' + oIndex + '][price_diff]'" x-model="option.price_diff" step="0.01" placeholder="0.00"
                                                    class="w-20 px-2 py-1.5 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white text-sm text-right">
                                                <span class="text-xs text-gray-500">TL</span>
                                            </div>
                                            <button type="button" @click="removeOption(gIndex, oIndex)" class="p-1 text-red-400 hover:text-red-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </template>

                                    <button type="button" @click="addOption(gIndex)"
                                        class="w-full px-3 py-1.5 text-xs text-gray-500 dark:text-gray-400 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-gray-400 dark:hover:border-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                                        + Secenek Ekle
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div x-show="formData.option_groups.length === 0" class="text-center py-4 text-xs text-gray-400 dark:text-gray-600 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                            Varyasyon grubu eklenmemis. "Grup Ekle" ile baslayin.
                        </div>
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
            in_stock: true,
            option_groups: []
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
                    in_stock: product.in_stock,
                    option_groups: (product.option_groups || []).map(g => ({
                        name: g.name,
                        type: g.type || 'single',
                        is_required: g.is_required || false,
                        max_selections: g.max_selections || 0,
                        options: (g.options || []).map(o => ({
                            name: o.name,
                            price_diff: o.price_diff || 0
                        }))
                    }))
                };
            } else {
                this.formData = {
                    id: null,
                    name: '',
                    category_id: '',
                    price: '',
                    description: '',
                    is_active: true,
                    in_stock: true,
                    option_groups: []
                };
            }
            this.showModal = true;
        },

        addOptionGroup() {
            this.formData.option_groups.push({
                name: '',
                type: 'single',
                is_required: false,
                max_selections: 0,
                options: [{ name: '', price_diff: 0 }]
            });
        },

        removeOptionGroup(index) {
            this.formData.option_groups.splice(index, 1);
        },

        addOption(groupIndex) {
            this.formData.option_groups[groupIndex].options.push({ name: '', price_diff: 0 });
        },

        removeOption(groupIndex, optionIndex) {
            this.formData.option_groups[groupIndex].options.splice(optionIndex, 1);
        }
    }
}
</script>
@endpush
@endsection
