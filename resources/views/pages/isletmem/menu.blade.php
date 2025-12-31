@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Menü Yönetimi"
        subtitle="Menü ürünlerinizi düzenleyin"
    >
        <x-slot name="icon">
            <x-ui.icon name="menu" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <x-ui.button icon="plus" onclick="showCreateCategoryModal()">
                Yeni Kategori
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Mesajlar --}}
    @if(session('success'))
        <x-feedback.alert type="success" class="mb-6">{{ session('success') }}</x-feedback.alert>
    @endif
    @if(session('error'))
        <x-feedback.alert type="danger" class="mb-6">{{ session('error') }}</x-feedback.alert>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Kategoriler --}}
        <x-ui.card>
            <h3 class="text-sm font-semibold text-black dark:text-white mb-3">Kategoriler</h3>
            <div class="space-y-2">
                @forelse($categories as $category)
                <div class="flex items-center gap-2">
                    <a href="{{ route('isletmem.menu', ['category_id' => $category->id]) }}"
                       class="flex-1 p-3 rounded-lg cursor-pointer transition-colors
                              @if($selectedCategory && $selectedCategory->id === $category->id)
                                  bg-black dark:bg-white text-white dark:text-black
                              @else
                                  border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900 text-black dark:text-white
                              @endif">
                        {{ $category->name }}
                    </a>
                    <x-ui.button variant="ghost" size="sm"
                        onclick="showEditCategoryModal({{ $category->id }}, '{{ $category->name }}', '{{ $category->description }}', {{ $category->order }}, {{ $category->is_active ? 'true' : 'false' }})">
                        <x-ui.icon name="edit" class="w-4 h-4" />
                    </x-ui.button>
                </div>
                @empty
                <p class="text-sm text-gray-600 dark:text-gray-400">Kategori bulunamadı</p>
                @endforelse
            </div>
        </x-ui.card>

        {{-- Ürünler --}}
        <div class="lg:col-span-3">
            <x-ui.card>
                @if($selectedCategory)
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-black dark:text-white">{{ $selectedCategory->name }}</h3>
                    <x-ui.button size="sm" onclick="showCreateProductModal({{ $selectedCategory->id }})">
                        Ürün Ekle
                    </x-ui.button>
                </div>
                <div class="space-y-3">
                    @forelse($selectedCategory->products as $product)
                    <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-800 rounded-lg">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-black dark:text-white">{{ $product->name }}</p>
                                @if(!$product->is_active)
                                    <x-ui.badge type="danger" size="sm">Pasif</x-ui.badge>
                                @endif
                                @if(!$product->in_stock)
                                    <x-ui.badge type="warning" size="sm">Stokta Yok</x-ui.badge>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $product->description }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <x-data.money :amount="$product->price" class="text-lg font-semibold" />
                            <x-ui.button variant="ghost" size="sm"
                                onclick="showEditProductModal({{ $product->id }}, {{ $product->category_id }}, '{{ $product->name }}', '{{ $product->description }}', {{ $product->price }}, {{ $product->is_active ? 'true' : 'false' }}, {{ $product->in_stock ? 'true' : 'false' }})">
                                Düzenle
                            </x-ui.button>
                            <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline"
                                onsubmit="return confirm('Bu ürünü silmek istediğinize emin misiniz?')">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm">Sil</x-ui.button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <x-ui.empty-state title="Ürün bulunamadı" description="Bu kategoride henüz ürün yok" icon="menu" />
                    @endforelse
                </div>
                @else
                <x-ui.empty-state title="Kategori seçin" description="Bir kategori seçin veya yeni kategori oluşturun" icon="menu" />
                @endif
            </x-ui.card>
        </div>
    </div>
</div>

{{-- Kategori Oluştur Modal --}}
<x-ui.modal name="createCategoryModal" title="Yeni Kategori" size="md">
    <form action="{{ route('categories.store') }}" method="POST" class="space-y-4">
        @csrf
        <x-form.input name="name" label="Kategori Adı" required />
        <x-form.textarea name="description" label="Açıklama" :rows="3" />
        <x-form.input type="number" name="order" label="Sıralama" value="0" min="0" />
        <x-form.checkbox name="is_active" value="1" label="Aktif" checked />

        <div class="flex gap-3 pt-4">
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', 'createCategoryModal')" class="flex-1">İptal</x-ui.button>
            <x-ui.button type="submit" class="flex-1">Oluştur</x-ui.button>
        </div>
    </form>
</x-ui.modal>

