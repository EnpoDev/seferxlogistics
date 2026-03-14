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
                                onclick="showEditProductModal({{ $product->id }}, {{ $product->category_id }}, '{{ addslashes($product->name) }}', '{{ addslashes($product->description) }}', {{ $product->price }}, {{ $product->is_active ? 'true' : 'false' }}, {{ $product->in_stock ? 'true' : 'false' }}, {{ json_encode($product->optionGroups ?? []) }})">
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
<x-ui.modal name="createProductModal" title="Yeni Ürün" size="lg">
    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4" x-data="productOptionManager()">
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

        {{-- Varyasyon Gruplari --}}
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <span class="text-sm font-medium text-black dark:text-white">Varyasyonlar</span>
                    <span class="text-xs text-gray-400 ml-1" x-text="groups.length > 0 ? '(' + groups.length + ' grup)' : ''"></span>
                </div>
                <button type="button" @click="addGroup()" class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Grup Ekle
                </button>
            </div>
            <template x-for="(group, gi) in groups" :key="gi">
                <div class="mb-3 p-3 bg-gray-50 dark:bg-black rounded-lg border border-gray-200 dark:border-gray-700">
                    {{-- Group Header --}}
                    <div class="flex items-center gap-2 mb-2">
                        <input type="text" :name="'option_groups['+gi+'][name]'" x-model="group.name" placeholder="Grup adi (Porsiyon, Ekstra...)"
                            class="flex-1 px-3 py-1.5 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white text-sm">
                        <select :name="'option_groups['+gi+'][type]'" x-model="group.type" @change="onGroupTypeChange(gi)"
                            class="px-2 py-1.5 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white text-xs">
                            <option value="radio">Tekli (Radio)</option>
                            <option value="checkbox">Coklu (Checkbox)</option>
                        </select>
                        <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            <input type="checkbox" :name="'option_groups['+gi+'][required]'" value="1" x-model="group.required" class="rounded border-gray-300"> Zorunlu
                        </label>
                        {{-- Move Up/Down --}}
                        <button type="button" x-show="gi > 0" @click="moveGroup(gi, -1)" class="p-1 text-gray-400 hover:text-gray-600" title="Yukari">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                        </button>
                        <button type="button" x-show="gi < groups.length - 1" @click="moveGroup(gi, 1)" class="p-1 text-gray-400 hover:text-gray-600" title="Asagi">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <button type="button" @click="removeGroup(gi)" class="p-1 text-red-400 hover:text-red-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    {{-- Selection limits (for checkbox type) --}}
                    <div x-show="group.type === 'checkbox'" class="flex items-center gap-3 mb-2 pl-1">
                        <label class="flex items-center gap-1 text-xs text-gray-500">
                            Min:
                            <input type="number" :name="'option_groups['+gi+'][min_selections]'" x-model.number="group.min_selections" min="0" step="1"
                                class="w-14 px-1.5 py-0.5 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded text-black dark:text-white text-xs text-center">
                        </label>
                        <label class="flex items-center gap-1 text-xs text-gray-500">
                            Max:
                            <input type="number" :name="'option_groups['+gi+'][max_selections]'" x-model.number="group.max_selections" min="0" step="1"
                                class="w-14 px-1.5 py-0.5 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded text-black dark:text-white text-xs text-center">
                        </label>
                    </div>
                    {{-- Options --}}
                    <template x-for="(opt, oi) in group.options" :key="oi">
                        <div class="flex items-center gap-2 mb-1">
                            <input type="text" :name="'option_groups['+gi+'][options]['+oi+'][name]'" x-model="opt.name" placeholder="Secenek adi"
                                class="flex-1 px-2 py-1 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded text-black dark:text-white text-xs">
                            <div class="flex items-center gap-1">
                                <input type="number" :name="'option_groups['+gi+'][options]['+oi+'][price_modifier]'" x-model.number="opt.price_modifier" step="0.01" placeholder="0"
                                    class="w-20 px-2 py-1 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded text-black dark:text-white text-xs text-right">
                                <span class="text-xs text-gray-400">TL</span>
                            </div>
                            <label class="flex items-center gap-1 text-xs text-gray-500 whitespace-nowrap" title="Varsayilan">
                                <input type="radio" :name="'option_groups_default_'+gi" :checked="opt.is_default" @change="setDefault(gi, oi)" class="text-xs"> Vars.
                            </label>
                            {{-- Move option up/down --}}
                            <button type="button" x-show="oi > 0" @click="moveOption(gi, oi, -1)" class="p-0.5 text-gray-400 hover:text-gray-600">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            </button>
                            <button type="button" x-show="oi < group.options.length - 1" @click="moveOption(gi, oi, 1)" class="p-0.5 text-gray-400 hover:text-gray-600">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <button type="button" @click="removeOption(gi, oi)" class="text-red-400 hover:text-red-600">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                    <button type="button" @click="addOption(gi)" class="text-xs text-blue-500 hover:text-blue-700 mt-1 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Secenek Ekle
                    </button>
                </div>
            </template>
            <div x-show="groups.length === 0" class="text-center py-3 text-xs text-gray-400 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                Varyasyon grubu yok. "Grup Ekle" ile porsiyon, ekstra secenekleri tanimlayabilirsiniz.
            </div>
        </div>

        <div class="flex gap-3 pt-4">
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', 'createProductModal')" class="flex-1">Iptal</x-ui.button>
            <x-ui.button type="submit" class="flex-1">Olustur</x-ui.button>
        </div>
    </form>
