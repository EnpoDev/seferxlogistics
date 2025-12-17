<x-bayi-layout>
    <x-slot name="title">Bölgelendirme - Bayi Paneli</x-slot>

    <div class="space-y-6" x-data="zoneManager()" x-init="init()">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Bölgelendirme</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Teslimat bölgelerini tanımlayın ve yönetin</p>
            </div>
            <button @click="openCreateModal()" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors font-medium flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Yeni Bölge
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Sol Panel: Bölgeler Listesi -->
            <div class="space-y-4">
                <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-4">
                    <h3 class="font-bold text-black dark:text-white mb-3">Bölgeler</h3>
                    
                    <!-- Zone Search Input -->
                    <div class="relative mb-3">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" 
                               x-model="zoneSearch"
                               style="padding-left: 2rem;"
                               placeholder="Bölge ara..."
                               class="w-full pl-9 pr-3 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white placeholder-gray-400 focus:outline-none focus:border-black dark:focus:border-white">
                    </div>
                    
                    <div class="space-y-3 max-h-80 overflow-y-auto">
                        @forelse($zones as $zone)
                        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg hover:border-black dark:hover:border-white border border-transparent transition-colors cursor-pointer group"
                             @click="selectZone({{ $zone->id }})"
                             @dragover.prevent="$el.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20')"
                             @dragleave="$el.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20')"
                             @drop.prevent="dropCourierToZone($event, {{ $zone->id }}); $el.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20')"
                             x-show="!zoneSearch || '{{ strtolower($zone->name) }}'.includes(zoneSearch.toLowerCase())"
                             :class="{ 'border-black dark:border-white': selectedZoneId === {{ $zone->id }} }">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-black dark:text-white">{{ $zone->name }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full" style="background-color: {{ $zone->color }}"></span>
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1">
                                        <button @click.stop="showZoneDetails({{ $zone->id }})" class="p-1 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded" title="Detayları Gör">
                                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                        <button @click.stop="editZone({{ $zone->id }})" class="p-1 hover:bg-gray-200 dark:hover:bg-gray-800 rounded" title="Düzenle">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button @click.stop="deleteZone({{ $zone->id }})" class="p-1 hover:bg-red-100 dark:hover:bg-red-900/30 rounded" title="Sil">
                                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    {{ $zone->couriers_count }} Kurye
                                </div>
                                <span>{{ $zone->formatted_delivery_fee }}</span>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                            <p>Henüz bölge tanımlanmamış</p>
                        </div>
                        @endforelse
                        
                        <button @click="startDrawing()" 
                                class="w-full py-2 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg text-gray-500 hover:border-black dark:hover:border-white hover:text-black dark:hover:text-white transition-colors text-sm font-medium">
                            + Haritadan Çiz
                        </button>
                    </div>
                </div>

                <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-4">
                    <h3 class="font-bold text-black dark:text-white mb-3">Atanmamış Kuryeler</h3>
                    
                    <!-- Search Input -->
                    <div class="relative mb-3">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" 
                               x-model="courierSearch"
                               style="padding-left: 2rem;"
                               placeholder="Kurye ara..."
                               class="w-full pl-9 pr-3 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white placeholder-gray-400 focus:outline-none focus:border-black dark:focus:border-white">
                    </div>
                    
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        @forelse($unassignedCouriers ?? $couriers->take(5) as $courier)
                        <div class="flex items-center gap-3 p-2 hover:bg-gray-50 dark:hover:bg-gray-900 rounded-lg cursor-grab courier-item"
                             draggable="true"
                             data-name="{{ strtolower($courier->name) }}"
                             data-phone="{{ $courier->phone }}"
                             x-show="!courierSearch || '{{ strtolower($courier->name) }}'.includes(courierSearch.toLowerCase()) || '{{ $courier->phone }}'.includes(courierSearch)"
                             @dragstart="dragCourier($event, {{ $courier->id }})">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold
                                {{ $courier->status === 'available' ? 'bg-green-500' : ($courier->status === 'busy' ? 'bg-orange-500' : 'bg-gray-400') }}">
                                {{ strtoupper(substr($courier->name, 0, 2)) }}
                            </div>
                            <div class="flex-1">
                                <span class="text-sm text-black dark:text-white">{{ $courier->name }}</span>
                                <span class="text-xs text-gray-500 block">{{ $courier->getStatusLabel() }}</span>
                            </div>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                            </svg>
                        </div>
                        @empty
                        <p class="text-sm text-gray-400 text-center py-4">Tüm kuryeler atanmış</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Sağ Panel: Harita -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-black dark:text-white">Bölge Haritası</h3>
                            <div class="flex items-center gap-2">
                                <button @click="toggleDrawMode()" 
                                        :class="isDrawing ? 'bg-red-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                                        class="px-3 py-1 rounded-lg text-sm font-medium transition-colors">
                                    <span x-text="isDrawing ? 'Çizimi Bitir' : 'Polygon Çiz'"></span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Map Container -->
                        <div id="zone-map" 
                             class="rounded-lg h-[600px] border border-gray-200 dark:border-gray-800 z-0"
                             @dragover.prevent
                             @drop="dropCourier($event)"></div>
                        
                        <!-- Drawing Instructions -->
                        <div x-show="isDrawing" x-cloak class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <p class="text-sm text-blue-700 dark:text-blue-400">
                                <strong>Çizim Modu:</strong> Harita üzerinde tıklayarak <strong>en az 4 nokta</strong> belirleyin. 
                                <br><span class="text-xs mt-1 block">• <strong>Sağ tık:</strong> Son noktayı sil | <strong>Tamamla:</strong> İlk noktaya tıkla veya çift tıkla</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Zone Modal -->
        <div x-show="showModal" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
                
                <div class="relative bg-white dark:bg-[#1a1a1a] rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4" x-text="editingZone ? 'Bölge Düzenle' : 'Yeni Bölge Oluştur'"></h3>
                    
                    <form @submit.prevent="saveZone()">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bölge Adı</label>
                                <input type="text" x-model="zoneForm.name" required
                                       class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Renk</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" x-model="zoneForm.color" 
                                           class="w-10 h-10 rounded cursor-pointer">
                                    <input type="text" x-model="zoneForm.color" 
                                           class="flex-1 px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teslimat Ücreti (₺)</label>
                                <input type="number" x-model="zoneForm.delivery_fee" step="0.01" min="0"
                                       class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tahmini Teslimat Süresi (dk)</label>
                                <input type="number" x-model="zoneForm.estimated_delivery_minutes" min="1"
                                       class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Açıklama</label>
                                <textarea x-model="zoneForm.description" rows="2"
                                          class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white resize-none"></textarea>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end gap-3 mt-6">
                            <button type="button" @click="showModal = false"
                                    class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                                İptal
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80 transition-opacity">
                                Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Zone Details Modal -->
        <div x-show="showDetailsModal" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             x-transition>
            <div class="flex items-start justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-black/50" @click="showDetailsModal = false"></div>
                
                <div class="relative bg-white dark:bg-[#1a1a1a] rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="sticky top-0 bg-white dark:bg-[#1a1a1a] border-b border-gray-200 dark:border-gray-800 px-6 py-4 flex items-center justify-between z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 rounded-full" :style="'background-color: ' + (zoneDetails?.zone?.color || '#3B82F6')"></div>
                            <h3 class="text-xl font-bold text-black dark:text-white" x-text="zoneDetails?.zone?.name || 'Bölge Detayları'"></h3>
                        </div>
                        <button @click="showDetailsModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="p-6" x-show="!detailsLoading">
                        <!-- Summary Stats -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4">
                                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium uppercase">Toplam Sipariş</p>
                                <p class="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1" x-text="zoneDetails?.summary?.total_orders || 0"></p>
                                <p class="text-xs text-blue-500 mt-1">Son 30 gün</p>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4">
                                <p class="text-xs text-green-600 dark:text-green-400 font-medium uppercase">Teslim Edilen</p>
                                <p class="text-2xl font-bold text-green-700 dark:text-green-300 mt-1" x-text="zoneDetails?.summary?.delivered_orders || 0"></p>
                                <p class="text-xs text-green-500 mt-1">%<span x-text="zoneDetails?.summary?.completion_rate || 0"></span> başarı</p>
                            </div>
                            <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4">
                                <p class="text-xs text-orange-600 dark:text-orange-400 font-medium uppercase">Kurye Sayısı</p>
                                <p class="text-2xl font-bold text-orange-700 dark:text-orange-300 mt-1" x-text="zoneDetails?.summary?.courier_count || 0"></p>
                                <p class="text-xs text-orange-500 mt-1">Aktif kurye</p>
                            </div>
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4">
                                <p class="text-xs text-purple-600 dark:text-purple-400 font-medium uppercase">Ort. Teslimat</p>
                                <p class="text-2xl font-bold text-purple-700 dark:text-purple-300 mt-1"><span x-text="zoneDetails?.summary?.avg_delivery_time || 0"></span> dk</p>
                                <p class="text-xs text-purple-500 mt-1">Ortalama süre</p>
                            </div>
                        </div>
                        
                        <!-- Financial Summary -->
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-5 mb-6">
                            <h4 class="font-bold text-black dark:text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Finansal Özet (Son 30 Gün)
                            </h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Brüt Gelir</p>
                                    <p class="text-xl font-bold text-black dark:text-white">₺<span x-text="Number(zoneDetails?.summary?.gross_revenue || 0).toLocaleString('tr-TR')"></span></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Teslimat Ücreti</p>
                                    <p class="text-xl font-bold text-blue-600">₺<span x-text="Number(zoneDetails?.summary?.delivery_fees || 0).toLocaleString('tr-TR')"></span></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Kurye Ödemeleri</p>
                                    <p class="text-xl font-bold text-red-500">-₺<span x-text="Number(zoneDetails?.summary?.courier_payments || 0).toLocaleString('tr-TR')"></span></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Net Gelir</p>
                                    <p class="text-xl font-bold" :class="(zoneDetails?.summary?.net_revenue || 0) >= 0 ? 'text-emerald-500' : 'text-red-500'">
                                        ₺<span x-text="Number(zoneDetails?.summary?.net_revenue || 0).toLocaleString('tr-TR')"></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Daily Stats Chart -->
                        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-xl p-5 mb-6">
                            <h4 class="font-bold text-black dark:text-white mb-4">Son 7 Günlük Performans</h4>
                            <div class="flex items-end justify-between gap-2 h-32">
                                <template x-for="(day, index) in zoneDetails?.daily_stats || []" :key="index">
                                    <div class="flex-1 flex flex-col items-center">
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-t relative" 
                                             :style="'height: ' + Math.max((day.orders / Math.max(...(zoneDetails?.daily_stats || []).map(d => d.orders || 1))) * 100, 10) + '%'">
                                            <div class="absolute bottom-0 left-0 right-0 bg-emerald-500 rounded-t"
                                                 :style="'height: ' + (day.orders > 0 ? (day.delivered / day.orders) * 100 : 0) + '%'"></div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2" x-text="day.date"></p>
                                        <p class="text-xs font-medium text-black dark:text-white" x-text="day.orders"></p>
                                    </div>
                                </template>
                            </div>
                            <div class="flex items-center justify-center gap-4 mt-4 text-xs">
                                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-gray-200 dark:bg-gray-700 rounded"></span> Toplam</span>
                                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-emerald-500 rounded"></span> Teslim</span>
                            </div>
                        </div>
                        
                        <!-- Couriers Performance -->
                        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-xl p-5 mb-6">
                            <h4 class="font-bold text-black dark:text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                Kurye Performansları
                            </h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                            <th class="pb-3 font-medium">Kurye</th>
                                            <th class="pb-3 font-medium text-center">Durum</th>
                                            <th class="pb-3 font-medium text-center">Sipariş</th>
                                            <th class="pb-3 font-medium text-center">Teslim</th>
                                            <th class="pb-3 font-medium text-center">İptal</th>
                                            <th class="pb-3 font-medium text-center">Ort. Süre</th>
                                            <th class="pb-3 font-medium text-right">Kazanç</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="courier in zoneDetails?.couriers || []" :key="courier.id">
                                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                                <td class="py-3">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                                             :class="courier.status === 'available' ? 'bg-green-500' : (courier.status === 'busy' ? 'bg-orange-500' : 'bg-gray-400')">
                                                            <span x-text="courier.name.substring(0, 2).toUpperCase()"></span>
                                                        </div>
                                                        <div>
                                                            <p class="font-medium text-black dark:text-white" x-text="courier.name"></p>
                                                            <p class="text-xs text-gray-500" x-text="courier.phone"></p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <span class="px-2 py-1 rounded-full text-xs font-medium"
                                                          :class="{
                                                              'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400': courier.status === 'available',
                                                              'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400': courier.status === 'busy',
                                                              'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400': courier.status === 'offline'
                                                          }"
                                                          x-text="courier.status_label"></span>
                                                </td>
                                                <td class="py-3 text-center font-medium text-black dark:text-white" x-text="courier.total_orders"></td>
                                                <td class="py-3 text-center text-green-600" x-text="courier.delivered_orders"></td>
                                                <td class="py-3 text-center text-red-500" x-text="courier.cancelled_orders"></td>
                                                <td class="py-3 text-center text-gray-600 dark:text-gray-400"><span x-text="courier.avg_delivery_time"></span> dk</td>
                                                <td class="py-3 text-right font-bold text-emerald-600">₺<span x-text="courier.total_earnings"></span></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                                <template x-if="!zoneDetails?.couriers?.length">
                                    <p class="text-center text-gray-500 py-4">Bu bölgede kurye bulunmuyor</p>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Recent Orders -->
                        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                            <h4 class="font-bold text-black dark:text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Son Siparişler
                            </h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <template x-for="order in zoneDetails?.recent_orders || []" :key="order.id">
                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                        <div class="flex items-center gap-3">
                                            <div>
                                                <p class="font-mono font-medium text-black dark:text-white text-sm" x-text="order.order_number"></p>
                                                <p class="text-xs text-gray-500" x-text="order.customer_name"></p>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium"
                                                  :class="{
                                                      'bg-yellow-100 text-yellow-700': order.status === 'pending',
                                                      'bg-blue-100 text-blue-700': order.status === 'preparing',
                                                      'bg-purple-100 text-purple-700': order.status === 'ready',
                                                      'bg-indigo-100 text-indigo-700': order.status === 'on_delivery',
                                                      'bg-green-100 text-green-700': order.status === 'delivered',
                                                      'bg-red-100 text-red-700': order.status === 'cancelled'
                                                  }"
                                                  x-text="order.status_label"></span>
                                            <p class="text-xs text-gray-400 mt-1" x-text="order.courier_name || '-'"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-black dark:text-white">₺<span x-text="Number(order.total).toFixed(2)"></span></p>
                                            <p class="text-xs text-gray-500" x-text="order.time_ago"></p>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!zoneDetails?.recent_orders?.length">
                                    <p class="text-center text-gray-500 py-4">Henüz sipariş bulunmuyor</p>
                                </template>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loading State -->
                    <div x-show="detailsLoading" class="p-12 text-center">
                        <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-gray-500">Yükleniyor...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function zoneManager() {
        return {
            map: null,
            drawnItems: null,
            drawControl: null,
            drawHandler: null,
            zones: @json($zones),
            zonePolygons: {},
            selectedZoneId: null,
            showModal: false,
            editingZone: null,
            isDrawing: false,
            currentDrawing: null,
            zoneForm: {
                name: '',
                color: '#3B82F6',
                delivery_fee: 0,
                estimated_delivery_minutes: 30,
                description: '',
                coordinates: null
            },
            showDetailsModal: false,
            detailsLoading: false,
            zoneDetails: null,
            courierSearch: '',
            zoneSearch: '',
            
            init() {
                // Wait for Leaflet and Leaflet.Draw to be available
                const checkLeaflet = () => {
                    if (typeof L !== 'undefined' && typeof L.Draw !== 'undefined') {
                        this.initMap();
                        this.loadZones();
                    } else {
                        setTimeout(checkLeaflet, 100);
                    }
                };
                this.$nextTick(checkLeaflet);
            },
            
            initMap() {
                const mapElement = document.getElementById('zone-map');
                if (!mapElement || mapElement._leaflet_id) return;
                
                this.map = L.map('zone-map').setView([41.0082, 28.9784], 11);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19,
                }).addTo(this.map);
                
                // Initialize drawing layer
                this.drawnItems = new L.FeatureGroup();
                this.map.addLayer(this.drawnItems);
                
                // Handle draw created event
                this.map.on(L.Draw.Event.CREATED, (e) => {
                    const layer = e.layer;
                    
                    // Get coordinates
                    const coords = layer.getLatLngs()[0].map(ll => [ll.lat, ll.lng]);
                    
                    // Validate minimum 4 points
                    if (coords.length < 4) {
                        window.showToast('Bölge için en az 4 nokta gereklidir', 'warning');
                        this.stopDrawing();
                        return;
                    }
                    
                    this.drawnItems.addLayer(layer);
                    this.zoneForm.coordinates = coords;
                    this.stopDrawing();
                    this.showModal = true;
                });
                
                console.log('Zone map initialized successfully');
            },
            
            loadZones() {
                this.zones.forEach(zone => {
                    if (zone.coordinates && zone.coordinates.length > 0) {
                        const polygon = L.polygon(zone.coordinates, {
                            color: zone.color,
                            fillColor: zone.color,
                            fillOpacity: 0.2,
                            weight: 2
                        }).addTo(this.map);
                        
                        polygon.bindPopup(`
                            <div class="p-2">
                                <strong>${zone.name}</strong>
                                <p class="text-xs text-gray-500">${zone.couriers_count || 0} kurye</p>
                            </div>
                        `);
                        
                        // Add click event to select zone from map
                        polygon.on('click', () => {
                            this.selectZone(zone.id);
                            window.showToast(`"${zone.name}" bölgesi seçildi`, 'info');
                        });
                        
                        this.zonePolygons[zone.id] = polygon;
                    }
                });
                
                // Fit bounds if we have zones
                if (Object.keys(this.zonePolygons).length > 0) {
                    const group = new L.featureGroup(Object.values(this.zonePolygons));
                    this.map.fitBounds(group.getBounds().pad(0.1));
                }
            },
            
            openCreateModal() {
                this.editingZone = null;
                this.zoneForm = {
                    name: '',
                    color: '#3B82F6',
                    delivery_fee: 0,
                    estimated_delivery_minutes: 30,
                    description: '',
                    coordinates: null
                };
                this.showModal = true;
            },
            
            startDrawing() {
                if (!this.map) return;
                
                this.isDrawing = true;
                
                // Set Leaflet.Draw locale for minimum points
                if (L.drawLocal && L.drawLocal.draw && L.drawLocal.draw.handlers && L.drawLocal.draw.handlers.polygon) {
                    L.drawLocal.draw.handlers.polygon.tooltip = {
                        start: 'Bölge çizmeye başlamak için tıklayın (min. 4 nokta)',
                        cont: 'Devam etmek için tıklayın (min. 4 nokta gerekli)',
                        end: 'Tamamlamak için ilk noktaya tıklayın'
                    };
                }
                
                // Create a new polygon draw handler with extended options
                this.drawHandler = new L.Draw.Polygon(this.map, {
                    allowIntersection: true, // Allow intersection to prevent drawing issues
                    showArea: true,
                    guidelineDistance: 20,
                    shapeOptions: {
                        color: this.zoneForm.color,
                        fillColor: this.zoneForm.color,
                        fillOpacity: 0.2,
                        weight: 2
                    },
                    icon: new L.DivIcon({
                        iconSize: new L.Point(10, 10),
                        className: 'leaflet-div-icon leaflet-editing-icon'
                    }),
                    touchIcon: new L.DivIcon({
                        iconSize: new L.Point(20, 20),
                        className: 'leaflet-div-icon leaflet-editing-icon leaflet-touch-icon'
                    })
                });
                
                this.drawHandler.enable();
                
                // Add right-click to delete last vertex
                this.map.on('contextmenu', (e) => {
                    if (this.isDrawing && this.drawHandler) {
                        e.originalEvent.preventDefault();
                        this.drawHandler.deleteLastVertex();
                        console.log('Last vertex deleted');
                    }
                });
                
                console.log('Drawing mode enabled - minimum 4 points required. Right-click to delete last point.');
            },
            
            toggleDrawMode() {
                if (this.isDrawing) {
                    this.stopDrawing();
                } else {
                    this.startDrawing();
                }
            },
            
            stopDrawing() {
                this.isDrawing = false;
                if (this.drawHandler) {
                    this.drawHandler.disable();
                    this.drawHandler = null;
                }
                // Remove right-click listener
                this.map.off('contextmenu');
                console.log('Drawing mode disabled');
            },
            
            selectZone(zoneId) {
                // Reset previous selection style
                if (this.selectedZoneId && this.zonePolygons[this.selectedZoneId]) {
                    const prevZone = this.zones.find(z => z.id === this.selectedZoneId);
                    if (prevZone) {
                        this.zonePolygons[this.selectedZoneId].setStyle({
                            weight: 2,
                            fillOpacity: 0.2
                        });
                    }
                }
                
                this.selectedZoneId = zoneId;
                const polygon = this.zonePolygons[zoneId];
                if (polygon) {
                    // Highlight selected zone
                    polygon.setStyle({
                        weight: 4,
                        fillOpacity: 0.4
                    });
                    this.map.fitBounds(polygon.getBounds());
                    polygon.openPopup();
                }
            },
            
            editZone(zoneId) {
                const zone = this.zones.find(z => z.id === zoneId);
                if (zone) {
                    this.editingZone = zone;
                    this.zoneForm = {
                        name: zone.name,
                        color: zone.color,
                        delivery_fee: zone.delivery_fee,
                        estimated_delivery_minutes: zone.estimated_delivery_minutes,
                        description: zone.description || '',
                        coordinates: zone.coordinates
                    };
                    this.showModal = true;
                }
            },
            
            async showZoneDetails(zoneId) {
                this.showDetailsModal = true;
                this.detailsLoading = true;
                this.zoneDetails = null;
                
                try {
                    const response = await fetch(`/bayi/zones/${zoneId}/details`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    
                    if (!response.ok) {
                        throw new Error('Veri yüklenemedi');
                    }
                    
                    this.zoneDetails = await response.json();
                } catch (error) {
                    console.error('Error loading zone details:', error);
                    window.showToast('Bölge detayları yüklenemedi', 'error');
                    this.showDetailsModal = false;
                } finally {
                    this.detailsLoading = false;
                }
            },
            
            async saveZone() {
                const url = this.editingZone 
                    ? `/bayi/zones/${this.editingZone.id}` 
                    : '/bayi/zones';
                const method = this.editingZone ? 'PUT' : 'POST';
                
                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.zoneForm)
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.showToast(data.message, 'success');
                        this.showModal = false;
                        location.reload();
                    }
                } catch (error) {
                    console.error('Error saving zone:', error);
                    window.showToast('Bir hata oluştu', 'error');
                }
            },
            
            async deleteZone(zoneId) {
                if (!confirm('Bu bölgeyi silmek istediğinizden emin misiniz?')) return;
                
                try {
                    const response = await fetch(`/bayi/zones/${zoneId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    
                    if (!response.ok) {
                        const errorText = await response.text();
                        console.error('Delete error:', response.status, errorText);
                        window.showToast('Silme işlemi başarısız: ' + response.status, 'error');
                        return;
                    }
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.showToast(data.message, 'success');
                        
                        // Reload page after short delay
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    } else {
                        window.showToast(data.message || 'Bir hata oluştu', 'error');
                    }
                } catch (error) {
                    console.error('Error deleting zone:', error);
                    window.showToast('Bir hata oluştu: ' + error.message, 'error');
                }
            },
            
            dragCourier(event, courierId) {
                event.dataTransfer.setData('courierId', courierId);
            },
            
            async dropCourierToZone(event, zoneId) {
                const courierId = event.dataTransfer.getData('courierId');
                if (!courierId) {
                    window.showToast('Kurye seçilmedi', 'warning');
                    return;
                }
                
                try {
                    const response = await fetch(`/bayi/zones/${zoneId}/courier`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ courier_id: courierId })
                    });
                    
                    if (!response.ok) {
                        window.showToast('Atama başarısız: ' + response.status, 'error');
                        return;
                    }
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        window.showToast(data.message || 'Bir hata oluştu', 'error');
                    }
                } catch (error) {
                    console.error('Error assigning courier:', error);
                    window.showToast('Bir hata oluştu: ' + error.message, 'error');
                }
            },
            
            async dropCourier(event) {
                const courierId = event.dataTransfer.getData('courierId');
                if (!courierId || !this.selectedZoneId) {
                    window.showToast('Lütfen önce bir bölge seçin', 'warning');
                    return;
                }
                
                try {
                    const response = await fetch(`/bayi/zones/${this.selectedZoneId}/courier`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ courier_id: courierId })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.showToast(data.message, 'success');
                        location.reload();
                    }
                } catch (error) {
                    console.error('Error assigning courier:', error);
                    window.showToast('Bir hata oluştu', 'error');
                }
            }
        }
    }
    </script>
    @endpush
</x-bayi-layout>

