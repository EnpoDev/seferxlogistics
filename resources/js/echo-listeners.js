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

// Export for use in other modules
window.showNotification = showNotification;
window.requestNotificationPermission = requestNotificationPermission;

