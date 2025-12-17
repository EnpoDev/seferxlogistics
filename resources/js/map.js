import L from 'leaflet';
import 'leaflet-draw';

// Make Leaflet available globally for plugins like Leaflet.Draw
window.L = L;

// Fix for default marker icons in webpack/vite
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
});

/**
 * CourierMapManager - Merkezi harita yönetim sınıfı
 */
class CourierMapManager {
    constructor(elementId, options = {}) {
        this.elementId = elementId;
        this.map = null;
        this.courierMarkers = new Map();
        this.orderMarkers = new Map();
        this.routeLines = new Map();
        this.zones = new Map();
        
        this.options = {
            center: [41.0082, 28.9784], // Istanbul default
            zoom: 12,
            ...options
        };
        
        this.icons = this.createIcons();
    }
    
    /**
     * Özel marker ikonları oluştur
     */
    createIcons() {
        return {
            courier: {
                available: L.divIcon({
                    className: 'custom-marker',
                    html: `<div class="w-10 h-10 bg-green-500 border-3 border-white rounded-full shadow-lg flex items-center justify-center animate-pulse">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                            </svg>
                          </div>`,
                    iconSize: [40, 40],
                    iconAnchor: [20, 20],
                    popupAnchor: [0, -20],
                }),
                busy: L.divIcon({
                    className: 'custom-marker',
                    html: `<div class="w-10 h-10 bg-orange-500 border-3 border-white rounded-full shadow-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                            </svg>
                          </div>`,
                    iconSize: [40, 40],
                    iconAnchor: [20, 20],
                    popupAnchor: [0, -20],
                }),
                offline: L.divIcon({
                    className: 'custom-marker',
                    html: `<div class="w-10 h-10 bg-gray-400 border-3 border-white rounded-full shadow-lg flex items-center justify-center opacity-60">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                            </svg>
                          </div>`,
                    iconSize: [40, 40],
                    iconAnchor: [20, 20],
                    popupAnchor: [0, -20],
                }),
                on_break: L.divIcon({
                    className: 'custom-marker',
                    html: `<div class="w-10 h-10 bg-yellow-500 border-3 border-white rounded-full shadow-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                          </div>`,
                    iconSize: [40, 40],
                    iconAnchor: [20, 20],
                    popupAnchor: [0, -20],
                }),
            },
            order: {
                pending: L.divIcon({
                    className: 'custom-marker',
                    html: `<div class="w-8 h-8 bg-yellow-500 border-2 border-white rounded-lg shadow-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z"/>
                            </svg>
                          </div>`,
                    iconSize: [32, 32],
                    iconAnchor: [16, 32],
                    popupAnchor: [0, -32],
                }),
                preparing: L.divIcon({
                    className: 'custom-marker',
                    html: `<div class="w-8 h-8 bg-blue-500 border-2 border-white rounded-lg shadow-lg flex items-center justify-center animate-pulse">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z"/>
                            </svg>
                          </div>`,
                    iconSize: [32, 32],
                    iconAnchor: [16, 32],
                    popupAnchor: [0, -32],
                }),
                ready: L.divIcon({
                    className: 'custom-marker',
                    html: `<div class="w-8 h-8 bg-purple-500 border-2 border-white rounded-lg shadow-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z"/>
                            </svg>
                          </div>`,
                    iconSize: [32, 32],
                    iconAnchor: [16, 32],
                    popupAnchor: [0, -32],
                }),
                on_delivery: L.divIcon({
                    className: 'custom-marker',
                    html: `<div class="w-8 h-8 bg-indigo-500 border-2 border-white rounded-lg shadow-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                            </svg>
                          </div>`,
                    iconSize: [32, 32],
                    iconAnchor: [16, 32],
                    popupAnchor: [0, -32],
                }),
            },
            restaurant: L.divIcon({
                className: 'custom-marker',
                html: `<div class="w-10 h-10 bg-red-500 border-3 border-white rounded-full shadow-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 3a1 1 0 011-1h.01a1 1 0 010 2H7a1 1 0 01-1-1zm2 3a1 1 0 00-2 0v1a2 2 0 00-2 2v1a2 2 0 00-2 2v.683a3.7 3.7 0 011.055.485 1.704 1.704 0 001.89 0 3.704 3.704 0 014.11 0 1.704 1.704 0 001.89 0 3.704 3.704 0 014.11 0 1.704 1.704 0 001.89 0A3.7 3.7 0 0118 12.683V12a2 2 0 00-2-2V9a2 2 0 00-2-2V6a1 1 0 10-2 0v1h-1V6a1 1 0 10-2 0v1H8V6zm10 8.868a3.704 3.704 0 01-4.055-.036 1.704 1.704 0 00-1.89 0 3.704 3.704 0 01-4.11 0 1.704 1.704 0 00-1.89 0A3.704 3.704 0 012 14.868V17a1 1 0 001 1h14a1 1 0 001-1v-2.132z" clip-rule="evenodd"/>
                        </svg>
                      </div>`,
                iconSize: [40, 40],
                iconAnchor: [20, 20],
                popupAnchor: [0, -20],
            }),
        };
    }
    
