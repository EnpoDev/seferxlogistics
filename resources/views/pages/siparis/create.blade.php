@extends('layouts.app')

@section('content')
<div class="h-[calc(100vh-7rem)] flex" x-data="orderForm()">
    @if ($errors->any())
    <div class="fixed top-20 right-4 z-50 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded shadow-lg" role="alert">
        <strong class="font-bold">Hata!</strong>
        <span class="block sm:inline">Lütfen formu kontrol ediniz.</span>
        <ul class="mt-2 list-disc list-inside text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
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
                            'restaurant' => $product->restaurant?->name,
                            'option_groups' => $product->optionGroups ? $product->optionGroups->map(fn($g) => [
                                'name' => $g->name,
                                'type' => $g->type,
                                'is_required' => $g->required,
                                'max_selections' => $g->max_selections,
                                'options' => $g->options->map(fn($o) => ['name' => $o->name, 'price_diff' => $o->price_modifier])
                            ]) : [],
                            'variations' => $product->variations ?? [],
                            'extras' => $product->extras ?? [],
                            'removables' => $product->removables ?? []
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

            <!-- Mahalle (Zone) Secimi -->
            <div class="mb-3">
                <select x-model="zoneId" @change="onZoneChange()" class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white text-sm">
                    <option value="">Mahalle Seciniz</option>
                    @foreach($zones as $zone)
                    <option value="{{ $zone->id }}">{{ $zone->name }} @if($zone->delivery_fee) ({{ number_format($zone->delivery_fee, 2) }} TL) @endif</option>
                    @endforeach
                </select>
                <!-- Zone Info Badge -->
                <div x-show="zoneInfo" x-cloak class="mt-2 p-2 rounded-lg text-xs" :class="zoneInfo?.canAccept ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400'">
                    <div class="flex items-center justify-between">
                        <span x-text="zoneInfo?.label"></span>
                        <span x-show="zoneInfo?.remaining !== null" x-text="'Kalan: ' + zoneInfo?.remaining + ' siparis'"></span>
                    </div>
                    <div x-show="zoneInfo?.estimatedMinutes" class="mt-1 text-gray-500 dark:text-gray-400">
                        Tahmini teslimat: <span x-text="zoneInfo?.estimatedMinutes + ' dk'" class="font-medium"></span>
                    </div>
                </div>
            </div>

            <!-- Saved Addresses -->
            <div x-show="customer?.addresses?.length > 0" x-cloak class="mb-3">
                <p class="text-xs text-gray-500 mb-2">Kayıtlı Adresler:</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="addr in (customer?.addresses ?? [])" :key="addr.id">
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
                            <p x-show="item.optionLabels && item.optionLabels.length > 0" class="text-xs text-purple-500 dark:text-purple-400" x-text="item.optionLabels.join(', ')"></p>
                            <p x-show="!item.optionLabels?.length && item.variation" class="text-xs text-purple-500 dark:text-purple-400" x-text="item.variation"></p>
                            <p x-show="item.extras && item.extras.length > 0" class="text-xs text-green-500 dark:text-green-400" x-text="getExtrasLabel(item.extras)"></p>
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
            <div class="mb-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Odeme Yontemi</p>
                    <label class="flex items-center space-x-1 cursor-pointer">
                        <input type="checkbox" x-model="splitPayment" @change="onSplitPaymentToggle()" class="rounded text-xs">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Parcali Odeme</span>
                    </label>
                </div>

                <!-- Single Payment Mode -->
                <div x-show="!splitPayment">
                    <div class="grid grid-cols-3 gap-2 mb-2">
                        <template x-for="method in paymentMethods" :key="method.key">
                            <button type="button" @click="paymentMethod = method.key"
                                :class="paymentMethod === method.key ? 'bg-black dark:bg-white text-white dark:text-black' : 'bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                                class="py-2 rounded-lg font-medium transition-colors"
                                :style="method.isMealCard ? 'font-size: 0.7rem' : 'font-size: 0.875rem'"
                                x-text="method.label">
                            </button>
                        </template>
                    </div>

                    <!-- Meal Card Info -->
                    <div x-show="isMealCardSelected" x-cloak class="mt-2 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                        <p class="text-xs text-amber-700 dark:text-amber-400 font-medium mb-1" x-text="getSelectedMethodLabel() + ' ile odeme'"></p>
                        <p class="text-xs text-amber-600 dark:text-amber-500">Kapida yemek karti ile tahsil edilecektir.</p>
                    </div>
                </div>

                <!-- Split Payment Mode -->
                <div x-show="splitPayment" x-cloak>
                    <div class="space-y-2 mb-2">
                        <template x-for="(sp, spIndex) in splitPayments" :key="spIndex">
                            <div class="flex items-center space-x-2 p-2 bg-gray-50 dark:bg-black rounded-lg">
                                <select x-model="sp.method" class="flex-1 px-2 py-1.5 bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-700 rounded text-sm text-black dark:text-white">
                                    <template x-for="method in paymentMethods" :key="method.key">
                                        <option :value="method.key" x-text="method.label"></option>
                                    </template>
                                </select>
                                <div class="flex items-center space-x-1">
                                    <span class="text-gray-500 text-sm">TL</span>
                                    <input type="number" x-model.number="sp.amount" step="0.01" min="0" :max="total"
                                        @input="onSplitAmountChange(spIndex)"
                                        class="w-20 px-2 py-1.5 bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-700 rounded text-right text-sm text-black dark:text-white">
                                </div>
                                <button type="button" x-show="splitPayments.length > 2" @click="removeSplitPayment(spIndex)" class="p-1 text-red-500 hover:text-red-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                    <div class="flex items-center justify-between">
                        <button type="button" @click="addSplitPayment()" class="text-xs text-blue-600 dark:text-blue-400 font-medium">+ Yontem Ekle</button>
                        <span class="text-xs" :class="splitPaymentRemaining === 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                              x-text="splitPaymentRemaining === 0 ? 'Tam eslesti' : 'Kalan: ' + splitPaymentRemaining.toFixed(2) + ' TL'"></span>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <textarea x-model="notes" placeholder="Sipariş notu (opsiyonel)" rows="2"
                class="w-full px-3 py-2 bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white text-sm mb-4 resize-none"></textarea>

            <!-- Print Option -->
            <div class="flex items-center justify-between mb-4 p-3 bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span class="text-sm text-gray-700 dark:text-gray-300">Yazdir</span>
                </div>
                <div class="flex items-center space-x-3">
                    <button type="button" @click="printMode = 'auto'"
                        :class="printMode === 'auto' ? 'bg-black dark:bg-white text-white dark:text-black' : 'bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-400'"
                        class="px-3 py-1 rounded text-xs font-medium transition-colors">
                        Otomatik
                    </button>
                    <button type="button" @click="printMode = 'manual'"
                        :class="printMode === 'manual' ? 'bg-black dark:bg-white text-white dark:text-black' : 'bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-400'"
                        class="px-3 py-1 rounded text-xs font-medium transition-colors">
                        Manuel
                    </button>
                    <button type="button" @click="printMode = 'none'"
                        :class="printMode === 'none' ? 'bg-black dark:bg-white text-white dark:text-black' : 'bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-400'"
                        class="px-3 py-1 rounded text-xs font-medium transition-colors">
                        Yazma
                    </button>
                </div>
            </div>

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

    <!-- Variation Selection Modal (option_groups based) -->
    <div x-show="showVariationModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showVariationModal = false"></div>
            <div class="relative bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 w-full max-w-md p-6 max-h-[80vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-1" x-text="selectedProduct?.name"></h3>
                <p class="text-sm text-gray-500 mb-4" x-text="'Temel fiyat: ₺' + (selectedProduct?.price || 0).toFixed(2)"></p>

                <!-- Dynamic Option Groups -->
                <template x-for="(group, gIdx) in (selectedProduct?.option_groups || [])" :key="gIdx">
                    <div class="mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="group.name"></p>
                            <span x-show="group.is_required" class="text-xs text-red-500">*</span>
                            <span class="text-xs text-gray-400" x-text="group.type === 'single' ? '(Birini secin)' : '(Birden fazla secilebilir)'"></span>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(option, oIdx) in group.options" :key="oIdx">
                                <label class="flex items-center p-3 bg-gray-50 dark:bg-black rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors"
                                    :class="isOptionSelected(gIdx, option.name) ? 'ring-2 ring-black dark:ring-white' : ''">
                                    <!-- Single select: radio -->
                                    <template x-if="group.type === 'single'">
                                        <input type="radio" :name="'option_group_' + gIdx" :value="option.name"
                                            @change="selectSingleOption(gIdx, option.name)" :checked="isOptionSelected(gIdx, option.name)" class="mr-3">
                                    </template>
                                    <!-- Multiple select: checkbox -->
                                    <template x-if="group.type === 'multiple'">
                                        <input type="checkbox" :value="option.name"
                                            @change="toggleMultiOption(gIdx, option.name, $event.target.checked)" :checked="isOptionSelected(gIdx, option.name)" class="mr-3 rounded">
                                    </template>
                                    <span class="flex-1 text-sm text-black dark:text-white" x-text="option.name"></span>
                                    <span class="text-sm text-gray-500" x-show="option.price_diff && option.price_diff !== 0"
                                        x-text="(option.price_diff > 0 ? '+' : '') + '₺' + parseFloat(option.price_diff || 0).toFixed(2)"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- No option groups fallback (legacy variations/extras/removables) -->
                <template x-if="(!selectedProduct?.option_groups || selectedProduct?.option_groups?.length === 0) && selectedProduct?.variations?.length > 0">
                    <div class="mb-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Porsiyon</p>
                        <div class="space-y-2">
                            <template x-for="variation in selectedProduct.variations" :key="variation.name">
                                <label class="flex items-center p-3 bg-gray-50 dark:bg-black rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors"
                                       :class="selectedVariation === variation.name ? 'ring-2 ring-black dark:ring-white' : ''">
                                    <input type="radio" :value="variation.name" x-model="selectedVariation" class="mr-3">
                                    <span class="flex-1 text-sm text-black dark:text-white" x-text="variation.name"></span>
                                    <span class="text-sm text-gray-500" x-show="variation.price_diff" x-text="(variation.price_diff > 0 ? '+' : '') + '₺' + (variation.price_diff || 0).toFixed(2)"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Dynamic Price Preview -->
                <div class="pt-3 border-t border-gray-200 dark:border-gray-700 mb-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Toplam Fiyat:</span>
                        <span class="text-lg font-bold text-black dark:text-white" x-text="'₺' + getVariationPreviewPrice().toFixed(2)"></span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button" @click="showVariationModal = false" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg">Iptal</button>
                    <button type="button" @click="confirmVariation()" class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg">Sepete Ekle</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function orderForm() {
    const productsMap = {};
    @foreach($categories as $category)
        @foreach($category->products as $product)
            productsMap[{{ $product->id }}] = {
                id: {{ $product->id }},
                name: @json($product->name),
                price: {{ $product->getCurrentPrice() }},
                category: @json($category->name),
                image: @json($product->image),
                restaurant: @json($product->restaurant?->name)
            };
        @endforeach
    @endforeach

    // Zone data for dynamic info display
    const zonesData = {
        @foreach($zones as $zone)
        {{ $zone->id }}: {
            id: {{ $zone->id }},
            name: @json($zone->name),
            deliveryFee: {{ $zone->delivery_fee ?? 0 }},
            estimatedMinutes: {{ $zone->estimated_delivery_minutes ?? 'null' }},
            dailyLimit: {{ $zone->daily_order_limit ?? 'null' }},
            currentCount: {{ $zone->current_order_count ?? 0 }},
            canAccept: {{ $zone->canAcceptOrders() ? 'true' : 'false' }}
        },
        @endforeach
    };

    // Payment method definitions
    const allPaymentMethods = [
        { key: 'cash', label: 'Nakit', isMealCard: false },
        { key: 'card', label: 'Kart', isMealCard: false },
        { key: 'online', label: 'Online', isMealCard: false },
        { key: 'pluxee', label: 'Pluxee', isMealCard: true },
        { key: 'edenred', label: 'Edenred', isMealCard: true },
        { key: 'multinet', label: 'Multinet', isMealCard: true },
        { key: 'metropol', label: 'Metropol', isMealCard: true },
        { key: 'tokenflex', label: 'Tokenflex', isMealCard: true },
        { key: 'setcard', label: 'Setcard', isMealCard: true },
    ];

    return {
        selectedCategory: null,
        cart: [],
        customerPhone: @json(old('customer_phone', $customer?->phone ?? '')),
        customerName: @json(old('customer_name', $customer?->name ?? '')),
        customerAddress: @json(old('customer_address', $customer?->address ?? '')),
        customer: @json($customer),
        lat: @json(old('lat', $customer?->lat ?? null)),
        lng: @json(old('lng', $customer?->lng ?? null)),
        deliveryFee: @json(old('delivery_fee', 0)),
        paymentMethod: @json(old('payment_method', 'cash')),
        notes: @json(old('notes', '')),
        restaurantId: @json(old('restaurant_id', '')),
        courierId: @json(old('courier_id', '')),
        zoneId: @json(old('zone_id', '')),
        printMode: @json(old('print_mode', 'auto')),
        autoAssignCourier: {{ old('auto_assign_courier') ? 'true' : 'false' }},
        isSearching: false,
        isSubmitting: false,

        // Payment
        paymentMethods: allPaymentMethods,
        splitPayment: false,
        splitPayments: [
            { method: 'cash', amount: 0 },
            { method: 'card', amount: 0 }
        ],

        // Zone info
        zoneInfo: null,

        // Variation modal
        showVariationModal: false,
        selectedProduct: null,
        selectedVariation: '',
        selectedExtras: [],
        selectedGroupOptions: {},

        init() {
            const oldItems = @json(old('items', []));
            if (Array.isArray(oldItems) && oldItems.length > 0) {
                 this.cart = oldItems.map(item => {
                    const product = productsMap[item.product_id];
                    if (product) {
                        return { ...product, quantity: parseInt(item.quantity) };
                    }
                    return null;
                }).filter(Boolean);
            }

            // Initialize zone info if zone was previously selected
            if (this.zoneId) {
                this.onZoneChange();
            }
        },

        // --- Computed Properties ---

        get subtotal() {
            return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        },

        get total() {
            return this.subtotal + parseFloat(this.deliveryFee || 0);
        },

        get isMealCardSelected() {
            const mealCards = ['pluxee', 'edenred', 'multinet', 'metropol', 'tokenflex', 'setcard'];
            return mealCards.includes(this.paymentMethod);
        },

        get splitPaymentRemaining() {
            const totalPaid = this.splitPayments.reduce((sum, sp) => sum + (parseFloat(sp.amount) || 0), 0);
            return Math.round((this.total - totalPaid) * 100) / 100;
        },

        // --- Payment Methods ---

        getSelectedMethodLabel() {
            const method = allPaymentMethods.find(m => m.key === this.paymentMethod);
            return method ? method.label : '';
        },

        onSplitPaymentToggle() {
            if (this.splitPayment) {
                this.splitPayments = [
                    { method: 'cash', amount: 0 },
                    { method: 'card', amount: 0 }
                ];
            }
        },

        addSplitPayment() {
            this.splitPayments.push({ method: 'cash', amount: 0 });
        },

        removeSplitPayment(index) {
            if (this.splitPayments.length > 2) {
                this.splitPayments.splice(index, 1);
            }
        },

        onSplitAmountChange(changedIndex) {
            // Auto-fill last split with remaining amount
            if (this.splitPayments.length === 2) {
                const otherIndex = changedIndex === 0 ? 1 : 0;
                const changedAmount = parseFloat(this.splitPayments[changedIndex].amount) || 0;
                const remaining = Math.max(0, this.total - changedAmount);
                this.splitPayments[otherIndex].amount = Math.round(remaining * 100) / 100;
            }
        },

        // --- Zone Management ---

        onZoneChange() {
            const zone = zonesData[this.zoneId];
            if (!zone) {
                this.zoneInfo = null;
                return;
            }

            // Auto-fill delivery fee from zone
            if (zone.deliveryFee > 0) {
                this.deliveryFee = zone.deliveryFee;
            }

            // Set zone info for display
            const remaining = zone.dailyLimit !== null ? (zone.dailyLimit - zone.currentCount) : null;
            this.zoneInfo = {
                label: zone.canAccept ? zone.name : zone.name + ' - Limit doldu!',
                canAccept: zone.canAccept,
                remaining: remaining,
                estimatedMinutes: zone.estimatedMinutes
            };
        },

        // --- Cart & Variations ---

        getExtrasLabel(extras) {
            if (!extras || extras.length === 0) return '';
            return extras.map(e => e.startsWith('remove_') ? e.replace('remove_', '') + ' cikar' : '+ ' + e).join(', ');
        },

        addToCart(product) {
            // Check for option_groups (new system) or legacy variations/extras
            if ((product.option_groups && product.option_groups.length > 0) ||
                (product.variations && product.variations.length > 0) ||
                (product.extras && product.extras.length > 0) ||
                (product.removables && product.removables.length > 0)) {
                this.selectedProduct = product;
                this.selectedVariation = product.variations?.[0]?.name || '';
                this.selectedExtras = [];
                // Initialize selectedGroupOptions for each group
                this.selectedGroupOptions = {};
                if (product.option_groups) {
                    product.option_groups.forEach((group, gIdx) => {
                        this.selectedGroupOptions[gIdx] = group.type === 'single' ? '' : [];
                    });
                }
                this.showVariationModal = true;
                return;
            }
            this.addToCartDirect(product);
        },

        // --- Option Group Selection Methods ---

        isOptionSelected(gIdx, optionName) {
            const selection = this.selectedGroupOptions[gIdx];
            if (Array.isArray(selection)) {
                return selection.includes(optionName);
            }
            return selection === optionName;
        },

        selectSingleOption(gIdx, optionName) {
            this.selectedGroupOptions[gIdx] = optionName;
        },

        toggleMultiOption(gIdx, optionName, checked) {
            if (!Array.isArray(this.selectedGroupOptions[gIdx])) {
                this.selectedGroupOptions[gIdx] = [];
            }
            if (checked) {
                // Enforce max_selections if set
                const group = this.selectedProduct?.option_groups?.[gIdx];
                const max = group?.max_selections;
                if (max && this.selectedGroupOptions[gIdx].length >= max) {
                    return; // Already at max
                }
                this.selectedGroupOptions[gIdx].push(optionName);
            } else {
                this.selectedGroupOptions[gIdx] = this.selectedGroupOptions[gIdx].filter(n => n !== optionName);
            }
        },

        // --- Cart Direct Add ---

        addToCartDirect(product, variation = null, extras = [], selectedOptions = null) {
            // Build cart key from selected options or legacy variation/extras
            let cartKey = '' + product.id;
            if (selectedOptions && Object.keys(selectedOptions).length > 0) {
                cartKey += '-opts:' + JSON.stringify(selectedOptions);
            } else {
                cartKey += (variation ? '-' + variation : '') + (extras.length ? '-' + extras.sort().join(',') : '');
            }

            const existingIndex = this.cart.findIndex(item => item.cartKey === cartKey);
            if (existingIndex !== -1) {
                this.cart[existingIndex].quantity++;
            } else {
                let optionsPrice = 0;
                let optionLabels = [];

                if (selectedOptions && product.option_groups) {
                    // Calculate price from option_groups
                    product.option_groups.forEach((group, gIdx) => {
                        const sel = selectedOptions[gIdx];
                        if (!sel) return;
                        const names = Array.isArray(sel) ? sel : (sel ? [sel] : []);
                        names.forEach(optName => {
                            const opt = group.options.find(o => o.name === optName);
                            if (opt && opt.price_diff) optionsPrice += parseFloat(opt.price_diff);
                            optionLabels.push(optName);
                        });
                    });
                } else {
                    // Legacy calculation
                    if (extras.length && product.extras) {
                        extras.forEach(extraName => {
                            if (extraName.startsWith('remove_')) return;
                            const extra = product.extras.find(e => e.name === extraName);
                            if (extra) optionsPrice += extra.price || 0;
                        });
                    }
                    if (variation && product.variations) {
                        const v = product.variations.find(v => v.name === variation);
                        if (v) optionsPrice += v.price_diff || 0;
                    }
                }

                const finalPrice = product.price + optionsPrice;
                this.cart.push({
                    ...product,
                    cartKey: cartKey,
                    quantity: 1,
                    variation: variation,
                    extras: extras,
                    selectedOptions: selectedOptions,
                    optionLabels: optionLabels,
                    unitPrice: finalPrice,
                    price: finalPrice
                });
            }
        },

        getVariationPreviewPrice() {
            if (!this.selectedProduct) return 0;
            let price = this.selectedProduct.price;

            // New option_groups price calculation
            if (this.selectedProduct.option_groups && this.selectedProduct.option_groups.length > 0) {
                this.selectedProduct.option_groups.forEach((group, gIdx) => {
                    const sel = this.selectedGroupOptions[gIdx];
                    if (!sel) return;
                    const names = Array.isArray(sel) ? sel : (sel ? [sel] : []);
                    names.forEach(optName => {
                        const opt = group.options.find(o => o.name === optName);
                        if (opt && opt.price_diff) price += parseFloat(opt.price_diff);
                    });
                });
                return price;
            }

            // Legacy fallback
            if (this.selectedVariation && this.selectedProduct.variations) {
                const v = this.selectedProduct.variations.find(v => v.name === this.selectedVariation);
                if (v) price += v.price_diff || 0;
            }
            if (this.selectedExtras.length && this.selectedProduct.extras) {
                this.selectedExtras.forEach(extraName => {
                    if (extraName.startsWith('remove_')) return;
                    const extra = this.selectedProduct.extras.find(e => e.name === extraName);
                    if (extra) price += extra.price || 0;
                });
            }
            return price;
        },

        confirmVariation() {
            if (!this.selectedProduct) return;

            // Validate required groups
            if (this.selectedProduct.option_groups && this.selectedProduct.option_groups.length > 0) {
                for (let gIdx = 0; gIdx < this.selectedProduct.option_groups.length; gIdx++) {
                    const group = this.selectedProduct.option_groups[gIdx];
                    if (group.is_required) {
                        const sel = this.selectedGroupOptions[gIdx];
                        const hasSelection = Array.isArray(sel) ? sel.length > 0 : !!sel;
                        if (!hasSelection) {
                            alert(group.name + ' secimi zorunludur.');
                            return;
                        }
                    }
                }
                // Deep copy selected options
                const optionsCopy = JSON.parse(JSON.stringify(this.selectedGroupOptions));
                this.addToCartDirect(this.selectedProduct, null, [], optionsCopy);
            } else {
                // Legacy path
                this.addToCartDirect(this.selectedProduct, this.selectedVariation, [...this.selectedExtras]);
            }

            this.showVariationModal = false;
            this.selectedProduct = null;
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

        // --- Customer Search ---

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

        // --- Form Submission ---

        async submitOrder() {
            if (this.cart.length === 0 || !this.customerPhone || !this.customerName || !this.customerAddress) {
                return;
            }

            // Zone limit check
            if (this.zoneInfo && !this.zoneInfo.canAccept) {
                alert('Secilen mahalle icin siparis limiti dolmustur.');
                return;
            }

            // Split payment validation
            if (this.splitPayment && Math.abs(this.splitPaymentRemaining) > 0.01) {
                alert('Parcali odeme tutarlari toplam ile eslesmiyor. Kalan: ' + this.splitPaymentRemaining.toFixed(2) + ' TL');
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
                payment_method: this.splitPayment ? this.splitPayments[0].method : this.paymentMethod,
                notes: this.notes,
                zone_id: this.zoneId || null,
                restaurant_id: this.restaurantId || null,
                courier_id: this.courierId || null,
                print_mode: this.printMode,
                auto_assign_courier: this.autoAssignCourier ? 1 : 0,
                items: this.cart.map(item => ({
                    product_id: item.id,
                    quantity: item.quantity,
                    variation: item.variation || null,
                    extras: item.extras || [],
                    selected_options: item.selectedOptions ? JSON.stringify(item.selectedOptions) : null
                }))
            };

            // Add split payment data
            if (this.splitPayment) {
                formData.payment_methods = JSON.stringify(this.splitPayments.filter(sp => sp.amount > 0));
            }

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
                            ['product_id', 'quantity', 'variation', 'selected_options'].forEach(field => {
                                if (item[field] !== null && item[field] !== undefined) {
                                    const input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = `items[${index}][${field}]`;
                                    input.value = item[field];
                                    form.appendChild(input);
                                }
                            });
                            if (item.extras && item.extras.length > 0) {
                                item.extras.forEach((extra, ei) => {
                                    const input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = `items[${index}][extras][${ei}]`;
                                    input.value = extra;
                                    form.appendChild(input);
                                });
                            }
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
