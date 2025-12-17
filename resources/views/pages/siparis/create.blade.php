@extends('layouts.app')

@section('content')
<div class="h-[calc(100vh-7rem)] flex" x-data="orderForm()">
    <!-- Left Panel - Categories & Products -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Category Tabs -->
        <div class="bg-white dark:bg-[#1a1a1a] border-b border-gray-200 dark:border-gray-800 px-4 py-3">
            <div class="flex items-center space-x-2 overflow-x-auto pb-1 scrollbar-hide">
                <button @click="selectedCategory = null" 
                    :class="selectedCategory === null ? 'bg-black dark:bg-white text-white dark:text-black' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-colors flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    <span>Tümü</span>
                </button>
                @foreach($categories as $category)
                <button @click="selectedCategory = {{ $category->id }}" 
                    :class="selectedCategory === {{ $category->id }} ? 'bg-black dark:bg-white text-white dark:text-black' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-colors flex items-center space-x-2">
                    @if($category->icon)
                    <span>{{ $category->icon }}</span>
                    @endif
                    <span>{{ $category->name }}</span>
                    <span class="text-xs opacity-70">({{ $category->products->count() }})</span>
                </button>
                @endforeach
            </div>
        </div>

        <!-- Products Grid -->
        <div class="flex-1 overflow-y-auto p-4 bg-gray-50 dark:bg-[#0f0f0f]">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @foreach($categories as $category)
                    @foreach($category->products as $product)
                    <div x-show="selectedCategory === null || selectedCategory === {{ $category->id }}"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        @click="addToCart({{ json_encode([
                            'id' => $product->id,
                            'name' => $product->name,
                            'price' => $product->getCurrentPrice(),
                            'category' => $category->name,
                            'image' => $product->image,
                            'restaurant' => $product->restaurant?->name
                        ]) }})"
                        class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden cursor-pointer hover:shadow-lg hover:scale-[1.02] transition-all group">
                        
                        <!-- Product Image -->
                        <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900 relative overflow-hidden">
                            @if($product->image)
                            <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                            @else
                            <div class="w-full h-full flex items-center justify-center">
                                <span class="text-4xl opacity-50">{{ $category->icon ?? '🍽️' }}</span>
                            </div>
                            @endif
                            
                            @if($product->hasDiscount())
                            <div class="absolute top-2 left-2 px-2 py-1 bg-red-500 text-white text-xs font-bold rounded">
                                %{{ $product->getDiscountPercentage() }}
                            </div>
                            @endif
                        </div>

                        <!-- Product Info -->
                        <div class="p-3">
                            <h4 class="font-medium text-black dark:text-white text-sm line-clamp-2 mb-1">{{ $product->name }}</h4>
                            @if($product->restaurant)
                            <p class="text-xs text-gray-500 mb-2">{{ $product->restaurant->name }}</p>
                            @endif
                            <div class="flex items-center justify-between">
                                <div>
                                    @if($product->hasDiscount())
                                    <span class="text-xs text-gray-400 line-through">₺{{ number_format($product->price, 2) }}</span>
                                    @endif
                                    <span class="font-bold text-black dark:text-white">₺{{ number_format($product->getCurrentPrice(), 2) }}</span>
                                </div>
                                <div class="w-8 h-8 bg-black dark:bg-white rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <svg class="w-4 h-4 text-white dark:text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @endforeach
            </div>

            <!-- Empty State -->
            @if($categories->flatMap->products->isEmpty())
            <div class="flex flex-col items-center justify-center h-full">
                <svg class="w-20 h-20 text-gray-300 dark:text-gray-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">Henüz ürün eklenmemiş</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Right Panel - Cart & Customer -->
    <div class="w-96 bg-white dark:bg-[#1a1a1a] border-l border-gray-200 dark:border-gray-800 flex flex-col">
        <!-- Customer Info Section -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Müşteri Bilgileri</h3>
            
            <!-- Phone Search -->
            <div class="relative mb-3">
                <input type="text" 
                    x-model="customerPhone" 
                    @input.debounce.500ms="searchCustomer()"
                    placeholder="Telefon numarası ile ara..."
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                <div x-show="isSearching" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="w-5 h-5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>

            <!-- Customer Found Badge -->
            <div x-show="customer" x-cloak class="mb-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-green-700 dark:text-green-400">Kayıtlı Müşteri</span>
                    </div>
                    <span class="text-xs text-green-600" x-text="customer?.total_orders + ' sipariş'"></span>
                </div>
            </div>

            <!-- Name -->
            <input type="text" 
                x-model="customerName" 
                placeholder="Müşteri adı *"
                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white mb-3">

            <!-- Address -->
            <textarea 
                x-model="customerAddress" 
                placeholder="Teslimat adresi *" 
                rows="2"
                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white mb-3 resize-none"></textarea>

            <!-- Saved Addresses -->
            <div x-show="customer?.addresses?.length > 0" x-cloak class="mb-3">
                <p class="text-xs text-gray-500 mb-2">Kayıtlı Adresler:</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="addr in customer.addresses" :key="addr.id">
                        <button type="button" 
                            @click="customerAddress = addr.full_address; lat = addr.lat; lng = addr.lng"
                            class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-xs rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                            x-text="addr.title">
                        </button>
                    </template>
                </div>
            </div>

            <!-- Restaurant & Courier Selection -->
            <div class="grid grid-cols-2 gap-3">
                <select x-model="restaurantId" class="px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white text-sm">
                    <option value="">Restoran</option>
                    @foreach($restaurants as $restaurant)
                    <option value="{{ $restaurant->id }}">{{ $restaurant->name }}</option>
                    @endforeach
                </select>
                <select x-model="courierId" class="px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white text-sm">
                    <option value="">Kurye</option>
                    @foreach($couriers as $courier)
                    <option value="{{ $courier->id }}">{{ $courier->name }} ({{ $courier->getStatusLabel() }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Auto Assign Toggle -->
            <label class="flex items-center mt-3 cursor-pointer">
                <input type="checkbox" x-model="autoAssignCourier" class="mr-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">Kurye otomatik ata</span>
            </label>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                Sepet <span x-text="'(' + cart.length + ')'"></span>
            </h3>

            <!-- Empty Cart -->
            <div x-show="cart.length === 0" class="flex flex-col items-center justify-center h-48 text-gray-400">
                <svg class="w-16 h-16 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p>Sepet boş</p>
                <p class="text-sm">Ürün eklemek için tıklayın</p>
            </div>

            <!-- Cart Items List -->
            <div class="space-y-3">
                <template x-for="(item, index) in cart" :key="index">
                    <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-black rounded-lg">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-black dark:text-white text-sm truncate" x-text="item.name"></p>
                            <p class="text-xs text-gray-500" x-text="'₺' + item.price.toFixed(2) + ' x ' + item.quantity"></p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button @click="decreaseQuantity(index)" class="w-7 h-7 bg-gray-200 dark:bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-300 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                </svg>
                            </button>
                            <span class="w-8 text-center font-medium text-black dark:text-white" x-text="item.quantity"></span>
                            <button @click="increaseQuantity(index)" class="w-7 h-7 bg-gray-200 dark:bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-300 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                            </button>
                        </div>
                        <p class="font-semibold text-black dark:text-white w-20 text-right" x-text="'₺' + (item.price * item.quantity).toFixed(2)"></p>
                    </div>
                </template>
            </div>
        </div>

        <!-- Cart Summary & Submit -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-black">
            <!-- Delivery Fee -->
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">Teslimat Ücreti</span>
                <div class="flex items-center space-x-2">
                    <span class="text-gray-500">₺</span>
                    <input type="number" x-model="deliveryFee" step="0.01" min="0"
                        class="w-20 px-2 py-1 bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-700 rounded text-right text-black dark:text-white">
                </div>
            </div>

            <!-- Subtotal -->
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">Ara Toplam</span>
                <span class="font-medium text-black dark:text-white" x-text="'₺' + subtotal.toFixed(2)"></span>
            </div>

            <!-- Total -->
            <div class="flex items-center justify-between mb-4 pt-2 border-t border-gray-200 dark:border-gray-700">
                <span class="font-semibold text-black dark:text-white">Toplam</span>
                <span class="text-xl font-bold text-black dark:text-white" x-text="'₺' + total.toFixed(2)"></span>
            </div>

            <!-- Payment Method -->
            <div class="flex items-center space-x-2 mb-4">
                <button @click="paymentMethod = 'cash'" 
                    :class="paymentMethod === 'cash' ? 'bg-black dark:bg-white text-white dark:text-black' : 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                    class="flex-1 py-2 rounded-lg text-sm font-medium transition-colors">
                    Nakit
                </button>
                <button @click="paymentMethod = 'card'" 
                    :class="paymentMethod === 'card' ? 'bg-black dark:bg-white text-white dark:text-black' : 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                    class="flex-1 py-2 rounded-lg text-sm font-medium transition-colors">
                    Kart
                </button>
                <button @click="paymentMethod = 'online'" 
                    :class="paymentMethod === 'online' ? 'bg-black dark:bg-white text-white dark:text-black' : 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                    class="flex-1 py-2 rounded-lg text-sm font-medium transition-colors">
                    Online
                </button>
            </div>

            <!-- Notes -->
            <textarea x-model="notes" placeholder="Sipariş notu (opsiyonel)" rows="2"
                class="w-full px-3 py-2 bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white text-sm mb-4 resize-none"></textarea>

            <!-- Submit Button -->
            <button @click="submitOrder()" 
                :disabled="cart.length === 0 || !customerPhone || !customerName || !customerAddress || isSubmitting"
                :class="cart.length === 0 || !customerPhone || !customerName || !customerAddress ? 'opacity-50 cursor-not-allowed' : 'hover:opacity-90'"
                class="w-full py-3 bg-black dark:bg-white text-white dark:text-black rounded-xl font-semibold transition-opacity flex items-center justify-center space-x-2">
                <template x-if="isSubmitting">
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </template>
                <span x-text="isSubmitting ? 'Oluşturuluyor...' : 'Siparişi Oluştur'"></span>
            </button>
        </div>
    </div>
</div>

<script>
function orderForm() {
    return {
        selectedCategory: null,
        cart: [],
        customerPhone: '{{ $customer?->phone ?? '' }}',
        customerName: '{{ $customer?->name ?? '' }}',
        customerAddress: '{{ $customer?->address ?? '' }}',
        customer: @json($customer),
        lat: {{ $customer?->lat ?? 'null' }},
        lng: {{ $customer?->lng ?? 'null' }},
        deliveryFee: 10,
        paymentMethod: 'cash',
        notes: '',
        restaurantId: '',
        courierId: '',
        autoAssignCourier: false,
        isSearching: false,
        isSubmitting: false,

        get subtotal() {
            return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        },

        get total() {
            return this.subtotal + parseFloat(this.deliveryFee || 0);
        },

        addToCart(product) {
            const existingIndex = this.cart.findIndex(item => item.id === product.id);
            if (existingIndex !== -1) {
                this.cart[existingIndex].quantity++;
            } else {
                this.cart.push({ ...product, quantity: 1 });
            }
        },

        increaseQuantity(index) {
            this.cart[index].quantity++;
        },

        decreaseQuantity(index) {
            if (this.cart[index].quantity > 1) {
                this.cart[index].quantity--;
            } else {
                this.cart.splice(index, 1);
            }
        },

        async searchCustomer() {
            if (this.customerPhone.length < 3) {
                this.customer = null;
                return;
            }

            this.isSearching = true;

            try {
                const response = await fetch('{{ route("musteri.search-phone") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ phone: this.customerPhone })
                });

                const data = await response.json();

                if (data.found) {
                    this.customer = data.customer;
                    this.customerName = data.customer.name;
                    this.customerAddress = data.customer.address || '';
                    this.lat = data.customer.lat;
                    this.lng = data.customer.lng;
                } else {
                    this.customer = null;
                }
            } catch (error) {
                console.error('Error searching customer:', error);
            }

            this.isSearching = false;
        },

        async submitOrder() {
            if (this.cart.length === 0 || !this.customerPhone || !this.customerName || !this.customerAddress) {
                return;
            }

            this.isSubmitting = true;

            const formData = {
                customer_name: this.customerName,
                customer_phone: this.customerPhone,
                customer_address: this.customerAddress,
                lat: this.lat,
                lng: this.lng,
                delivery_fee: this.deliveryFee,
                payment_method: this.paymentMethod,
                notes: this.notes,
                restaurant_id: this.restaurantId || null,
                courier_id: this.courierId || null,
                auto_assign_courier: this.autoAssignCourier,
                items: this.cart.map(item => ({
                    product_id: item.id,
                    quantity: item.quantity
                }))
            };

            try {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("siparis.store") }}';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
                form.appendChild(csrfInput);

                Object.keys(formData).forEach(key => {
                    if (key === 'items') {
                        formData.items.forEach((item, index) => {
                            const productInput = document.createElement('input');
                            productInput.type = 'hidden';
                            productInput.name = `items[${index}][product_id]`;
                            productInput.value = item.product_id;
                            form.appendChild(productInput);

                            const quantityInput = document.createElement('input');
                            quantityInput.type = 'hidden';
                            quantityInput.name = `items[${index}][quantity]`;
                            quantityInput.value = item.quantity;
                            form.appendChild(quantityInput);
                        });
                    } else if (formData[key] !== null && formData[key] !== '') {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = formData[key];
                        form.appendChild(input);
                    }
                });

                document.body.appendChild(form);
                form.submit();
            } catch (error) {
                console.error('Error submitting order:', error);
                this.isSubmitting = false;
            }
        }
    }
}
</script>

<style>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
[x-cloak] {
    display: none !important;
}
</style>
@endsection