{{-- Kategori Düzenle Modal --}}
<x-ui.modal name="editCategoryModal" title="Kategori Düzenle" size="md">
    <form id="editCategoryForm" method="POST" class="space-y-4">
        @csrf
        @method('PUT')
        <x-form.input name="name" id="edit_cat_name" label="Kategori Adı" required />
        <x-form.textarea name="description" id="edit_cat_desc" label="Açıklama" :rows="3" />
        <x-form.input type="number" name="order" id="edit_cat_order" label="Sıralama" min="0" />
        <x-form.checkbox name="is_active" id="edit_cat_active" value="1" label="Aktif" />

        <div class="flex gap-3 pt-4">
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', 'editCategoryModal')" class="flex-1">İptal</x-ui.button>
            <x-ui.button type="submit" class="flex-1">Güncelle</x-ui.button>
        </div>
    </form>
    <form id="deleteCategoryForm" method="POST" class="mt-4" onsubmit="return confirm('Bu kategoriyi silmek istediğinize emin misiniz?')">
        @csrf
        @method('DELETE')
        <x-ui.button type="submit" variant="danger" class="w-full">Kategoriyi Sil</x-ui.button>
    </form>
</x-ui.modal>

{{-- Ürün Oluştur Modal --}}
<x-ui.modal name="createProductModal" title="Yeni Ürün" size="md">
    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <input type="hidden" name="category_id" id="create_product_category_id">
        <x-form.input name="name" label="Ürün Adı" required />
        <x-form.textarea name="description" label="Açıklama" :rows="3" />
        <x-form.input type="number" name="price" label="Fiyat (TL)" step="0.01" min="0" required />
        <x-form.input type="file" name="image" label="Resim" accept="image/*" />
        <div class="flex gap-4">
            <x-form.checkbox name="is_active" value="1" label="Aktif" checked />
            <x-form.checkbox name="in_stock" value="1" label="Stokta Var" checked />
        </div>

        <div class="flex gap-3 pt-4">
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', 'createProductModal')" class="flex-1">İptal</x-ui.button>
            <x-ui.button type="submit" class="flex-1">Oluştur</x-ui.button>
        </div>
    </form>
</x-ui.modal>

{{-- Ürün Düzenle Modal --}}
<x-ui.modal name="editProductModal" title="Ürün Düzenle" size="md">
    <form id="editProductForm" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')
        <input type="hidden" name="category_id" id="edit_product_category_id">
        <x-form.input name="name" id="edit_prod_name" label="Ürün Adı" required />
        <x-form.textarea name="description" id="edit_prod_desc" label="Açıklama" :rows="3" />
        <x-form.input type="number" name="price" id="edit_prod_price" label="Fiyat (TL)" step="0.01" min="0" required />
        <x-form.input type="file" name="image" label="Resim (değiştirmek için seçin)" accept="image/*" />
        <div class="flex gap-4">
            <x-form.checkbox name="is_active" id="edit_prod_active" value="1" label="Aktif" />
            <x-form.checkbox name="in_stock" id="edit_prod_stock" value="1" label="Stokta Var" />
        </div>

        <div class="flex gap-3 pt-4">
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', 'editProductModal')" class="flex-1">İptal</x-ui.button>
            <x-ui.button type="submit" class="flex-1">Güncelle</x-ui.button>
        </div>
    </form>
</x-ui.modal>

@push('scripts')
<script>
function showCreateCategoryModal() {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'createCategoryModal' }));
}

function showEditCategoryModal(id, name, description, order, isActive) {
    document.getElementById('editCategoryForm').action = `/categories/${id}`;
    document.getElementById('deleteCategoryForm').action = `/categories/${id}`;
    document.getElementById('edit_cat_name').value = name;
    document.getElementById('edit_cat_desc').value = description || '';
    document.getElementById('edit_cat_order').value = order;
    document.getElementById('edit_cat_active').checked = isActive;
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'editCategoryModal' }));
}

function showCreateProductModal(categoryId) {
    document.getElementById('create_product_category_id').value = categoryId;
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'createProductModal' }));
}

function showEditProductModal(id, categoryId, name, description, price, isActive, inStock) {
    document.getElementById('editProductForm').action = `/products/${id}`;
    document.getElementById('edit_product_category_id').value = categoryId;
    document.getElementById('edit_prod_name').value = name;
    document.getElementById('edit_prod_desc').value = description || '';
    document.getElementById('edit_prod_price').value = price;
    document.getElementById('edit_prod_active').checked = isActive;
    document.getElementById('edit_prod_stock').checked = inStock;
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'editProductModal' }));
}
</script>
@endpush
@endsection
