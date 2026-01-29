/**
 * Real-time event listeners for the application
 * This file sets up all the Echo channel listeners
 */

// Wait for Echo to be initialized
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.Echo === 'undefined') {
        console.warn('Echo is not initialized');
        return;
    }

    setupOrderListeners();
    setupCourierListeners();
    setupPoolListeners();
    setupDashboardListeners();
});

/**
 * Setup order channel listeners
 */
function setupOrderListeners() {
    const ordersChannel = window.Echo.channel('orders');

    // Listen for new orders
    ordersChannel.listen('.order.created', (data) => {
        console.log('New order created:', data);
        
        // Update map if it exists
        if (window.courierMap) {
            window.courierMap.updateOrder(data);
        }
        
        // Show notification
        showNotification('Yeni Sipariş', `${data.order_number} - ${data.customer_name}`, 'info');
        
        // Dispatch custom event for other components
        window.dispatchEvent(new CustomEvent('order:created', { detail: data }));
    });

    // Listen for order status updates
    ordersChannel.listen('.order.status.updated', (data) => {
        console.log('Order status updated:', data);
        
        // Update map if it exists
        if (window.courierMap) {
            if (data.new_status === 'delivered' || data.new_status === 'cancelled') {
                window.courierMap.removeOrder(data.id);
            } else {
                window.courierMap.updateOrder(data);
            }
        }
        
        // Show notification
        showNotification(
            'Sipariş Güncellendi', 
            `${data.order_number}: ${data.status_label}`,
            data.new_status === 'cancelled' ? 'error' : 'success'
        );
        
        // Dispatch custom event for other components
        window.dispatchEvent(new CustomEvent('order:status.updated', { detail: data }));
    });
}

/**
 * Setup courier channel listeners
 */
function setupCourierListeners() {
    const couriersChannel = window.Echo.channel('couriers');

    // Listen for courier location updates
    couriersChannel.listen('.courier.location.updated', (data) => {
        console.log('Courier location updated:', data);
        
        // Update map if it exists
        if (window.courierMap) {
            window.courierMap.updateCourier(data);
        }
        
        // Dispatch custom event for other components
        window.dispatchEvent(new CustomEvent('courier:location.updated', { detail: data }));
    });

    // Listen for courier status changes
    couriersChannel.listen('.courier.status.changed', (data) => {
        console.log('Courier status changed:', data);
        
        // Update map if it exists
        if (window.courierMap) {
            window.courierMap.updateCourier(data);
        }
        
        // Show notification
        showNotification(
            'Kurye Durumu', 
            `${data.name}: ${data.status_label}`,
            data.new_status === 'available' ? 'success' : 'info'
        );
        
        // Dispatch custom event for other components
        window.dispatchEvent(new CustomEvent('courier:status.changed', { detail: data }));
    });
}

/**
 * Show a toast notification
 */
function showNotification(title, message, type = 'info') {
    // Use the global showToast function if available
    if (typeof window.showToast === 'function') {
        window.showToast(`${title}: ${message}`, type);
    } else {
        // Fallback to console
        console.log(`[${type.toUpperCase()}] ${title}: ${message}`);
    }
    
    // Also try browser notifications if permitted
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(title, { body: message });
    }
}

/**
 * Request browser notification permission
 */
function requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

// Request permission when user interacts with the page
document.addEventListener('click', function() {
    requestNotificationPermission();
}, { once: true });

/**
 * Setup pool channel listeners
 */
function setupPoolListeners() {
    const poolChannel = window.Echo.channel('pool');

    // Listen for new orders added to pool
    poolChannel.listen('.pool.order.added', (data) => {
        console.log('New order added to pool:', data);

        // Update pool list if on pool page
        if (window.poolManager) {
            window.poolManager.addOrder(data);
        }

        // Play notification sound if enabled
        playNotificationSound('pool');

        // Show notification
        showNotification('Yeni Havuz Siparişi', `${data.order_number} - ${data.customer_name}`, 'info');

        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('pool:order.added', { detail: data }));
    });

    // Listen for pool order being assigned
    poolChannel.listen('.pool.order.assigned', (data) => {
        console.log('Pool order assigned:', data);

        // Update pool list if on pool page
        if (window.poolManager) {
            window.poolManager.removeOrder(data.order.id);
        }

        // Show notification
        showNotification('Sipariş Alındı', `${data.order.order_number} - ${data.courier.name} tarafından alındı`, 'success');

        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('pool:order.assigned', { detail: data }));
    });
}

