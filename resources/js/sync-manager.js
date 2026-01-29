/**
 * Sync Manager - Handles syncing offline data when online
 */
class SyncManager {
    constructor(options = {}) {
        this.db = options.db || null;
        this.csrfToken = options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content;
        this.baseUrl = options.baseUrl || '/kurye/api';
        this.isOnline = navigator.onLine;
        this.isSyncing = false;
        this.syncInterval = options.syncInterval || 30000; // 30 saniye
        this.intervalId = null;

        // Callbacks
        this.onOnline = options.onOnline || null;
        this.onOffline = options.onOffline || null;
        this.onSyncStart = options.onSyncStart || null;
        this.onSyncComplete = options.onSyncComplete || null;
        this.onSyncError = options.onSyncError || null;
    }

    async init() {
        if (!this.db) {
            this.db = new OfflineDB();
            await this.db.init();
        }

        this.setupEventListeners();
        this.startPeriodicSync();

        // İlk sync
        if (this.isOnline) {
            await this.syncAll();
        }

        return this;
    }

    setupEventListeners() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            if (this.onOnline) this.onOnline();
            this.syncAll();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            if (this.onOffline) this.onOffline();
        });

        // Background sync API (if supported)
        if ('serviceWorker' in navigator && 'SyncManager' in window) {
            navigator.serviceWorker.ready.then(registration => {
                // Register background sync
                registration.sync.register('sync-data').catch(err => {
                    console.warn('Background sync registration failed:', err);
                });
            });
        }
    }

    startPeriodicSync() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }

        this.intervalId = setInterval(() => {
            if (this.isOnline) {
                this.syncAll();
            }
        }, this.syncInterval);
    }

    stopPeriodicSync() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    async syncAll() {
        if (this.isSyncing || !this.isOnline) return;

        this.isSyncing = true;
        if (this.onSyncStart) this.onSyncStart();

        const results = {
            orders: { success: 0, failed: 0 },
            statusUpdates: { success: 0, failed: 0 },
            locationUpdates: { success: 0, failed: 0 },
            pods: { success: 0, failed: 0 }
        };

        try {
            // Sync orders (fetch fresh data)
            await this.syncOrders();

            // Sync pending status updates
            results.statusUpdates = await this.syncStatusUpdates();

            // Sync pending location updates
            results.locationUpdates = await this.syncLocationUpdates();

            // Sync pending PODs
            results.pods = await this.syncPODs();

            if (this.onSyncComplete) this.onSyncComplete(results);
        } catch (error) {
            console.error('Sync error:', error);
            if (this.onSyncError) this.onSyncError(error);
        } finally {
            this.isSyncing = false;
        }

        return results;
    }

    async syncOrders() {
        try {
            const response = await fetch('/kurye/siparisler?format=json', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.orders) {
                    await this.db.saveOrders(data.orders);
                }
            }
        } catch (error) {
            console.warn('Siparişler senkronize edilemedi:', error);
        }
    }

    async syncStatusUpdates() {
        const pending = await this.db.getPendingStatusUpdates();
        const results = { success: 0, failed: 0 };

        for (const update of pending) {
            try {
                const response = await fetch(`/kurye/siparis/${update.orderId}/durum`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify({
                        status: update.status,
                        offline_timestamp: update.timestamp
                    })
                });

                if (response.ok) {
                    await this.db.markStatusUpdateSynced(update.id);
                    results.success++;
                } else {
                    results.failed++;
                }
            } catch (error) {
                results.failed++;
            }
        }

        return results;
    }

    async syncLocationUpdates() {
        const pending = await this.db.getPendingLocationUpdates();
        const results = { success: 0, failed: 0 };

        if (pending.length === 0) return results;

        // Son konumu gönder (tümünü değil)
        const latest = pending.reduce((prev, current) =>
            (prev.timestamp > current.timestamp) ? prev : current
        );

        try {
            const response = await fetch('/kurye/konum', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    lat: latest.lat,
                    lng: latest.lng
                })
            });

            if (response.ok) {
                await this.db.clearLocationQueue();
                results.success = pending.length;
            } else {
                results.failed = pending.length;
            }
        } catch (error) {
            results.failed = pending.length;
        }

        return results;
    }

    async syncPODs() {
        const pending = await this.db.getPendingPODs();
        const results = { success: 0, failed: 0 };

        for (const pod of pending) {
            try {
                const formData = new FormData();
                formData.append('photo', pod.photoBlob, 'pod.jpg');
                if (pod.location) {
                    formData.append('lat', pod.location.lat);
                    formData.append('lng', pod.location.lng);
                }
                if (pod.note) {
                    formData.append('note', pod.note);
                }

                const response = await fetch(`/kurye/siparis/${pod.orderId}/pod`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: formData
                });

                if (response.ok) {
                    await this.db.removePOD(pod.id);
                    results.success++;
                } else {
                    results.failed++;
                }
            } catch (error) {
                results.failed++;
            }
        }

        return results;
    }

    // Manual sync trigger
    async forcSync() {
        return this.syncAll();
    }

    // Get sync status
    async getStatus() {
        const pending = await this.db.getPendingCount();
        return {
            isOnline: this.isOnline,
            isSyncing: this.isSyncing,
            pendingItems: pending
        };
    }

    destroy() {
        this.stopPeriodicSync();
        window.removeEventListener('online', this.onOnline);
        window.removeEventListener('offline', this.onOffline);
    }
}

// Global instance
window.SyncManager = SyncManager;

// Auto-init
document.addEventListener('DOMContentLoaded', async () => {
    const offlineContainer = document.querySelector('[data-offline-sync]');
    if (offlineContainer) {
        const db = new OfflineDB();
        await db.init();

        const syncManager = new SyncManager({
            db,
            onOnline: () => {
                window.dispatchEvent(new CustomEvent('app-online'));
            },
            onOffline: () => {
                window.dispatchEvent(new CustomEvent('app-offline'));
            },
            onSyncComplete: (results) => {
                window.dispatchEvent(new CustomEvent('sync-complete', { detail: results }));
            }
        });

        await syncManager.init();
        window.syncManager = syncManager;
        window.offlineDB = db;
    }
});
