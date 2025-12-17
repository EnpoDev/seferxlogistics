@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Menü Yönetimi</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Menü ürünlerinizi düzenleyin</p>
        </div>
        <button onclick="showCreateCategoryModal()" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
            + Yeni Kategori
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

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Kategoriler -->
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-4">
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
                    <button onclick="showEditCategoryModal({{ $category->id }}, '{{ $category->name }}', '{{ $category->description }}', {{ $category->order }}, {{ $category->is_active ? 'true' : 'false' }})"
                        class="p-2 text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white">
                        ✏️
                    </button>
                </div>
                @empty
                <p class="text-sm text-gray-600 dark:text-gray-400">Kategori bulunamadı</p>
                @endforelse
            </div>
        </div>

        <!-- Ürünler -->
        <div class="lg:col-span-3">
            <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6">
                @if($selectedCategory)
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-black dark:text-white">{{ $selectedCategory->name }}</h3>
                    <button onclick="showCreateProductModal({{ $selectedCategory->id }})" 
                        class="px-3 py-1 text-sm bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                        + Ürün Ekle
                    </button>
                </div>
                <div class="space-y-3">
                    @forelse($selectedCategory->products as $product)
                    <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-800 rounded-lg">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-black dark:text-white">{{ $product->name }}</p>
                                @if(!$product->is_active)
                                <span class="px-2 py-0.5 text-xs bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded">Pasif</span>
                                @endif
                                @if(!$product->in_stock)
                                <span class="px-2 py-0.5 text-xs bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded">Stokta Yok</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $product->description }}</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-lg font-semibold text-black dark:text-white">₺{{ number_format($product->price, 2) }}</span>
                            <button onclick="showEditProductModal({{ $product->id }}, {{ $product->category_id }}, '{{ $product->name }}', '{{ $product->description }}', {{ $product->price }}, {{ $product->is_active ? 'true' : 'false' }}, {{ $product->in_stock ? 'true' : 'false' }})" 
                                class="text-black dark:text-white hover:opacity-60">Düzenle</button>
                            <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline" 
                                onsubmit="return confirm('Bu ürünü silmek istediğinize emin misiniz?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 dark:text-red-400 hover:opacity-60">Sil</button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-gray-600 dark:text-gray-400">
                        Bu kategoride henüz ürün yok
                    </div>
                    @endforelse
                </div>
                @else
                <div class="text-center py-8 text-gray-600 dark:text-gray-400">
                    Bir kategori seçin veya yeni kategori oluşturun
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Create Category Modal -->
<div id="createCategoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Yeni Kategori</h3>
        <form action="{{ route('categories.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Kategori Adı *</label>
                    <input type="text" name="name" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Açıklama</label>
                    <textarea name="description" rows="3" 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Sıralama</label>
                    <input type="number" name="order" value="0" min="0" 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" checked 
                            class="mr-2 rounded border-gray-300 dark:border-gray-700">
                        <span class="text-sm text-black dark:text-white">Aktif</span>
                    </label>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="submit" 
                    class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                    Oluştur
                </button>
                <button type="button" onclick="closeCategoryModal()" 
                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">
                    İptal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Kategori Düzenle</h3>
        <form id="editCategoryForm" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Kategori Adı *</label>
                    <input type="text" name="name" id="edit_cat_name" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Açıklama</label>
                    <textarea name="description" id="edit_cat_desc" rows="3" 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Sıralama</label>
                    <input type="number" name="order" id="edit_cat_order" min="0" 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" id="edit_cat_active" value="1" 
                            class="mr-2 rounded border-gray-300 dark:border-gray-700">
                        <span class="text-sm text-black dark:text-white">Aktif</span>
                    </label>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="submit" 
                    class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                    Güncelle
                </button>
                <button type="button" onclick="closeEditCategoryModal()" 
                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">
                    İptal
                </button>
            </div>
        </form>
        <form id="deleteCategoryForm" method="POST" class="mt-4" onsubmit="return confirm('Bu kategoriyi silmek istediğinize emin misiniz?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                Kategoriyi Sil
            </button>
        </form>
    </div>
