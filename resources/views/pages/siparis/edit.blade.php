@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Sipariş Düzenle: {{ $order->order_number }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Sipariş bilgilerini güncelle</p>
        </div>
        <form action="{{ route('siparis.destroy', $order) }}" method="POST" onsubmit="return confirm('Bu siparişi silmek istediğinize emin misiniz?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                Siparişi Sil
            </button>
        </form>
    </div>

    <form action="{{ route('siparis.update', $order) }}" method="POST" class="max-w-4xl">
        @csrf
        @method('PUT')

        <!-- Müşteri Bilgileri -->
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Müşteri Bilgileri</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Müşteri Adı *</label>
                    <input type="text" name="customer_name" value="{{ old('customer_name', $order->customer_name) }}" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    @error('customer_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Telefon *</label>
                    <input type="text" name="customer_phone" value="{{ old('customer_phone', $order->customer_phone) }}" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    @error('customer_phone')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Adres *</label>
                    <textarea name="customer_address" rows="3" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">{{ old('customer_address', $order->customer_address) }}</textarea>
                    @error('customer_address')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Sipariş Detayları -->
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Sipariş Detayları</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Durum *</label>
                    <select name="status" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                        <option value="pending" {{ old('status', $order->status) == 'pending' ? 'selected' : '' }}>Beklemede</option>
                        <option value="preparing" {{ old('status', $order->status) == 'preparing' ? 'selected' : '' }}>Hazırlanıyor</option>
                        <option value="ready" {{ old('status', $order->status) == 'ready' ? 'selected' : '' }}>Hazır</option>
                        <option value="on_delivery" {{ old('status', $order->status) == 'on_delivery' ? 'selected' : '' }}>Yolda</option>
                        <option value="delivered" {{ old('status', $order->status) == 'delivered' ? 'selected' : '' }}>Teslim Edildi</option>
                        <option value="cancelled" {{ old('status', $order->status) == 'cancelled' ? 'selected' : '' }}>İptal Edildi</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Şube</label>
                    <select name="branch_id" 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                        <option value="">Seçiniz...</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id', $order->branch_id) == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Kurye</label>
                    <select name="courier_id" 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                        <option value="">Seçiniz...</option>
                        @foreach($couriers as $courier)
                            <option value="{{ $courier->id }}" {{ old('courier_id', $order->courier_id) == $courier->id ? 'selected' : '' }}>
                                {{ $courier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Teslimat Ücreti (₺) *</label>
                    <input type="number" name="delivery_fee" step="0.01" min="0" value="{{ old('delivery_fee', $order->delivery_fee) }}" required 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    @error('delivery_fee')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Notlar</label>
                    <textarea name="notes" rows="2" 
                        class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">{{ old('notes', $order->notes) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Ürünler -->
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Ürünler *</h3>
            <div id="items-container" class="space-y-3 mb-4">
                @foreach($order->items as $index => $item)
                <div class="flex gap-3 items-start">
                    <div class="flex-1">
                        <select name="items[{{ $index }}][product_id]" required 
                            class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                            <option value="">Ürün seçin...</option>
                            @foreach($products as $category => $categoryProducts)
                                <optgroup label="{{ $category }}">
                                    @foreach($categoryProducts as $product)
                                        <option value="{{ $product->id }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                            ₺{{ number_format($product->price, 2) }} - {{ $product->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-24">
                        <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}" min="1" required 
                            class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" 
                        class="px-3 py-2 text-red-600 dark:text-red-400 hover:opacity-60">
                        Sil
                    </button>
                </div>
                @endforeach
            </div>
            <button type="button" id="add-item" 
                class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                + Ürün Ekle
            </button>
            @error('items')
                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit Buttons -->
        <div class="flex gap-4">
            <button type="submit" 
                class="px-6 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                Değişiklikleri Kaydet
            </button>
            <a href="{{ route('siparis.liste') }}" 
                class="px-6 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">
                İptal
            </a>
        </div>
    </form>
</div>

<script>
let itemIndex = {{ $order->items->count() }};
const products = @json($products);

document.getElementById('add-item').addEventListener('click', function() {
    addItemRow();
});

function addItemRow() {
    const container = document.getElementById('items-container');
    const row = document.createElement('div');
    row.className = 'flex gap-3 items-start';
    row.innerHTML = `
        <div class="flex-1">
            <select name="items[${itemIndex}][product_id]" required 
                class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
                <option value="">Ürün seçin...</option>
                ${Object.entries(products).map(([category, items]) => `
                    <optgroup label="${category}">
                        ${items.map(product => `
                            <option value="${product.id}">₺${product.price} - ${product.name}</option>
                        `).join('')}
                    </optgroup>
                `).join('')}
            </select>
        </div>
        <div class="w-24">
            <input type="number" name="items[${itemIndex}][quantity]" value="1" min="1" required 
                class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
        </div>
        <button type="button" onclick="this.parentElement.remove()" 
            class="px-3 py-2 text-red-600 dark:text-red-400 hover:opacity-60">
            Sil
        </button>
    `;
    container.appendChild(row);
    itemIndex++;
}
</script>
@endsection

