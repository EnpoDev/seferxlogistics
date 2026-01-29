/**
 * Offline Database - IndexedDB wrapper for offline data storage
 */
class OfflineDB {
    constructor(dbName = 'seferx-offline', version = 1) {
        this.dbName = dbName;
        this.version = version;
        this.db = null;
    }

    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Orders store
                if (!db.objectStoreNames.contains('orders')) {
                    const ordersStore = db.createObjectStore('orders', { keyPath: 'id' });
                    ordersStore.createIndex('status', 'status', { unique: false });
                    ordersStore.createIndex('synced', 'synced', { unique: false });
                }

                // Status updates queue
                if (!db.objectStoreNames.contains('statusQueue')) {
                    const statusStore = db.createObjectStore('statusQueue', { keyPath: 'id', autoIncrement: true });
                    statusStore.createIndex('orderId', 'orderId', { unique: false });
                    statusStore.createIndex('timestamp', 'timestamp', { unique: false });
                }

                // Location updates queue
                if (!db.objectStoreNames.contains('locationQueue')) {
                    const locationStore = db.createObjectStore('locationQueue', { keyPath: 'id', autoIncrement: true });
                    locationStore.createIndex('timestamp', 'timestamp', { unique: false });
                }

                // POD queue
                if (!db.objectStoreNames.contains('podQueue')) {
                    const podStore = db.createObjectStore('podQueue', { keyPath: 'id', autoIncrement: true });
                    podStore.createIndex('orderId', 'orderId', { unique: false });
                }

                // App state
                if (!db.objectStoreNames.contains('appState')) {
                    db.createObjectStore('appState', { keyPath: 'key' });
                }
            };
        });
    }

    // Orders
    async saveOrders(orders) {
        const tx = this.db.transaction('orders', 'readwrite');
        const store = tx.objectStore('orders');

        for (const order of orders) {
            order.synced = true;
            order.lastUpdated = Date.now();
            await store.put(order);
        }

        return tx.complete;
    }

    async getOrders() {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('orders', 'readonly');
            const store = tx.objectStore('orders');
            const request = store.getAll();

            request.onsuccess = () => resolve(request.result || []);
            request.onerror = () => reject(request.error);
        });
    }

    async getOrderById(id) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('orders', 'readonly');
            const store = tx.objectStore('orders');
            const request = store.get(id);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async updateOrderLocally(orderId, updates) {
        const order = await this.getOrderById(orderId);
        if (order) {
            Object.assign(order, updates, { synced: false, lastUpdated: Date.now() });
            const tx = this.db.transaction('orders', 'readwrite');
            const store = tx.objectStore('orders');
            await store.put(order);
        }
        return order;
    }

    // Status Queue
    async queueStatusUpdate(orderId, status, timestamp = Date.now()) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('statusQueue', 'readwrite');
            const store = tx.objectStore('statusQueue');
            const request = store.add({
                orderId,
                status,
                timestamp,
                synced: false
            });

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async getPendingStatusUpdates() {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('statusQueue', 'readonly');
            const store = tx.objectStore('statusQueue');
            const request = store.getAll();

            request.onsuccess = () => {
                const updates = request.result || [];
                resolve(updates.filter(u => !u.synced));
            };
            request.onerror = () => reject(request.error);
        });
    }

    async markStatusUpdateSynced(id) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('statusQueue', 'readwrite');
            const store = tx.objectStore('statusQueue');
            const request = store.delete(id);

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    // Location Queue
    async queueLocationUpdate(lat, lng, timestamp = Date.now()) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('locationQueue', 'readwrite');
            const store = tx.objectStore('locationQueue');
            const request = store.add({
                lat,
                lng,
                timestamp,
                synced: false
            });

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async getPendingLocationUpdates() {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('locationQueue', 'readonly');
            const store = tx.objectStore('locationQueue');
            const request = store.getAll();

            request.onsuccess = () => {
                const updates = request.result || [];
                resolve(updates.filter(u => !u.synced));
            };
            request.onerror = () => reject(request.error);
        });
    }

    async clearLocationQueue() {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('locationQueue', 'readwrite');
            const store = tx.objectStore('locationQueue');
            const request = store.clear();

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    // POD Queue
    async queuePOD(orderId, photoBlob, location, note) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('podQueue', 'readwrite');
            const store = tx.objectStore('podQueue');
            const request = store.add({
                orderId,
                photoBlob,
                location,
                note,
                timestamp: Date.now(),
                synced: false
            });

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async getPendingPODs() {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('podQueue', 'readonly');
            const store = tx.objectStore('podQueue');
            const request = store.getAll();

            request.onsuccess = () => {
                const pods = request.result || [];
                resolve(pods.filter(p => !p.synced));
            };
            request.onerror = () => reject(request.error);
        });
    }

    async removePOD(id) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('podQueue', 'readwrite');
            const store = tx.objectStore('podQueue');
            const request = store.delete(id);

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    // App State
    async saveState(key, value) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('appState', 'readwrite');
            const store = tx.objectStore('appState');
            const request = store.put({ key, value, updatedAt: Date.now() });

            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    async getState(key) {
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('appState', 'readonly');
            const store = tx.objectStore('appState');
            const request = store.get(key);

            request.onsuccess = () => resolve(request.result?.value);
            request.onerror = () => reject(request.error);
        });
    }

    // Utility
    async getPendingCount() {
        const [statusUpdates, locationUpdates, pods] = await Promise.all([
            this.getPendingStatusUpdates(),
            this.getPendingLocationUpdates(),
            this.getPendingPODs()
        ]);

        return {
            statusUpdates: statusUpdates.length,
            locationUpdates: locationUpdates.length,
            pods: pods.length,
            total: statusUpdates.length + locationUpdates.length + pods.length
        };
    }

    async clearAll() {
        const stores = ['orders', 'statusQueue', 'locationQueue', 'podQueue', 'appState'];

        for (const storeName of stores) {
            const tx = this.db.transaction(storeName, 'readwrite');
            const store = tx.objectStore(storeName);
            await store.clear();
        }
    }
}

// Global instance
window.OfflineDB = OfflineDB;