</div>

<!-- Create Product Modal -->
<div id="createProductModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Yeni Ürün</h3>
        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="space-y-4">
                <input type="hidden" name="category_id" id="create_product_category_id">
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Ürün Adı *</label>
                    <input type="text" name="name" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Açıklama</label>
                    <textarea name="description" rows="3" 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Fiyat (₺) *</label>
                    <input type="number" name="price" step="0.01" min="0" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Resim</label>
                    <input type="file" name="image" accept="image/*" 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" checked 
                            class="mr-2 rounded border-gray-300 dark:border-gray-700">
                        <span class="text-sm text-black dark:text-white">Aktif</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="in_stock" value="1" checked 
                            class="mr-2 rounded border-gray-300 dark:border-gray-700">
                        <span class="text-sm text-black dark:text-white">Stokta Var</span>
                    </label>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="submit" 
                    class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                    Oluştur
                </button>
                <button type="button" onclick="closeProductModal()" 
                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">
                    İptal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Ürün Düzenle</h3>
        <form id="editProductForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <input type="hidden" name="category_id" id="edit_product_category_id">
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Ürün Adı *</label>
                    <input type="text" name="name" id="edit_prod_name" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Açıklama</label>
                    <textarea name="description" id="edit_prod_desc" rows="3" 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Fiyat (₺) *</label>
                    <input type="number" name="price" id="edit_prod_price" step="0.01" min="0" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Resim (değiştirmek için seçin)</label>
                    <input type="file" name="image" accept="image/*" 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" id="edit_prod_active" value="1" 
                            class="mr-2 rounded border-gray-300 dark:border-gray-700">
                        <span class="text-sm text-black dark:text-white">Aktif</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="in_stock" id="edit_prod_stock" value="1" 
                            class="mr-2 rounded border-gray-300 dark:border-gray-700">
                        <span class="text-sm text-black dark:text-white">Stokta Var</span>
                    </label>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="submit" 
                    class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                    Güncelle
                </button>
                <button type="button" onclick="closeEditProductModal()" 
                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">
                    İptal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Category Modals
function showCreateCategoryModal() {
    document.getElementById('createCategoryModal').classList.remove('hidden');
}

function closeCategoryModal() {
    document.getElementById('createCategoryModal').classList.add('hidden');
}

function showEditCategoryModal(id, name, description, order, isActive) {
    document.getElementById('editCategoryForm').action = `/categories/${id}`;
    document.getElementById('deleteCategoryForm').action = `/categories/${id}`;
    document.getElementById('edit_cat_name').value = name;
    document.getElementById('edit_cat_desc').value = description || '';
    document.getElementById('edit_cat_order').value = order;
    document.getElementById('edit_cat_active').checked = isActive;
    document.getElementById('editCategoryModal').classList.remove('hidden');
}

function closeEditCategoryModal() {
    document.getElementById('editCategoryModal').classList.add('hidden');
}

// Product Modals
function showCreateProductModal(categoryId) {
    document.getElementById('create_product_category_id').value = categoryId;
    document.getElementById('createProductModal').classList.remove('hidden');
}

function closeProductModal() {
    document.getElementById('createProductModal').classList.add('hidden');
}

function showEditProductModal(id, categoryId, name, description, price, isActive, inStock) {
    document.getElementById('editProductForm').action = `/products/${id}`;
    document.getElementById('edit_product_category_id').value = categoryId;
    document.getElementById('edit_prod_name').value = name;
    document.getElementById('edit_prod_desc').value = description || '';
    document.getElementById('edit_prod_price').value = price;
    document.getElementById('edit_prod_active').checked = isActive;
    document.getElementById('edit_prod_stock').checked = inStock;
    document.getElementById('editProductModal').classList.remove('hidden');
}

function closeEditProductModal() {
    document.getElementById('editProductModal').classList.add('hidden');
}
</script>
@endsection