    /**
     * Haritayı başlat
     */
    init() {
        const mapElement = document.getElementById(this.elementId);
        if (!mapElement) {
            console.warn(`Map element #${this.elementId} not found`);
            return null;
        }
        
        // Önceki harita varsa temizle
        if (mapElement._leaflet_id) {
            this.map?.remove();
        }
        
        this.map = L.map(this.elementId).setView(this.options.center, this.options.zoom);
        
        // OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(this.map);
        
        return this;
    }
    
    /**
     * Kurye marker'ı ekle veya güncelle
     */
    updateCourier(courier) {
        if (!this.map || !courier.lat || !courier.lng) return;
        
        const statusKey = courier.status || 'available';
        const icon = this.icons.courier[statusKey] || this.icons.courier.available;
        
        if (this.courierMarkers.has(courier.id)) {
            // Mevcut marker'ı güncelle
            const marker = this.courierMarkers.get(courier.id);
            marker.setLatLng([courier.lat, courier.lng]);
            marker.setIcon(icon);
            marker.getPopup()?.setContent(this.createCourierPopup(courier));
        } else {
            // Yeni marker oluştur
            const marker = L.marker([courier.lat, courier.lng], { icon })
                .addTo(this.map)
                .bindPopup(this.createCourierPopup(courier));
            
            this.courierMarkers.set(courier.id, marker);
        }
    }
    
    /**
     * Kurye popup içeriği
     */
    createCourierPopup(courier) {
        const statusLabels = {
            available: { text: 'Müsait', color: 'text-green-600', bg: 'bg-green-100' },
            busy: { text: 'Meşgul', color: 'text-orange-600', bg: 'bg-orange-100' },
            offline: { text: 'Çevrimdışı', color: 'text-gray-600', bg: 'bg-gray-100' },
            on_break: { text: 'Molada', color: 'text-yellow-600', bg: 'bg-yellow-100' },
        };
        
        const status = statusLabels[courier.status] || statusLabels.available;
        
        return `
            <div class="p-3 min-w-[200px]">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center font-bold text-gray-600">
                        ${courier.name?.substring(0, 2).toUpperCase() || 'K'}
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-gray-900">${courier.name || 'Kurye'}</h3>
                        <p class="text-xs text-gray-500">${courier.phone || ''}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <span class="px-2 py-1 text-xs font-medium rounded-full ${status.bg} ${status.color}">
                        ${status.text}
                    </span>
                    <span class="text-xs text-gray-500">
                        ${courier.active_orders_count || 0} aktif sipariş
                    </span>
                </div>
                ${courier.vehicle_plate ? `<p class="text-xs text-gray-400 mt-2">Plaka: ${courier.vehicle_plate}</p>` : ''}
            </div>
        `;
    }
    
    /**
     * Sipariş marker'ı ekle veya güncelle
     */
    updateOrder(order) {
        if (!this.map || !order.lat || !order.lng) return;
        
        const statusKey = order.status || 'pending';
        const icon = this.icons.order[statusKey] || this.icons.order.pending;
        
        if (this.orderMarkers.has(order.id)) {
            const marker = this.orderMarkers.get(order.id);
            marker.setLatLng([order.lat, order.lng]);
            marker.setIcon(icon);
            marker.getPopup()?.setContent(this.createOrderPopup(order));
        } else {
            const marker = L.marker([order.lat, order.lng], { icon })
                .addTo(this.map)
                .bindPopup(this.createOrderPopup(order));
            
            this.orderMarkers.set(order.id, marker);
        }
    }
    