</x-ui.modal>

{{-- Urun Duzenle Modal --}}
<x-ui.modal name="editProductModal" title="Urun Duzenle" size="lg">
    <form id="editProductForm" method="POST" enctype="multipart/form-data" class="space-y-4" x-data="productOptionManager(true)">
        @csrf
        @method('PUT')
        <input type="hidden" name="category_id" id="edit_product_category_id">
        <x-form.input name="name" id="edit_prod_name" label="Urun Adi" required />
        <x-form.textarea name="description" id="edit_prod_desc" label="Aciklama" :rows="3" />
        <x-form.input type="number" name="price" id="edit_prod_price" label="Fiyat (TL)" step="0.01" min="0" required />
        <x-form.input type="file" name="image" label="Resim (degistirmek icin secin)" accept="image/*" />
        <div class="flex gap-4">
            <x-form.checkbox name="is_active" id="edit_prod_active" value="1" label="Aktif" />
            <x-form.checkbox name="in_stock" id="edit_prod_stock" value="1" label="Stokta Var" />
        </div>

        {{-- Varyasyon Gruplari --}}
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <span class="text-sm font-medium text-black dark:text-white">Varyasyonlar</span>
                    <span class="text-xs text-gray-400 ml-1" x-text="groups.length > 0 ? '(' + groups.length + ' grup)' : ''"></span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="saveVariationsAjax()" x-show="productId" class="px-3 py-1 text-xs bg-green-500 text-white rounded-lg hover:bg-green-600 flex items-center gap-1" :disabled="savingVariations">
                        <svg x-show="!savingVariations" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <svg x-show="savingVariations" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="savingVariations ? 'Kaydediliyor...' : 'Varyasyonlari Kaydet'"></span>
                    </button>
                    <button type="button" @click="addGroup()" class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Grup Ekle
                    </button>
                </div>
            </div>
            {{-- Variation save feedback --}}
            <div x-show="variationMessage" x-cloak x-transition class="mb-3 px-3 py-2 rounded-lg text-xs"
                 :class="variationMessageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800'"
                 x-text="variationMessage"></div>
            <template x-for="(group, gi) in groups" :key="gi">
                <div class="mb-3 p-3 bg-gray-50 dark:bg-black rounded-lg border border-gray-200 dark:border-gray-700">
                    {{-- Group Header --}}
                    <div class="flex items-center gap-2 mb-2">
                        <input type="text" :name="'option_groups['+gi+'][name]'" x-model="group.name" placeholder="Grup adi (Porsiyon, Ekstra...)"
                            class="flex-1 px-3 py-1.5 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white text-sm">
                        <select :name="'option_groups['+gi+'][type]'" x-model="group.type" @change="onGroupTypeChange(gi)"
                            class="px-2 py-1.5 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white text-xs">
                            <option value="radio">Tekli (Radio)</option>
                            <option value="checkbox">Coklu (Checkbox)</option>
                        </select>
                        <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            <input type="checkbox" :name="'option_groups['+gi+'][required]'" value="1" x-model="group.required" class="rounded border-gray-300"> Zorunlu
                        </label>
                        {{-- Move Up/Down --}}
                        <button type="button" x-show="gi > 0" @click="moveGroup(gi, -1)" class="p-1 text-gray-400 hover:text-gray-600" title="Yukari">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                        </button>
                        <button type="button" x-show="gi < groups.length - 1" @click="moveGroup(gi, 1)" class="p-1 text-gray-400 hover:text-gray-600" title="Asagi">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <button type="button" @click="removeGroup(gi)" class="p-1 text-red-400 hover:text-red-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    {{-- Selection limits (for checkbox type) --}}
                    <div x-show="group.type === 'checkbox'" class="flex items-center gap-3 mb-2 pl-1">
                        <label class="flex items-center gap-1 text-xs text-gray-500">
                            Min:
                            <input type="number" :name="'option_groups['+gi+'][min_selections]'" x-model.number="group.min_selections" min="0" step="1"
                                class="w-14 px-1.5 py-0.5 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded text-black dark:text-white text-xs text-center">
                        </label>
                        <label class="flex items-center gap-1 text-xs text-gray-500">
                            Max:
                            <input type="number" :name="'option_groups['+gi+'][max_selections]'" x-model.number="group.max_selections" min="0" step="1"
                                class="w-14 px-1.5 py-0.5 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded text-black dark:text-white text-xs text-center">
                        </label>
                    </div>
                    {{-- Options --}}
                    <template x-for="(opt, oi) in group.options" :key="oi">
                        <div class="flex items-center gap-2 mb-1">
                            <input type="text" :name="'option_groups['+gi+'][options]['+oi+'][name]'" x-model="opt.name" placeholder="Secenek adi"
                                class="flex-1 px-2 py-1 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded text-black dark:text-white text-xs">
                            <div class="flex items-center gap-1">
                                <input type="number" :name="'option_groups['+gi+'][options]['+oi+'][price_modifier]'" x-model.number="opt.price_modifier" step="0.01" placeholder="0"
                                    class="w-20 px-2 py-1 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-700 rounded text-black dark:text-white text-xs text-right">
                                <span class="text-xs text-gray-400">TL</span>
                            </div>
                            <label class="flex items-center gap-1 text-xs text-gray-500 whitespace-nowrap" title="Varsayilan">
                                <input type="radio" :name="'option_groups_default_'+gi" :checked="opt.is_default" @change="setDefault(gi, oi)" class="text-xs"> Vars.
                            </label>
                            {{-- Move option up/down --}}
                            <button type="button" x-show="oi > 0" @click="moveOption(gi, oi, -1)" class="p-0.5 text-gray-400 hover:text-gray-600">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            </button>
                            <button type="button" x-show="oi < group.options.length - 1" @click="moveOption(gi, oi, 1)" class="p-0.5 text-gray-400 hover:text-gray-600">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <button type="button" @click="removeOption(gi, oi)" class="text-red-400 hover:text-red-600">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                    <button type="button" @click="addOption(gi)" class="text-xs text-blue-500 hover:text-blue-700 mt-1 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Secenek Ekle
                    </button>
                </div>
            </template>
            <div x-show="groups.length === 0" class="text-center py-3 text-xs text-gray-400 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                Varyasyon grubu yok. "Grup Ekle" ile porsiyon, ekstra secenekleri tanimlayabilirsiniz.
            </div>
        </div>

        <div class="flex gap-3 pt-4">
            <x-ui.button type="button" variant="secondary" @click="$dispatch('close-modal', 'editProductModal')" class="flex-1">Iptal</x-ui.button>
            <x-ui.button type="submit" class="flex-1">Guncelle</x-ui.button>
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

