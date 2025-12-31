<x-bayi-layout>
    <x-slot name="title">Bölgelendirme - Bayi Paneli</x-slot>

    <div class="space-y-6" x-data="zoneManager()" x-init="init()">
        {{-- Page Header --}}
        <x-layout.page-header title="Bölgelendirme" subtitle="Teslimat bölgelerini tanımlayın ve yönetin">
            <x-slot name="actions">
                <x-ui.button @click="openCreateModal()" icon="plus">Yeni Bölge</x-ui.button>
            </x-slot>
        </x-layout.page-header>

        <x-layout.grid cols="1" lgCols="3" gap="6">
            {{-- Sol Panel: Bölgeler Listesi --}}
            <div class="space-y-4">
                <x-ui.card>
                    <h3 class="font-bold text-black dark:text-white mb-3">Bölgeler</h3>

                    <x-form.search-input x-model="zoneSearch" placeholder="Bölge ara..." class="mb-3" />

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
                                            <x-ui.icon name="eye" class="w-4 h-4 text-blue-500" />
                                        </button>
                                        <button @click.stop="editZone({{ $zone->id }})" class="p-1 hover:bg-gray-200 dark:hover:bg-gray-800 rounded" title="Düzenle">
                                            <x-ui.icon name="edit" class="w-4 h-4 text-gray-500" />
                                        </button>
                                        <button @click.stop="deleteZone({{ $zone->id }})" class="p-1 hover:bg-red-100 dark:hover:bg-red-900/30 rounded" title="Sil">
                                            <x-ui.icon name="trash" class="w-4 h-4 text-red-500" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <div class="flex items-center">
                                    <x-ui.icon name="users" class="w-4 h-4 mr-1" />
                                    {{ $zone->couriers_count }} Kurye
                                </div>
                                <span>{{ $zone->formatted_delivery_fee }}</span>
                            </div>
                        </div>
                        @empty
                        <x-ui.empty-state title="Henüz bölge tanımlanmamış" icon="map" />
                        @endforelse

                        <button @click="startDrawing()"
                                class="w-full py-2 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg text-gray-500 hover:border-black dark:hover:border-white hover:text-black dark:hover:text-white transition-colors text-sm font-medium">
                            + Haritadan Çiz
                        </button>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <h3 class="font-bold text-black dark:text-white mb-3">Atanmamış Kuryeler</h3>

                    <x-form.search-input x-model="courierSearch" placeholder="Kurye ara..." class="mb-3" />

                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        @forelse($unassignedCouriers ?? $couriers->take(5) as $courier)
                        <div class="flex items-center gap-3 p-2 hover:bg-gray-50 dark:hover:bg-gray-900 rounded-lg cursor-grab courier-item"
                             draggable="true"
                             data-name="{{ strtolower($courier->name) }}"
                             data-phone="{{ $courier->phone }}"
                             x-show="!courierSearch || '{{ strtolower($courier->name) }}'.includes(courierSearch.toLowerCase()) || '{{ $courier->phone }}'.includes(courierSearch)"
                             @dragstart="dragCourier($event, {{ $courier->id }})">
                            <x-data.courier-avatar :courier="$courier" size="sm" :showName="false" :showStatus="false" />
                            <div class="flex-1">
                                <span class="text-sm text-black dark:text-white">{{ $courier->name }}</span>
                                <span class="text-xs text-gray-500 block">{{ $courier->getStatusLabel() }}</span>
                            </div>
                            <x-ui.icon name="menu" class="w-4 h-4 text-gray-400" />
                        </div>
                        @empty
                        <p class="text-sm text-gray-400 text-center py-4">Tüm kuryeler atanmış</p>
                        @endforelse
                    </div>
                </x-ui.card>
            </div>

            {{-- Sag Panel: Harita --}}
            <div class="lg:col-span-2">
                <x-ui.card>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-black dark:text-white">Bölge Haritası</h3>
                        <x-ui.button size="sm" @click="toggleDrawMode()"
                            :class="'isDrawing ? \'bg-red-500 hover:bg-red-600\' : \'\''"
                            x-text="isDrawing ? 'Çizimi Bitir' : 'Polygon Çiz'">
                        </x-ui.button>
                    </div>

                    <div id="zone-map"
                         class="rounded-lg h-[600px] border border-gray-200 dark:border-gray-800 z-0"
                         @dragover.prevent
                         @drop="dropCourier($event)"></div>

                    <x-feedback.alert x-show="isDrawing" x-cloak type="info" class="mt-4">
                        <strong>Çizim Modu:</strong> Harita üzerinde tıklayarak <strong>en az 4 nokta</strong> belirleyin.
                        <br><span class="text-xs mt-1 block">Sağ tık: Son noktayı sil | Tamamla: İlk noktaya tıkla veya çift tıkla</span>
                    </x-feedback.alert>
                </x-ui.card>
            </div>
        </x-layout.grid>

        {{-- Create/Edit Zone Modal --}}
        <x-ui.modal name="zoneModal" x-bind:title="editingZone ? 'Bölge Düzenle' : 'Yeni Bölge Oluştur'" size="md">
            <div x-show="showModal">
                <form @submit.prevent="saveZone()" class="space-y-4">
                    <x-form.input name="zone_name" label="Bölge Adı" x-model="zoneForm.name" required />

                    <x-form.form-group label="Renk">
                        <div class="flex items-center gap-2">
                            <input type="color" x-model="zoneForm.color" class="w-10 h-10 rounded cursor-pointer">
                            <input type="text" x-model="zoneForm.color"
                                   class="flex-1 px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                        </div>
                    </x-form.form-group>

                    <x-form.input type="number" name="delivery_fee" label="Teslimat Ücreti (TL)" x-model="zoneForm.delivery_fee" />
                    <x-form.input type="number" name="estimated_delivery_minutes" label="Tahmini Teslimat Süresi (dk)" x-model="zoneForm.estimated_delivery_minutes" />
                    <x-form.textarea name="zone_description" label="Açıklama" x-model="zoneForm.description" :rows="2" />

                    <div class="flex gap-3 pt-4">
                        <x-ui.button type="button" variant="secondary" @click="showModal = false" class="flex-1">İptal</x-ui.button>
                        <x-ui.button type="submit" class="flex-1">Kaydet</x-ui.button>
                    </div>
                </form>
            </div>
        </x-ui.modal>

        {{-- Zone Details Modal --}}
        <div x-show="showDetailsModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
            <div class="flex items-start justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-black/50" @click="showDetailsModal = false"></div>

                <div class="relative bg-white dark:bg-[#1a1a1a] rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                    {{-- Modal Header --}}
                    <div class="sticky top-0 bg-white dark:bg-[#1a1a1a] border-b border-gray-200 dark:border-gray-800 px-6 py-4 flex items-center justify-between z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 rounded-full" :style="'background-color: ' + (zoneDetails?.zone?.color || '#3B82F6')"></div>
                            <h3 class="text-xl font-bold text-black dark:text-white" x-text="zoneDetails?.zone?.name || 'Bölge Detayları'"></h3>
                        </div>
                        <button @click="showDetailsModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">
                            <x-ui.icon name="x" class="w-5 h-5 text-gray-500" />
                        </button>
                    </div>

                    <div class="p-6" x-show="!detailsLoading">
                        {{-- Summary Stats --}}
                        <x-layout.grid cols="2" mdCols="4" gap="4" class="mb-6">
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
                        </x-layout.grid>

                        {{-- Financial Summary --}}
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-5 mb-6">
                            <h4 class="font-bold text-black dark:text-white mb-4 flex items-center gap-2">
                                <x-ui.icon name="money" class="w-5 h-5 text-emerald-500" />
                                Finansal Özet (Son 30 Gün)
                            </h4>
                            <x-layout.grid cols="2" mdCols="4" gap="4">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Brüt Gelir</p>
                                    <p class="text-xl font-bold text-black dark:text-white">TL<span x-text="Number(zoneDetails?.summary?.gross_revenue || 0).toLocaleString('tr-TR')"></span></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Teslimat Ücreti</p>
                                    <p class="text-xl font-bold text-blue-600">TL<span x-text="Number(zoneDetails?.summary?.delivery_fees || 0).toLocaleString('tr-TR')"></span></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Kurye Ödemeleri</p>
                                    <p class="text-xl font-bold text-red-500">-TL<span x-text="Number(zoneDetails?.summary?.courier_payments || 0).toLocaleString('tr-TR')"></span></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Net Gelir</p>
                                    <p class="text-xl font-bold" :class="(zoneDetails?.summary?.net_revenue || 0) >= 0 ? 'text-emerald-500' : 'text-red-500'">
                                        TL<span x-text="Number(zoneDetails?.summary?.net_revenue || 0).toLocaleString('tr-TR')"></span>
                                    </p>
                                </div>
                            </x-layout.grid>
                        </div>

                        {{-- Couriers Performance --}}
                        <x-ui.card class="mb-6">
                            <h4 class="font-bold text-black dark:text-white mb-4 flex items-center gap-2">
                                <x-ui.icon name="users" class="w-5 h-5 text-orange-500" />
                                Kurye Performansları
                            </h4>
                            <x-table.table hoverable>
                                <x-table.thead>
                                    <x-table.tr :hoverable="false">
                                        <x-table.th>Kurye</x-table.th>
                                        <x-table.th align="center">Durum</x-table.th>
                                        <x-table.th align="center">Sipariş</x-table.th>
                                        <x-table.th align="center">Teslim</x-table.th>
                                        <x-table.th align="center">İptal</x-table.th>
                                        <x-table.th align="center">Ort. Süre</x-table.th>
                                        <x-table.th align="right">Kazanç</x-table.th>
                                    </x-table.tr>
                                </x-table.thead>
                                <x-table.tbody>
                                    <template x-for="courier in zoneDetails?.couriers || []" :key="courier.id">
                                        <x-table.tr>
                                            <x-table.td>
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
                                            </x-table.td>
                                            <x-table.td align="center">
                                                <span class="px-2 py-1 rounded-full text-xs font-medium"
                                                      :class="{
                                                          'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400': courier.status === 'available',
                                                          'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400': courier.status === 'busy',
                                                          'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400': courier.status === 'offline'
                                                      }"
                                                      x-text="courier.status_label"></span>
                                            </x-table.td>
                                            <x-table.td align="center" class="font-medium" x-text="courier.total_orders"></x-table.td>
                                            <x-table.td align="center" class="text-green-600" x-text="courier.delivered_orders"></x-table.td>
                                            <x-table.td align="center" class="text-red-500" x-text="courier.cancelled_orders"></x-table.td>
                                            <x-table.td align="center" class="text-gray-600 dark:text-gray-400"><span x-text="courier.avg_delivery_time"></span> dk</x-table.td>
                                            <x-table.td align="right" class="font-bold text-emerald-600">TL<span x-text="courier.total_earnings"></span></x-table.td>
                                        </x-table.tr>
                                    </template>
                                </x-table.tbody>
                            </x-table.table>
                            <template x-if="!zoneDetails?.couriers?.length">
                                <p class="text-center text-gray-500 py-4">Bu bölgede kurye bulunmuyor</p>
                            </template>
                        </x-ui.card>

                        {{-- Recent Orders --}}
                        <x-ui.card>
                            <h4 class="font-bold text-black dark:text-white mb-4 flex items-center gap-2">
                                <x-ui.icon name="package" class="w-5 h-5 text-blue-500" />
                                Son Siparişler
                            </h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <template x-for="order in zoneDetails?.recent_orders || []" :key="order.id">
                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                        <div>
                                            <p class="font-mono font-medium text-black dark:text-white text-sm" x-text="order.order_number"></p>
                                            <p class="text-xs text-gray-500" x-text="order.customer_name"></p>
                                        </div>
                                        <div class="text-center">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium"
                                                  :class="{
                                                      'bg-yellow-100 text-yellow-700': order.status === 'pending',
                                                      'bg-blue-100 text-blue-700': order.status === 'preparing',
                                                      'bg-green-100 text-green-700': order.status === 'delivered',
                                                      'bg-red-100 text-red-700': order.status === 'cancelled'
                                                  }"
                                                  x-text="order.status_label"></span>
                                            <p class="text-xs text-gray-400 mt-1" x-text="order.courier_name || '-'"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-black dark:text-white">TL<span x-text="Number(order.total).toFixed(2)"></span></p>
                                            <p class="text-xs text-gray-500" x-text="order.time_ago"></p>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!zoneDetails?.recent_orders?.length">
                                    <p class="text-center text-gray-500 py-4">Henüz sipariş bulunmuyor</p>
                                </template>
                            </div>
                        </x-ui.card>
                    </div>

                    {{-- Loading State --}}
                    <div x-show="detailsLoading" class="p-12 text-center">
                        <x-feedback.loading text="Yükleniyor..." />
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm Modal --}}
    <x-ui.confirm-modal name="deleteZoneModal" title="Bölge Sil" type="danger" />

    @push('scripts')
    <script>
    function zoneManager() {
        return {
            map: null,
            drawnItems: null,
            drawHandler: null,
            zones: @json($zones),
            zonePolygons: {},
            selectedZoneId: null,
            showModal: false,
            editingZone: null,
            isDrawing: false,
            zoneForm: { name: '', color: '#3B82F6', delivery_fee: 0, estimated_delivery_minutes: 30, description: '', coordinates: null },
            showDetailsModal: false,
            detailsLoading: false,
            zoneDetails: null,
            courierSearch: '',
            zoneSearch: '',

            init() {
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
                    attribution: '&copy; OpenStreetMap contributors', maxZoom: 19,
                }).addTo(this.map);

                this.drawnItems = new L.FeatureGroup();
                this.map.addLayer(this.drawnItems);

                this.map.on(L.Draw.Event.CREATED, (e) => {
                    const layer = e.layer;
                    const coords = layer.getLatLngs()[0].map(ll => [ll.lat, ll.lng]);
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
            },

            loadZones() {
                this.zones.forEach(zone => {
                    if (zone.coordinates && zone.coordinates.length > 0) {
                        const polygon = L.polygon(zone.coordinates, {
                            color: zone.color, fillColor: zone.color, fillOpacity: 0.2, weight: 2
                        }).addTo(this.map);
                        polygon.bindPopup(`<div class="p-2"><strong>${zone.name}</strong><p class="text-xs text-gray-500">${zone.couriers_count || 0} kurye</p></div>`);
                        polygon.on('click', () => { this.selectZone(zone.id); window.showToast(`"${zone.name}" bölgesi seçildi`, 'info'); });
                        this.zonePolygons[zone.id] = polygon;
                    }
                });
                if (Object.keys(this.zonePolygons).length > 0) {
                    const group = new L.featureGroup(Object.values(this.zonePolygons));
                    this.map.fitBounds(group.getBounds().pad(0.1));
                }
            },

            openCreateModal() {
                this.editingZone = null;
                this.zoneForm = { name: '', color: '#3B82F6', delivery_fee: 0, estimated_delivery_minutes: 30, description: '', coordinates: null };
                this.showModal = true;
            },

            startDrawing() {
                if (!this.map) return;
                this.isDrawing = true;
                this.drawHandler = new L.Draw.Polygon(this.map, {
                    allowIntersection: true, showArea: true,
                    shapeOptions: { color: this.zoneForm.color, fillColor: this.zoneForm.color, fillOpacity: 0.2, weight: 2 }
                });
                this.drawHandler.enable();
                this.map.on('contextmenu', (e) => { if (this.isDrawing && this.drawHandler) { e.originalEvent.preventDefault(); this.drawHandler.deleteLastVertex(); } });
            },

            toggleDrawMode() { this.isDrawing ? this.stopDrawing() : this.startDrawing(); },

            stopDrawing() {
                this.isDrawing = false;
                if (this.drawHandler) { this.drawHandler.disable(); this.drawHandler = null; }
                this.map.off('contextmenu');
            },

            selectZone(zoneId) {
                if (this.selectedZoneId && this.zonePolygons[this.selectedZoneId]) {
                    this.zonePolygons[this.selectedZoneId].setStyle({ weight: 2, fillOpacity: 0.2 });
                }
                this.selectedZoneId = zoneId;
                const polygon = this.zonePolygons[zoneId];
                if (polygon) {
                    polygon.setStyle({ weight: 4, fillOpacity: 0.4 });
                    this.map.fitBounds(polygon.getBounds());
                    polygon.openPopup();
                }
            },

            editZone(zoneId) {
                const zone = this.zones.find(z => z.id === zoneId);
                if (zone) {
                    this.editingZone = zone;
                    this.zoneForm = { name: zone.name, color: zone.color, delivery_fee: zone.delivery_fee, estimated_delivery_minutes: zone.estimated_delivery_minutes, description: zone.description || '', coordinates: zone.coordinates };
                    this.showModal = true;
                }
            },

            async showZoneDetails(zoneId) {
                this.showDetailsModal = true;
                this.detailsLoading = true;
                this.zoneDetails = null;
                try {
                    const response = await fetch(`/bayi/zones/${zoneId}/details`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                    if (!response.ok) throw new Error('Veri yüklenemedi');
                    this.zoneDetails = await response.json();
                } catch (error) {
                    window.showToast('Bölge detayları yüklenemedi', 'error');
                    this.showDetailsModal = false;
                } finally { this.detailsLoading = false; }
            },

            async saveZone() {
                const url = this.editingZone ? `/bayi/zones/${this.editingZone.id}` : '/bayi/zones';
                const method = this.editingZone ? 'PUT' : 'POST';
                try {
                    const response = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }, body: JSON.stringify(this.zoneForm) });
                    const data = await response.json();
                    if (data.success) { window.showToast(data.message, 'success'); this.showModal = false; location.reload(); }
                } catch (error) { window.showToast('Bir hata oluştu', 'error'); }
            },

            deleteZone(zoneId) {
                window.dispatchEvent(new CustomEvent('open-confirm', {
                    detail: {
                        title: 'Bölge Sil',
                        message: 'Bu bölgeyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.',
                        confirmText: 'Sil',
                        cancelText: 'Vazgeç',
                        onConfirm: async () => {
                            try {
                                const response = await fetch(`/bayi/zones/${zoneId}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } });
                                const data = await response.json();
                                if (data.success) { window.showToast(data.message, 'success'); setTimeout(() => location.reload(), 500); }
                            } catch (error) { window.showToast('Bir hata oluştu', 'error'); }
                        }
                    }
                }));
            },

            dragCourier(event, courierId) { event.dataTransfer.setData('courierId', courierId); },

            async dropCourierToZone(event, zoneId) {
                const courierId = event.dataTransfer.getData('courierId');
                if (!courierId) { window.showToast('Kurye seçilmedi', 'warning'); return; }
                try {
                    const response = await fetch(`/bayi/zones/${zoneId}/courier`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }, body: JSON.stringify({ courier_id: courierId }) });
                    const data = await response.json();
                    if (data.success) { window.showToast(data.message, 'success'); setTimeout(() => location.reload(), 500); }
                } catch (error) { window.showToast('Bir hata oluştu', 'error'); }
            },

            async dropCourier(event) {
                const courierId = event.dataTransfer.getData('courierId');
                if (!courierId || !this.selectedZoneId) { window.showToast('Lütfen önce bir bölge seçin', 'warning'); return; }
                try {
                    const response = await fetch(`/bayi/zones/${this.selectedZoneId}/courier`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }, body: JSON.stringify({ courier_id: courierId }) });
                    const data = await response.json();
                    if (data.success) { window.showToast(data.message, 'success'); location.reload(); }
                } catch (error) { window.showToast('Bir hata oluştu', 'error'); }
            }
        }
    }
    </script>
    @endpush
</x-bayi-layout>