    /**
     * Sipariş popup içeriği
     */
    createOrderPopup(order) {
        const statusLabels = {
            pending: { text: 'Beklemede', color: 'text-yellow-600', bg: 'bg-yellow-100' },
            preparing: { text: 'Hazırlanıyor', color: 'text-blue-600', bg: 'bg-blue-100' },
            ready: { text: 'Hazır', color: 'text-purple-600', bg: 'bg-purple-100' },
            on_delivery: { text: 'Yolda', color: 'text-indigo-600', bg: 'bg-indigo-100' },
            delivered: { text: 'Teslim Edildi', color: 'text-green-600', bg: 'bg-green-100' },
            cancelled: { text: 'İptal', color: 'text-red-600', bg: 'bg-red-100' },
        };
        
        const status = statusLabels[order.status] || statusLabels.pending;
        
        return `
            <div class="p-3 min-w-[220px]">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-mono font-bold text-sm text-gray-900">${order.order_number || '#' + order.id}</span>
                    <span class="px-2 py-1 text-xs font-medium rounded-full ${status.bg} ${status.color}">
                        ${status.text}
                    </span>
                </div>
                <div class="space-y-1 text-xs text-gray-600">
                    <p><strong>Müşteri:</strong> ${order.customer_name || '-'}</p>
                    <p><strong>Adres:</strong> ${order.customer_address || '-'}</p>
                    ${order.courier_name ? `<p><strong>Kurye:</strong> ${order.courier_name}</p>` : ''}
                    <p class="font-semibold text-gray-900 mt-2">Toplam: ₺${parseFloat(order.total || 0).toFixed(2)}</p>
                </div>
            </div>
        `;
    }
    
    /**
     * Birden fazla kurye ekle
     */
    setCouriers(couriers) {
        // Önce tüm mevcut marker'ları temizle
        this.courierMarkers.forEach(marker => marker.remove());
        this.courierMarkers.clear();
        
        // Yeni marker'ları ekle
        couriers.forEach(courier => this.updateCourier(courier));
        
        // Haritayı kuryelere göre ortala
        if (couriers.length > 0) {
            const validCouriers = couriers.filter(c => c.lat && c.lng);
            if (validCouriers.length > 0) {
                const bounds = L.latLngBounds(validCouriers.map(c => [c.lat, c.lng]));
                this.map.fitBounds(bounds, { padding: [50, 50], maxZoom: 14 });
            }
        }
    }
    
    /**
     * Birden fazla sipariş ekle
     */
    setOrders(orders) {
        this.orderMarkers.forEach(marker => marker.remove());
        this.orderMarkers.clear();
        
        orders.forEach(order => this.updateOrder(order));
    }
    
    /**
     * Rota çiz (kurye -> restoran -> müşteri)
     */
    drawRoute(routeId, points, options = {}) {
        if (!this.map || points.length < 2) return;
        
        // Önceki rotayı temizle
        if (this.routeLines.has(routeId)) {
            this.routeLines.get(routeId).remove();
        }
        
        const polyline = L.polyline(points, {
            color: options.color || '#3B82F6',
            weight: options.weight || 4,
            opacity: options.opacity || 0.8,
            dashArray: options.dashed ? '10, 10' : null,
        }).addTo(this.map);
        
        this.routeLines.set(routeId, polyline);
        
        return polyline;
    }
    
    /**
     * Rotayı temizle
     */
    clearRoute(routeId) {
        if (this.routeLines.has(routeId)) {
            this.routeLines.get(routeId).remove();
            this.routeLines.delete(routeId);
        }
    }
    
    /**
     * Tüm rotaları temizle
     */
    clearAllRoutes() {
        this.routeLines.forEach(line => line.remove());
        this.routeLines.clear();
    }
    
    /**
     * Zone (bölge) polygon'u ekle
     */
    addZone(zone) {
        if (!this.map || !zone.coordinates) return;
        
        const polygon = L.polygon(zone.coordinates, {
            color: zone.color || '#3B82F6',
            fillColor: zone.color || '#3B82F6',
            fillOpacity: 0.2,
            weight: 2,
        }).addTo(this.map);
        
        if (zone.name) {
            polygon.bindPopup(`<div class="p-2 font-semibold">${zone.name}</div>`);
        }
        
        this.zones.set(zone.id, polygon);
        return polygon;
    }
    
    /**
     * Zone'u kaldır
     */
    removeZone(zoneId) {
        if (this.zones.has(zoneId)) {
            this.zones.get(zoneId).remove();
            this.zones.delete(zoneId);
        }
    }
    
    /**
     * Tüm zone'ları temizle
     */
    clearZones() {
        this.zones.forEach(zone => zone.remove());
        this.zones.clear();
    }
    
    /**
     * Belirli bir konuma odaklan
     */
    focusOn(lat, lng, zoom = 15) {
        if (this.map) {
            this.map.setView([lat, lng], zoom);
        }
    }
    
    /**
     * Kurye marker'ına odaklan
     */
    focusOnCourier(courierId) {
        const marker = this.courierMarkers.get(courierId);
        if (marker) {
            this.map.setView(marker.getLatLng(), 15);
            marker.openPopup();
        }
    }
    
    /**
     * Sipariş marker'ına odaklan
     */
    focusOnOrder(orderId) {
        const marker = this.orderMarkers.get(orderId);
        if (marker) {
            this.map.setView(marker.getLatLng(), 15);
            marker.openPopup();
        }
    }
    