function productOptionManager(isEditMode = false) {
    return {
        groups: [],
        productId: null,
        savingVariations: false,
        variationMessage: '',
        variationMessageType: 'success',

        init() {
            if (isEditMode) {
                // Listen for load-option-groups event from showEditProductModal
                window.addEventListener('load-option-groups', (e) => {
                    this.loadGroups(e.detail.groups || e.detail || []);
                    this.productId = e.detail.productId || null;
                    this.variationMessage = '';
                });
            }
        },

        addGroup() {
            this.groups.push({
                name: '',
                type: 'radio',
                required: true,
                min_selections: 1,
                max_selections: 1,
                options: [{ name: '', price_modifier: 0, is_default: true }]
            });
        },

        removeGroup(gi) {
            this.groups.splice(gi, 1);
        },

        addOption(gi) {
            this.groups[gi].options.push({ name: '', price_modifier: 0, is_default: false });
        },

        removeOption(gi, oi) {
            this.groups[gi].options.splice(oi, 1);
        },

        setDefault(gi, oi) {
            // Only one default per group
            this.groups[gi].options.forEach((opt, i) => {
                opt.is_default = (i === oi);
            });
        },

        onGroupTypeChange(gi) {
            const group = this.groups[gi];
            if (group.type === 'radio') {
                group.min_selections = 1;
                group.max_selections = 1;
            } else {
                group.min_selections = 0;
                group.max_selections = group.options.length || 3;
            }
        },

        moveGroup(gi, direction) {
            const newIndex = gi + direction;
            if (newIndex < 0 || newIndex >= this.groups.length) return;
            const temp = this.groups[gi];
            this.groups.splice(gi, 1);
            this.groups.splice(newIndex, 0, temp);
        },

        moveOption(gi, oi, direction) {
            const opts = this.groups[gi].options;
            const newIndex = oi + direction;
            if (newIndex < 0 || newIndex >= opts.length) return;
            const temp = opts[oi];
            opts.splice(oi, 1);
            opts.splice(newIndex, 0, temp);
        },

        loadGroups(optionGroups) {
            this.groups = (optionGroups || []).map(g => ({
                name: g.name || '',
                type: g.type || 'radio',
                required: g.required ?? g.is_required ?? true,
                min_selections: g.min_selections ?? 1,
                max_selections: g.max_selections ?? 1,
                options: (g.options || []).map(o => ({
                    name: o.name || '',
                    price_modifier: o.price_modifier ?? o.price_diff ?? 0,
                    is_default: o.is_default ?? false
                }))
            }));
        },

        buildPayload() {
            return {
                groups: this.groups.map(g => ({
                    name: g.name,
                    type: g.type,
                    required: g.required,
                    min_selections: g.min_selections,
                    max_selections: g.max_selections,
                    options: g.options.map(o => ({
                        name: o.name,
                        price_modifier: parseFloat(o.price_modifier) || 0,
                        is_default: o.is_default || false
                    }))
                }))
            };
        },

        async saveVariationsAjax() {
            if (!this.productId) return;

            // Validate
            for (const group of this.groups) {
                if (!group.name.trim()) {
                    this.variationMessage = 'Tum gruplara isim verilmelidir.';
                    this.variationMessageType = 'error';
                    return;
                }
                if (group.options.length === 0) {
                    this.variationMessage = '"' + group.name + '" grubunda en az bir secenek olmalidir.';
                    this.variationMessageType = 'error';
                    return;
                }
                for (const opt of group.options) {
                    if (!opt.name.trim()) {
                        this.variationMessage = '"' + group.name + '" grubundaki tum seceneklere isim verilmelidir.';
                        this.variationMessageType = 'error';
                        return;
                    }
                }
            }

            this.savingVariations = true;
            this.variationMessage = '';

            try {
                const response = await fetch(`/urunler/${this.productId}/varyasyonlar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.buildPayload())
                });

                const result = await response.json();
                if (response.ok && (result.success !== false)) {
                    this.variationMessage = result.message || 'Varyasyonlar basariyla kaydedildi.';
                    this.variationMessageType = 'success';
                    // Update loaded groups from response if provided
                    if (result.groups) {
                        this.loadGroups(result.groups);
                    }
                } else {
                    this.variationMessage = result.message || 'Varyasyonlar kaydedilemedi.';
                    this.variationMessageType = 'error';
                }
            } catch (error) {
                console.error('Variation save error:', error);
                this.variationMessage = 'Bir hata olustu. Lutfen tekrar deneyin.';
                this.variationMessageType = 'error';
            }

            this.savingVariations = false;
            // Auto-hide message after 5 seconds
            setTimeout(() => { this.variationMessage = ''; }, 5000);
        },

        async loadVariationsFromApi(productId) {
            this.productId = productId;
            try {
                const response = await fetch(`/urunler/${productId}/varyasyonlar`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (response.ok) {
                    const data = await response.json();
                    this.loadGroups(data.groups || data || []);
                }
            } catch (error) {
                console.error('Variation load error:', error);
            }
        }
    };
}

function showCreateProductModal(categoryId) {
    document.getElementById('create_product_category_id').value = categoryId;
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'createProductModal' }));
}

function showEditProductModal(id, categoryId, name, description, price, isActive, inStock, optionGroups) {
    document.getElementById('editProductForm').action = `/products/${id}`;
    document.getElementById('edit_product_category_id').value = categoryId;
    document.getElementById('edit_prod_name').value = name;
    document.getElementById('edit_prod_desc').value = description || '';
    document.getElementById('edit_prod_price').value = price;
    document.getElementById('edit_prod_active').checked = isActive;
    document.getElementById('edit_prod_stock').checked = inStock;
    // Load option groups into the edit form's Alpine component
    window.dispatchEvent(new CustomEvent('load-option-groups', { detail: { groups: optionGroups || [], productId: id } }));
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'editProductModal' }));
}
</script>
@endpush
@endsection
