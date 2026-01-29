<x-bayi-layout>
    <x-slot name="title">Trendyol Go Ayarları - Bayi Paneli</x-slot>

    <div class="space-y-6" x-data="trendyolSettings()">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Trendyol Go Ayarları</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Trendyol Go by Uber Eats entegrasyonunu yönetin</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="refreshData()" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-red-700 dark:text-red-400">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Connection Status -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-black dark:text-white">Trendyol Go by Uber Eats</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            @if($restaurant)
                                {{ $restaurant['name'] ?? 'Restoran' }} -
                                <span class="{{ ($restaurant['workingStatus'] ?? '') === 'OPEN' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ ($restaurant['workingStatus'] ?? '') === 'OPEN' ? 'Açık' : 'Kapalı' }}
                                </span>
                            @else
                                Bağlantı bekleniyor...
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @if($restaurant)
                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-sm font-medium">Bağlı</span>
                    @else
                        <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-sm font-medium">Bağlantı Yok</span>
                    @endif
                </div>
            </div>
        </div>

        @if($restaurant)
        <!-- Order Management Section -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800" x-data="trendyolOrders()">
            <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-black dark:text-white">Sipariş Yönetimi</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Trendyol Go siparişlerini yönetin</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <select x-model="statusFilter" @change="loadOrders()" class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800">
                            <option value="active">Aktif Siparişler</option>
                            <option value="Created">Yeni</option>
                            <option value="Picking">Hazırlanıyor</option>
                            <option value="Invoiced">Hazır</option>
                            <option value="Shipped">Yolda</option>
                        </select>
                        <button @click="loadOrders()" class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                            <svg class="w-5 h-5" :class="{ 'animate-spin': ordersLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <!-- Loading State -->
                <div x-show="ordersLoading" class="text-center py-8">
                    <svg class="animate-spin h-8 w-8 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">Siparişler yükleniyor...</p>
                </div>

                <!-- Empty State -->
                <div x-show="!ordersLoading && orders.length === 0" class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">Aktif sipariş bulunmuyor</p>
                </div>

                <!-- Orders List -->
                <div x-show="!ordersLoading && orders.length > 0" class="space-y-4">
                    <template x-for="order in orders" :key="order.id">
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                            <!-- Order Header -->
                            <div class="p-4 bg-gray-50 dark:bg-gray-900 flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                          :class="{
                                              'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': order.status === 'Created',
                                              'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': order.status === 'Picking',
                                              'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': order.status === 'Invoiced',
                                              'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400': order.status === 'Shipped',
                                          }" x-text="order.statusLabel"></span>
                                    <span class="font-semibold text-black dark:text-white" x-text="'#' + order.orderNumber"></span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400" x-text="order.customerName"></span>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-black dark:text-white" x-text="order.totalPrice.toFixed(2) + ' TL'"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="order.address"></p>
                                </div>
                            </div>

                            <!-- Order Items -->
                            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <template x-for="(line, idx) in order.lines" :key="idx">
                                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-sm text-gray-700 dark:text-gray-300"
                                              x-text="line.quantity + 'x ' + line.name"></span>
                                    </template>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex flex-wrap gap-2">
                                    <!-- Accept Order (Created -> Picking) -->
                                    <template x-if="order.status === 'Created'">
                                        <div class="flex items-center gap-2">
                                            <select x-model="prepTimes[order.id]" class="px-2 py-1.5 text-sm bg-gray-100 dark:bg-gray-800 rounded-lg border-0">
                                                <option value="10">10 dk</option>
                                                <option value="15" selected>15 dk</option>
                                                <option value="20">20 dk</option>
                                                <option value="25">25 dk</option>
                                                <option value="30">30 dk</option>
                                                <option value="45">45 dk</option>
                                            </select>
                                            <button @click="acceptOrder(order)" :disabled="actionLoading[order.id]"
                                                    class="px-4 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 text-sm font-medium flex items-center gap-2">
                                                <svg x-show="actionLoading[order.id]" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                </svg>
                                                Kabul Et
                                            </button>
                                        </div>
                                    </template>

                                    <!-- Mark Prepared (Picking -> Invoiced) -->
                                    <template x-if="order.status === 'Picking'">
                                        <button @click="prepareOrder(order)" :disabled="actionLoading[order.id]"
                                                class="px-4 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 text-sm font-medium">
                                            Hazır
                                        </button>
                                    </template>

                                    <!-- Ship Order (Invoiced -> Shipped) -->
                                    <template x-if="order.status === 'Invoiced'">
                                        <button @click="shipOrder(order)" :disabled="actionLoading[order.id]"
                                                class="px-4 py-1.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 text-sm font-medium">
                                            Yola Çıkar
                                        </button>
                                    </template>

                                    <!-- Deliver Order (Shipped -> Delivered) -->
                                    <template x-if="order.status === 'Shipped'">
                                        <button @click="deliverOrder(order)" :disabled="actionLoading[order.id]"
                                                class="px-4 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 text-sm font-medium">
                                            Teslim Edildi
                                        </button>
                                    </template>

                                    <!-- Cancel Button (all statuses except Shipped) -->
                                    <template x-if="order.status !== 'Shipped' && order.status !== 'Delivered'">
                                        <button @click="showCancelModal(order)"
                                                class="px-4 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 text-sm font-medium">
                                            İptal Et
                                        </button>
                                    </template>

                                    <!-- Invoice Button (Delivered orders) -->
                                    <template x-if="order.status === 'Delivered' || order.status === 'Invoiced'">
                                        <button @click="showInvoiceModal(order)"
                                                class="px-4 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 text-sm font-medium">
                                            Fatura Gönder
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Cancel Modal -->
            <div x-show="cancelModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="cancelModal = false">
                <div class="bg-white dark:bg-[#181818] rounded-xl p-6 w-full max-w-md mx-4">
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Sipariş İptali</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4" x-text="'#' + (selectedOrder?.orderNumber || '')"></p>

                    <label class="block text-sm font-medium text-black dark:text-white mb-2">İptal Nedeni</label>
                    <select x-model="cancelReason" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 mb-4">
                        <option value="621">Tedarik problemi</option>
                        <option value="622">Mağaza kapalı</option>
                        <option value="623">Mağaza siparişi hazırlayamıyor</option>
                        <option value="624">Yüksek yoğunluk / Kurye yok</option>
                        <option value="626">Alan dışı</option>
                        <option value="627">Sipariş karışıklığı</option>
                    </select>

                    <div class="flex gap-3 justify-end">
                        <button @click="cancelModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-lg">Vazgeç</button>
                        <button @click="cancelOrder()" :disabled="cancelLoading" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50">
                            <span x-show="!cancelLoading">İptal Et</span>
                            <span x-show="cancelLoading">İşleniyor...</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Invoice Modal -->
            <div x-show="invoiceModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="invoiceModal = false">
                <div class="bg-white dark:bg-[#181818] rounded-xl p-6 w-full max-w-md mx-4">
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Fatura Linki Gönder</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4" x-text="'#' + (selectedOrder?.orderNumber || '')"></p>

                    <label class="block text-sm font-medium text-black dark:text-white mb-2">Fatura URL</label>
                    <input type="url" x-model="invoiceLink" placeholder="https://example.com/fatura.pdf"
                           class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 mb-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Fatura linkinin 10 yıl boyunca erişilebilir olması yasal zorunluluktur.</p>

                    <div class="flex gap-3 justify-end">
                        <button @click="invoiceModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-lg">Vazgeç</button>
                        <button @click="sendInvoice()" :disabled="invoiceLoading || !invoiceLink" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg disabled:opacity-50">
                            <span x-show="!invoiceLoading">Gönder</span>
                            <span x-show="invoiceLoading">Gönderiliyor...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restaurant Status Toggle -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-black dark:text-white">Restoran Durumu</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Restoranı anlık olarak açıp kapatabilirsiniz</p>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-black dark:text-white">Restoran Durumu</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Kapalı olduğunda yeni sipariş alamazsınız</p>
                    </div>
                    <form action="{{ route('bayi.ayarlar.trendyol.status') }}" method="POST" class="flex items-center gap-3">
                        @csrf
                        <input type="hidden" name="status" value="{{ ($restaurant['workingStatus'] ?? '') === 'OPEN' ? 'CLOSED' : 'OPEN' }}">
                        <span class="text-sm {{ ($restaurant['workingStatus'] ?? '') === 'OPEN' ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ ($restaurant['workingStatus'] ?? '') === 'OPEN' ? 'AÇIK' : 'KAPALI' }}
                        </span>
                        <button type="submit" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ ($restaurant['workingStatus'] ?? '') === 'OPEN' ? 'bg-green-600' : 'bg-gray-200 dark:bg-gray-700' }}">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ ($restaurant['workingStatus'] ?? '') === 'OPEN' ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delivery Time -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-black dark:text-white">Teslimat Süresi</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Ortalama teslimat süresini ayarlayın (5'in katları, min: 15-85, max: 20-90)</p>
            </div>
            <form action="{{ route('bayi.ayarlar.trendyol.delivery-time') }}" method="POST">
                @csrf
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Minimum (dk)</label>
                            <select name="min" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-black dark:focus:border-white">
                                @for($i = 15; $i <= 85; $i += 5)
                                    <option value="{{ $i }}" {{ ($restaurant['averageOrderPreparationTimeInMin'] ?? 20) == $i ? 'selected' : '' }}>{{ $i }} dk</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-black dark:text-white mb-2">Maksimum (dk)</label>
                            <select name="max" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-black dark:focus:border-white">
                                @for($i = 20; $i <= 90; $i += 5)
                                    <option value="{{ $i }}" {{ (($restaurant['averageOrderPreparationTimeInMin'] ?? 20) + 10) == $i ? 'selected' : '' }}>{{ $i }} dk</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors font-medium">
                        Güncelle
                    </button>
                </div>
            </form>
        </div>

        <!-- Working Hours -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-black dark:text-white">Çalışma Saatleri</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Restoranın haftalık çalışma saatlerini ayarlayın</p>
            </div>
            <form action="{{ route('bayi.ayarlar.trendyol.working-hours') }}" method="POST">
                @csrf
                <div class="p-6 space-y-4">
                    @php
                        $days = [
                            'MONDAY' => 'Pazartesi',
                            'TUESDAY' => 'Salı',
                            'WEDNESDAY' => 'Çarşamba',
                            'THURSDAY' => 'Perşembe',
                            'FRIDAY' => 'Cuma',
                            'SATURDAY' => 'Cumartesi',
                            'SUNDAY' => 'Pazar',
                        ];
                        $existingHours = collect($restaurant['workingHours'] ?? [])->keyBy('dayOfWeek');
                    @endphp

                    @foreach($days as $dayKey => $dayName)
                        @php
                            $dayHours = $existingHours->get($dayKey);
                        @endphp
                        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <div class="w-28">
                                <label class="flex items-center">
                                    <input type="checkbox" name="days[{{ $dayKey }}][enabled]" value="1"
                                           {{ $dayHours ? 'checked' : '' }}
                                           class="w-4 h-4 text-black dark:text-white bg-gray-100 dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded focus:ring-0">
                                    <span class="ml-2 text-sm font-medium text-black dark:text-white">{{ $dayName }}</span>
                                </label>
                            </div>
                            <div class="flex-1 flex items-center gap-2">
                                <input type="time" name="days[{{ $dayKey }}][open]"
                                       value="{{ $dayHours ? substr($dayHours['openingTime'], 0, 5) : '09:00' }}"
                                       class="px-3 py-2 bg-white dark:bg-gray-800 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-700 focus:outline-none focus:border-black dark:focus:border-white text-sm">
                                <span class="text-gray-500 dark:text-gray-400">-</span>
                                <input type="time" name="days[{{ $dayKey }}][close]"
                                       value="{{ $dayHours ? substr($dayHours['closingTime'], 0, 5) : '22:00' }}"
                                       class="px-3 py-2 bg-white dark:bg-gray-800 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-700 focus:outline-none focus:border-black dark:focus:border-white text-sm">
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors font-medium">
                        Çalışma Saatlerini Kaydet
                    </button>
                </div>
            </form>
        </div>

        <!-- Delivery Areas -->
        @if($deliveryAreas)
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-black dark:text-white">Teslimat Bölgeleri</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Yarıçap: {{ $deliveryAreas['radius'] ?? 0 }} metre |
                    {{ $deliveryAreas['isHexagonBased'] ?? false ? 'Hexagon Bazlı' : 'Çokgen Bazlı' }}
                </p>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @forelse($deliveryAreas['areas'] ?? [] as $area)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <div>
                                <p class="font-medium text-black dark:text-white">{{ $area['name'] ?? 'Bölge ' . ($area['areaId'] ?? $loop->iteration) }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Min Sepet: {{ $area['minBasketPrice'] ?? 0 }} TL |
                                    Teslimat: {{ $area['averageDeliveryTime']['min'] ?? 0 }}-{{ $area['averageDeliveryTime']['max'] ?? 0 }} dk
                                </p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ ($area['status'] ?? '') === 'AVAILABLE' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' }}">
                                {{ ($area['status'] ?? '') === 'AVAILABLE' ? 'Aktif' : 'Pasif' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">Teslimat bölgesi tanımlı değil</p>
                    @endforelse
                </div>
            </div>
        </div>
        @endif

        <!-- Menu Products -->
        @if($menu)
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-6 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-black dark:text-white">Menü Yönetimi</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ count($menu['sections'] ?? []) }} kategori, {{ count($menu['products'] ?? []) }} ürün
                    </p>
                </div>
            </div>
            <div class="p-6">
                <!-- Categories/Sections -->
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Kategoriler</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($menu['sections'] ?? [] as $section)
                            <form action="{{ route('bayi.ayarlar.trendyol.section-status') }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="section_name" value="{{ $section['name'] }}">
                                <input type="hidden" name="status" value="{{ ($section['status'] ?? '') === 'ACTIVE' ? 'PASSIVE' : 'ACTIVE' }}">
                                <button type="submit" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors {{ ($section['status'] ?? '') === 'ACTIVE' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/50' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                                    {{ $section['name'] }} ({{ count($section['products'] ?? []) }})
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>

                <!-- Products -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Ürünler</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($menu['products'] ?? [] as $product)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-black dark:text-white truncate">{{ $product['name'] }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($product['sellingPrice'] ?? 0, 2) }} TL</p>
                                </div>
                                <form action="{{ route('bayi.ayarlar.trendyol.product-status') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product['id'] }}">
                                    <input type="hidden" name="status" value="{{ ($product['status'] ?? '') === 'ACTIVE' ? 'PASSIVE' : 'ACTIVE' }}">
                                    <button type="submit" class="ml-2 px-2 py-1 rounded text-xs font-medium transition-colors {{ ($product['status'] ?? '') === 'ACTIVE' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">
                                        {{ ($product['status'] ?? '') === 'ACTIVE' ? 'Açık' : 'Kapalı' }}
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        @else
        <!-- No Connection -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Bağlantı Kurulamadı</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Trendyol Go API'ye bağlanılamadı. Entegrasyon ayarlarınızı kontrol edin.</p>
            <a href="{{ route('yonetim.entegrasyonlar') }}" class="inline-flex items-center px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors font-medium">
                Entegrasyon Ayarları
            </a>
        </div>
        @endif
    </div>

    <script>
        function trendyolSettings() {
            return {
                loading: false,
                refreshData() {
                    this.loading = true;
                    window.location.reload();
                }
            }
        }

        function trendyolOrders() {
            return {
                orders: [],
                ordersLoading: false,
                statusFilter: 'active',
                prepTimes: {},
                actionLoading: {},

                // Cancel Modal
                cancelModal: false,
                cancelLoading: false,
                cancelReason: '621',
                selectedOrder: null,

                // Invoice Modal
                invoiceModal: false,
                invoiceLoading: false,
                invoiceLink: '',

                init() {
                    this.loadOrders();
                    // Auto-refresh every 30 seconds
                    setInterval(() => this.loadOrders(), 30000);
                },

                async loadOrders() {
                    this.ordersLoading = true;
                    try {
                        const statuses = this.statusFilter === 'active'
                            ? ['Created', 'Picking', 'Invoiced', 'Shipped']
                            : [this.statusFilter];

                        const response = await fetch(`{{ route('bayi.trendyol.orders') }}?statuses=${statuses.join(',')}`);
                        const data = await response.json();

                        if (data.orders) {
                            this.orders = data.orders;
                            // Initialize prep times
                            this.orders.forEach(order => {
                                if (!this.prepTimes[order.id]) {
                                    this.prepTimes[order.id] = '15';
                                }
                            });
                        }
                    } catch (error) {
                        console.error('Failed to load orders:', error);
                    } finally {
                        this.ordersLoading = false;
                    }
                },

                async acceptOrder(order) {
                    this.actionLoading[order.id] = true;
                    try {
                        const response = await fetch('{{ route('bayi.trendyol.orders.accept') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                package_id: order.id,
                                preparation_time: parseInt(this.prepTimes[order.id] || 15)
                            })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.loadOrders();
                        } else {
                            alert(data.error || 'İşlem başarısız');
                        }
                    } catch (error) {
                        alert('Bir hata oluştu');
                    } finally {
                        this.actionLoading[order.id] = false;
                    }
                },

                async prepareOrder(order) {
                    this.actionLoading[order.id] = true;
                    try {
                        const response = await fetch('{{ route('bayi.trendyol.orders.prepare') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ package_id: order.id })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.loadOrders();
                        } else {
                            alert(data.error || 'İşlem başarısız');
                        }
                    } catch (error) {
                        alert('Bir hata oluştu');
                    } finally {
                        this.actionLoading[order.id] = false;
                    }
                },

                async shipOrder(order) {
                    this.actionLoading[order.id] = true;
                    try {
                        const response = await fetch('{{ route('bayi.trendyol.orders.ship') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ package_id: order.id })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.loadOrders();
                        } else {
                            alert(data.error || 'İşlem başarısız');
                        }
                    } catch (error) {
                        alert('Bir hata oluştu');
                    } finally {
                        this.actionLoading[order.id] = false;
                    }
                },

                async deliverOrder(order) {
                    this.actionLoading[order.id] = true;
                    try {
                        const response = await fetch('{{ route('bayi.trendyol.orders.deliver') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ package_id: order.id })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.loadOrders();
                        } else {
                            alert(data.error || 'İşlem başarısız');
                        }
                    } catch (error) {
                        alert('Bir hata oluştu');
                    } finally {
                        this.actionLoading[order.id] = false;
                    }
                },

                showCancelModal(order) {
                    this.selectedOrder = order;
                    this.cancelReason = '621';
                    this.cancelModal = true;
                },

                async cancelOrder() {
                    if (!this.selectedOrder) return;
                    this.cancelLoading = true;
                    try {
                        const response = await fetch('{{ route('bayi.trendyol.orders.cancel') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                package_id: this.selectedOrder.id,
                                item_ids: this.selectedOrder.itemIds,
                                reason_id: parseInt(this.cancelReason)
                            })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.cancelModal = false;
                            this.loadOrders();
                        } else {
                            alert(data.error || 'İptal başarısız');
                        }
                    } catch (error) {
                        alert('Bir hata oluştu');
                    } finally {
                        this.cancelLoading = false;
                    }
                },

                showInvoiceModal(order) {
                    this.selectedOrder = order;
                    this.invoiceLink = '';
                    this.invoiceModal = true;
                },

                async sendInvoice() {
                    if (!this.selectedOrder || !this.invoiceLink) return;
                    this.invoiceLoading = true;
                    try {
                        const response = await fetch('{{ route('bayi.trendyol.orders.invoice') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                order_id: this.selectedOrder.orderId,
                                invoice_link: this.invoiceLink
                            })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.invoiceModal = false;
                            alert('Fatura linki gönderildi');
                        } else {
                            alert(data.error || 'Gönderim başarısız');
                        }
                    } catch (error) {
                        alert('Bir hata oluştu');
                    } finally {
                        this.invoiceLoading = false;
                    }
                }
            }
        }
    </script>
</x-bayi-layout>
