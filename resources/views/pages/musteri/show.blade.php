@extends('layouts.app')

@section('content')
<div class="p-6">
    <!-- Back Button & Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
            <a href="{{ route('musteri.index') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-black dark:text-white">{{ $customer->name }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Müşteri detayları ve sipariş geçmişi</p>
            </div>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('siparis.create') }}?customer_id={{ $customer->id }}" 
                class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80 flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span>Yeni Sipariş</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Customer Info -->
        <div class="space-y-6">
            <!-- Customer Card -->
            <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-6">
                <div class="flex items-center space-x-4 mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                        {{ strtoupper(substr($customer->name, 0, 2)) }}
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-black dark:text-white">{{ $customer->name }}</h2>
                        <p class="text-gray-500">Müşteri #{{ $customer->id }}</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-black rounded-lg">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-500">Telefon</p>
                            <p class="font-mono font-medium text-black dark:text-white">{{ $customer->formatted_phone }}</p>
                        </div>
                    </div>

                    @if($customer->email)
                    <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-black rounded-lg">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-500">E-posta</p>
                            <p class="font-medium text-black dark:text-white">{{ $customer->email }}</p>
                        </div>
                    </div>
                    @endif

                    @if($customer->address)
                    <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-black rounded-lg">
                        <svg class="w-5 h-5 text-gray-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-500">Varsayılan Adres</p>
                            <p class="font-medium text-black dark:text-white">{{ $customer->address }}</p>
                        </div>
                    </div>
                    @endif
                </div>

                @if($customer->notes)
                <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <p class="text-xs text-yellow-600 dark:text-yellow-400 font-medium mb-1">Not</p>
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">{{ $customer->notes }}</p>
                </div>
                @endif

                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-800">
                    <button onclick="openEditCustomerModal()" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors">
                        Bilgileri Düzenle
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <p class="text-sm text-gray-500 mb-1">Toplam Sipariş</p>
                    <p class="text-2xl font-bold text-black dark:text-white">{{ $stats['total_orders'] }}</p>
                </div>
                <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <p class="text-sm text-gray-500 mb-1">Toplam Harcama</p>
                    <p class="text-2xl font-bold text-black dark:text-white">₺{{ number_format($stats['total_spent'], 0, ',', '.') }}</p>
                </div>
                <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <p class="text-sm text-gray-500 mb-1">Ort. Sipariş</p>
                    <p class="text-2xl font-bold text-black dark:text-white">₺{{ number_format($stats['average_order'], 0, ',', '.') }}</p>
                </div>
                <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-4">
                    <p class="text-sm text-gray-500 mb-1">Son Sipariş</p>
                    <p class="text-lg font-bold text-black dark:text-white">{{ $stats['last_order']?->diffForHumans() ?? '-' }}</p>
                </div>
            </div>

            <!-- Favorite Products -->
            @if(count($stats['favorite_products']) > 0)
            <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Favori Ürünler</h3>
                <div class="space-y-3">
                    @foreach($stats['favorite_products'] as $product)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 dark:text-gray-300">{{ $product['product_name'] }}</span>
                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-sm font-medium text-black dark:text-white">
                            {{ $product['count'] }}x
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Saved Addresses -->
            <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-black dark:text-white">Kayıtlı Adresler</h3>
                    <button onclick="openAddAddressModal()" class="text-sm text-blue-600 hover:text-blue-700">
                        + Adres Ekle
                    </button>
                </div>
                <div class="space-y-3">
                    @forelse($customer->addresses as $address)
                    <div class="p-3 bg-gray-50 dark:bg-black rounded-lg">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-black dark:text-white">{{ $address->title }}</span>
                            @if($address->is_default)
                            <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs rounded">Varsayılan</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $address->full_address }}</p>
                        @if($address->directions)
                        <p class="text-xs text-gray-500 mt-1">Tarif: {{ $address->directions }}</p>
                        @endif
                    </div>
                    @empty
                    <p class="text-sm text-gray-500 text-center py-4">Kayıtlı adres bulunmuyor</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Column - Order History -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-[#1a1a1a] border border-gray-200 dark:border-gray-800 rounded-xl">
                <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-black dark:text-white">Sipariş Geçmişi</h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($customer->orders as $order)
                    <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <div class="flex items-center space-x-3">
                                    <span class="font-mono font-semibold text-black dark:text-white">{{ $order->order_number }}</span>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if($order->status === 'delivered') bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                                        @elseif($order->status === 'cancelled') bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                                        @elseif($order->status === 'on_delivery') bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400
                                        @elseif($order->status === 'preparing') bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400
                                        @else bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400
                                        @endif">
                                        {{ $order->getStatusLabel() }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-black dark:text-white">₺{{ number_format($order->total, 2, ',', '.') }}</p>
                                <p class="text-sm text-gray-500">{{ $order->getPaymentMethodLabel() }}</p>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="bg-gray-50 dark:bg-black rounded-lg p-3 mb-3">
                            <div class="space-y-2">
                                @foreach($order->items->take(3) as $item)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-700 dark:text-gray-300">{{ $item->quantity }}x {{ $item->product_name }}</span>
                                    <span class="text-gray-500">₺{{ number_format($item->total, 2, ',', '.') }}</span>
                                </div>
                                @endforeach
                                @if($order->items->count() > 3)
                                <p class="text-xs text-gray-500 pt-1">+{{ $order->items->count() - 3 }} ürün daha</p>
                                @endif
                            </div>
                        </div>

                        <!-- Order Footer -->
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center space-x-4 text-gray-500">
                                @if($order->courier)
                                <span class="flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span>{{ $order->courier->name }}</span>
                                </span>
                                @endif
                                @if($order->restaurant)
                                <span class="flex items-center space-x-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    <span>{{ $order->restaurant->name }}</span>
                                </span>
                                @endif
                            </div>
                            <a href="{{ route('siparis.edit', $order) }}" class="text-blue-600 hover:text-blue-700 font-medium">
                                Detay
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="p-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 dark:text-gray-700 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p class="text-gray-500">Bu müşterinin henüz siparişi bulunmuyor</p>
                        <a href="{{ route('siparis.create') }}?customer_id={{ $customer->id }}" class="inline-block mt-4 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                            İlk Siparişi Oluştur
                        </a>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div id="editCustomerModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeEditCustomerModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#1a1a1a] rounded-2xl shadow-xl w-full max-w-md animate-slideUp">
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-black dark:text-white">Müşteri Bilgilerini Düzenle</h3>
                <button onclick="closeEditCustomerModal()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form action="{{ route('musteri.update', $customer) }}" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Ad Soyad *</label>
                    <input type="text" name="name" value="{{ $customer->name }}" required
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Telefon *</label>
                    <input type="text" name="phone" value="{{ $customer->phone }}" required
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">E-posta</label>
                    <input type="email" name="email" value="{{ $customer->email }}"
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Adres</label>
                    <textarea name="address" rows="2"
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">{{ $customer->address }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Not</label>
                    <textarea name="notes" rows="2"
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">{{ $customer->notes }}</textarea>
                </div>
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeEditCustomerModal()" 
                        class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white hover:bg-gray-50 dark:hover:bg-gray-900">
                        İptal
                    </button>
                    <button type="submit" 
                        class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                        Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div id="addAddressModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeAddAddressModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#1a1a1a] rounded-2xl shadow-xl w-full max-w-md animate-slideUp">
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-black dark:text-white">Yeni Adres Ekle</h3>
                <button onclick="closeAddAddressModal()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="addAddressForm" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Adres Başlığı *</label>
                    <select name="title" required
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                        <option value="Ev">Ev</option>
                        <option value="İş">İş</option>
                        <option value="Diğer">Diğer</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Adres *</label>
                    <textarea name="address" rows="2" required
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></textarea>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Bina No</label>
                        <input type="text" name="building_no"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Kat</label>
                        <input type="text" name="floor"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Daire</label>
                        <input type="text" name="apartment_no"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Adres Tarifi</label>
                    <textarea name="directions" rows="2" placeholder="Örn: Sarı bina, marketin yanı"
                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></textarea>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_default" id="is_default" class="mr-2">
                    <label for="is_default" class="text-sm text-gray-700 dark:text-gray-300">Varsayılan adres olarak ayarla</label>
                </div>
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeAddAddressModal()" 
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
function openEditCustomerModal() {
    document.getElementById('editCustomerModal').classList.remove('hidden');
}

function closeEditCustomerModal() {
    document.getElementById('editCustomerModal').classList.add('hidden');
}

function openAddAddressModal() {
    document.getElementById('addAddressModal').classList.remove('hidden');
}

function closeAddAddressModal() {
    document.getElementById('addAddressModal').classList.add('hidden');
}

document.getElementById('addAddressForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    data.is_default = formData.has('is_default');
    
    fetch('{{ route("musteri.address.store", $customer) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
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