/**
 * Setup dashboard channel listeners for real-time stats
 */
function setupDashboardListeners() {
    const dashboardChannel = window.Echo.channel('dashboard');

    // Listen for stats updates
    dashboardChannel.listen('.stats.updated', (data) => {
        console.log('Dashboard stats updated:', data);

        // Update stats counters if they exist
        updateStatCounters(data);

        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('dashboard:stats.updated', { detail: data }));
    });
}

/**
 * Update stat counters on the page
 */
function updateStatCounters(data) {
    // Update order status counters
    if (data.orders) {
        Object.keys(data.orders).forEach(status => {
            const element = document.querySelector(`[data-stat="orders-${status}"]`);
            if (element) {
                animateCounter(element, parseInt(element.textContent) || 0, data.orders[status]);
            }
        });
    }

    // Update courier counters
    if (data.couriers) {
        Object.keys(data.couriers).forEach(status => {
            const element = document.querySelector(`[data-stat="couriers-${status}"]`);
            if (element) {
                animateCounter(element, parseInt(element.textContent) || 0, data.couriers[status]);
            }
        });
    }

    // Update pool counter
    if (data.pool_count !== undefined) {
        const element = document.querySelector('[data-stat="pool-count"]');
        if (element) {
            animateCounter(element, parseInt(element.textContent) || 0, data.pool_count);
        }
    }
}

/**
 * Animate counter from one value to another
 */
function animateCounter(element, from, to) {
    const duration = 500;
    const startTime = performance.now();
    const diff = to - from;

    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        // Easing function
        const easeOutQuad = progress * (2 - progress);

        element.textContent = Math.round(from + diff * easeOutQuad);

        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }

    requestAnimationFrame(update);
}

/**
 * Play notification sound
 */
function playNotificationSound(type = 'default') {
    // Check if sounds are enabled
    const soundsEnabled = localStorage.getItem('notification_sounds') !== 'false';
    if (!soundsEnabled) return;

    const sounds = {
        default: '/sounds/notification.mp3',
        pool: '/sounds/pool-order.mp3',
        order: '/sounds/new-order.mp3',
        alert: '/sounds/alert.mp3'
    };

    const soundFile = sounds[type] || sounds.default;

    try {
        const audio = new Audio(soundFile);
        audio.volume = 0.5;
        audio.play().catch(() => {
            // Audio play failed, probably not allowed
            console.log('Could not play notification sound');
        });
    } catch (e) {
        console.log('Audio not supported');
    }
}

/**
 * Setup private channel for user-specific notifications
 */
function setupUserChannel(userId) {
    if (!userId) return;

    const userChannel = window.Echo.private(`user.${userId}`);

    userChannel.listen('.notification', (data) => {
        console.log('User notification:', data);
        showNotification(data.title, data.message, data.type || 'info');
        window.dispatchEvent(new CustomEvent('user:notification', { detail: data }));
    });
}

/**
 * Setup presence channel for tracking online couriers
 */
function setupCourierPresenceChannel(branchId) {
    if (!branchId) return;

    const presenceChannel = window.Echo.join(`couriers.${branchId}`);

    presenceChannel
        .here((users) => {
            console.log('Online couriers:', users);
            window.dispatchEvent(new CustomEvent('couriers:online', { detail: users }));
        })
        .joining((user) => {
            console.log('Courier joined:', user);
            showNotification('Kurye Çevrimiçi', `${user.name} çevrimiçi oldu`, 'success');
            window.dispatchEvent(new CustomEvent('courier:joined', { detail: user }));
        })
        .leaving((user) => {
            console.log('Courier left:', user);
            showNotification('Kurye Çevrimdışı', `${user.name} çevrimdışı oldu`, 'warning');
            window.dispatchEvent(new CustomEvent('courier:left', { detail: user }));
        });

    return presenceChannel;
}

// Export for use in other modules
window.showNotification = showNotification;
window.requestNotificationPermission = requestNotificationPermission;
window.playNotificationSound = playNotificationSound;
window.setupUserChannel = setupUserChannel;
window.setupCourierPresenceChannel = setupCourierPresenceChannel;