    /**
     * Kurye marker'ını kaldır
     */
    removeCourier(courierId) {
        if (this.courierMarkers.has(courierId)) {
            this.courierMarkers.get(courierId).remove();
            this.courierMarkers.delete(courierId);
        }
    }
    
    /**
     * Sipariş marker'ını kaldır
     */
    removeOrder(orderId) {
        if (this.orderMarkers.has(orderId)) {
            this.orderMarkers.get(orderId).remove();
            this.orderMarkers.delete(orderId);
        }
    }
    
    /**
     * İstatistikleri hesapla
     */
    getStats() {
        const courierStats = {
            total: this.courierMarkers.size,
            available: 0,
            busy: 0,
            offline: 0,
            on_break: 0,
        };
        
        // Bu bilgiyi marker'lardan alamıyoruz, dışarıdan gelmeli
        return courierStats;
    }
    
    /**
     * Haritayı yeniden boyutlandır
     */
    invalidateSize() {
        if (this.map) {
            setTimeout(() => this.map.invalidateSize(), 100);
        }
    }
    
    /**
     * Haritayı temizle ve kaldır
     */
    destroy() {
        this.courierMarkers.forEach(m => m.remove());
        this.orderMarkers.forEach(m => m.remove());
        this.routeLines.forEach(l => l.remove());
        this.zones.forEach(z => z.remove());
        
        this.courierMarkers.clear();
        this.orderMarkers.clear();
        this.routeLines.clear();
        this.zones.clear();
        
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
    }
    
    /**
     * Kuryeleri duruma göre filtrele
     */
    filterCouriersByStatus(status) {
        this.courierMarkers.forEach((marker, id) => {
            const courierData = marker.courierData;
            if (!status || courierData?.status === status) {
                marker.addTo(this.map);
            } else {
                marker.remove();
            }
        });
    }
    
    /**
     * Siparişleri duruma göre filtrele
     */
    filterOrdersByStatus(status) {
        this.orderMarkers.forEach((marker, id) => {
            const orderData = marker.orderData;
            if (!status || orderData?.status === status) {
                marker.addTo(this.map);
            } else {
                marker.remove();
            }
        });
    }
    
    /**
     * Tüm marker'ları göster
     */
    showAllMarkers() {
        this.courierMarkers.forEach(marker => marker.addTo(this.map));
        this.orderMarkers.forEach(marker => marker.addTo(this.map));
    }
    
    /**
     * Tüm marker'ları gizle
     */
    hideAllMarkers() {
        this.courierMarkers.forEach(marker => marker.remove());
        this.orderMarkers.forEach(marker => marker.remove());
    }
    
    /**
     * Harita sınırlarını tüm marker'lara göre ayarla
     */
    fitToAllMarkers() {
        const allPoints = [];
        
        this.courierMarkers.forEach(marker => {
            const latlng = marker.getLatLng();
            allPoints.push([latlng.lat, latlng.lng]);
        });
        
        this.orderMarkers.forEach(marker => {
            const latlng = marker.getLatLng();
            allPoints.push([latlng.lat, latlng.lng]);
        });
        
        if (allPoints.length > 0) {
            const bounds = L.latLngBounds(allPoints);
            this.map.fitBounds(bounds, { padding: [50, 50], maxZoom: 14 });
        }
    }
    
    /**
     * Kurye sayısını al
     */
    getCourierCount() {
        return this.courierMarkers.size;
    }
    
    /**
     * Sipariş sayısını al
     */
    getOrderCount() {
        return this.orderMarkers.size;
    }
}

// Global erişim için
window.CourierMapManager = CourierMapManager;

// Basit harita başlatma fonksiyonu (geriye uyumluluk)
window.initMap = function(elementId = 'courier-map', options = {}) {
    const manager = new CourierMapManager(elementId, options);
    manager.init();
    window.courierMap = manager;
    return manager;
};

// DOM hazır olduğunda otomatik başlat
document.addEventListener('DOMContentLoaded', function() {
    const mapElement = document.getElementById('courier-map');
    if (mapElement) {
        // Data attribute'lardan başlangıç verilerini al
        const couriersData = mapElement.dataset.couriers;
        const ordersData = mapElement.dataset.orders;
        
        const manager = window.initMap('courier-map');
        
        if (couriersData) {
            try {
                const couriers = JSON.parse(couriersData);
                manager.setCouriers(couriers);
            } catch (e) {
                console.error('Error parsing couriers data:', e);
            }
        }
        
        if (ordersData) {
            try {
                const orders = JSON.parse(ordersData);
                manager.setOrders(orders);
            } catch (e) {
                console.error('Error parsing orders data:', e);
            }
        }
    }
});

export { CourierMapManager };
export default CourierMapManager;
