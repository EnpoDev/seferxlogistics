/**
 * SeferX Lojistik - Service Worker
 * Provides offline support and caching
 */

const CACHE_NAME = 'seferx-v1';
const OFFLINE_URL = '/offline.html';

// Static assets to cache
const STATIC_ASSETS = [
    '/',
    '/offline.html',
    '/css/app.css',
    '/js/app.js',
    '/images/logo.svg',
    '/manifest.json'
];

// API routes that should be network-first
const API_ROUTES = [
    '/api/',
    '/kurye/api/',
    '/broadcasting/'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[SW] Caching static assets');
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => {
                        console.log('[SW] Deleting old cache:', name);
                        return caches.delete(name);
                    })
            );
        })
    );
    self.clients.claim();
});

// Fetch event - handle network requests
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip cross-origin requests
    if (url.origin !== location.origin) {
        return;
    }

    // API requests - network first, fallback to cache
    if (API_ROUTES.some(route => url.pathname.startsWith(route))) {
        event.respondWith(networkFirst(request));
        return;
    }

    // Static assets - cache first
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // HTML pages - network first with offline fallback
    if (request.headers.get('Accept')?.includes('text/html')) {
        event.respondWith(networkFirstWithOffline(request));
        return;
    }

    // Default - cache first
    event.respondWith(cacheFirst(request));
});

// Cache first strategy
async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) {
        return cached;
    }

    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        console.error('[SW] Fetch failed:', error);
        return new Response('Offline', { status: 503 });
    }
}

// Network first strategy
async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await caches.match(request);
        if (cached) {
            return cached;
        }
        return new Response(JSON.stringify({ error: 'Offline' }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Network first with offline page fallback
async function networkFirstWithOffline(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await caches.match(request);
        if (cached) {
            return cached;
        }
        return caches.match(OFFLINE_URL);
    }
}

// Check if URL is a static asset
function isStaticAsset(pathname) {
    const staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.woff', '.woff2', '.ttf'];
    return staticExtensions.some(ext => pathname.endsWith(ext));
}

// Push notification event
self.addEventListener('push', (event) => {
    if (!event.data) return;

    const data = event.data.json();
    const options = {
        body: data.body || data.message,
        icon: '/images/icons/icon-192x192.png',
        badge: '/images/icons/badge-72x72.png',
        vibrate: [100, 50, 100],
        data: data,
        actions: data.actions || [
            { action: 'open', title: 'Aç' },
            { action: 'dismiss', title: 'Kapat' }
        ],
        tag: data.tag || 'seferx-notification',
        renotify: true
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'SeferX', options)
    );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const data = event.notification.data;
    let url = '/';

    // Determine URL based on notification type
    if (data.type === 'new_order') {
        url = '/kurye/havuz';
    } else if (data.type === 'order_update') {
        url = `/kurye/siparis/${data.order_id}`;
    } else if (data.url) {
        url = data.url;
    }

    if (event.action === 'dismiss') {
        return;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window' }).then((clientList) => {
            // Check if already open
            for (const client of clientList) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            // Open new window
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});

// Background sync for offline orders
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-orders') {
        event.waitUntil(syncOfflineOrders());
    }
    if (event.tag === 'sync-location') {
        event.waitUntil(syncCourierLocation());
    }
});

// Sync offline orders when back online
async function syncOfflineOrders() {
    const cache = await caches.open(CACHE_NAME);
    const offlineOrders = await cache.match('/offline-orders');

    if (!offlineOrders) return;

    const orders = await offlineOrders.json();

    for (const order of orders) {
        try {
            await fetch('/api/orders/sync', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(order)
            });
        } catch (error) {
            console.error('[SW] Failed to sync order:', error);
        }
    }

    // Clear synced orders
    await cache.delete('/offline-orders');
}

// Sync courier location when back online
async function syncCourierLocation() {
    const cache = await caches.open(CACHE_NAME);
    const offlineLocation = await cache.match('/offline-location');

    if (!offlineLocation) return;

    const location = await offlineLocation.json();

    try {
        await fetch('/api/courier/location', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(location)
        });
        await cache.delete('/offline-location');
    } catch (error) {
        console.error('[SW] Failed to sync location:', error);
    }
}
