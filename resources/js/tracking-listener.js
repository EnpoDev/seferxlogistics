/**
 * Real-time tracking listener for customer portal
 * This file handles live location updates for the tracking page
 */

class TrackingListener {
    constructor(trackingToken) {
        this.trackingToken = trackingToken;
        this.channel = null;
        this.onLocationUpdate = null;
        this.onStatusUpdate = null;
    }

    /**
     * Initialize the tracking listener
     */
    init() {
        if (typeof window.Echo === 'undefined') {
            console.warn('Echo is not initialized, retrying in 1s...');
            setTimeout(() => this.init(), 1000);
            return;
        }

        this.subscribe();
    }

    /**
     * Subscribe to the tracking channel
     */
    subscribe() {
        const channelName = `tracking.${this.trackingToken}`;
        this.channel = window.Echo.channel(channelName);

        // Listen for location updates
        this.channel.listen('.location.updated', (data) => {
            console.log('Tracking location updated:', data);

            // Update the map if callback is set
            if (typeof this.onLocationUpdate === 'function') {
                this.onLocationUpdate(data);
            }

            // Update UI elements
            this.updateUIElements(data);
        });

        // Listen for status updates
        this.channel.listen('.status.updated', (data) => {
            console.log('Order status updated:', data);

            if (typeof this.onStatusUpdate === 'function') {
                this.onStatusUpdate(data);
            }

            this.updateStatusUI(data);
        });

        console.log('Subscribed to tracking channel:', channelName);
    }

    /**
     * Unsubscribe from the tracking channel
     */
    unsubscribe() {
        if (this.channel) {
            window.Echo.leave(`tracking.${this.trackingToken}`);
            this.channel = null;
        }
    }

    /**
     * Update UI elements with new location data
     */
    updateUIElements(data) {
        // Update progress bar
        const progressBar = document.querySelector('[data-tracking-progress]');
        if (progressBar && data.progress !== undefined) {
            progressBar.style.width = `${data.progress}%`;
        }

        // Update ETA
        const etaElement = document.querySelector('[data-tracking-eta]');
        if (etaElement && data.estimated_minutes !== undefined) {
            if (data.estimated_minutes <= 0) {
                etaElement.textContent = 'Şimdi varıyor';
            } else {
                etaElement.textContent = `${data.estimated_minutes} dk`;
            }
        }

        // Update status label
        const statusLabel = document.querySelector('[data-tracking-status]');
        if (statusLabel && data.status_label) {
            statusLabel.textContent = data.status_label;
        }

        // Dispatch custom event for other components
        window.dispatchEvent(new CustomEvent('tracking:updated', { detail: data }));
    }

    /**
     * Update status UI when order status changes
     */
    updateStatusUI(data) {
        // Update status badge
        const statusBadge = document.querySelector('[data-order-status]');
        if (statusBadge) {
            statusBadge.textContent = data.status_label;
            statusBadge.dataset.status = data.status;
        }

        // Update tracking steps
        const steps = document.querySelectorAll('[data-tracking-step]');
        steps.forEach((step) => {
            const stepStatus = step.dataset.trackingStep;
            const stepOrder = ['pending', 'preparing', 'ready', 'on_delivery', 'delivered'];
            const currentIndex = stepOrder.indexOf(data.status);
            const stepIndex = stepOrder.indexOf(stepStatus);

            if (stepIndex <= currentIndex) {
                step.classList.add('completed');
                step.classList.remove('pending');
            } else {
                step.classList.remove('completed');
                step.classList.add('pending');
            }
        });

        // Handle delivered status
        if (data.status === 'delivered') {
            this.showDeliveredMessage();
        }

        // Handle cancelled status
        if (data.status === 'cancelled') {
            this.showCancelledMessage();
        }

        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('tracking:status.changed', { detail: data }));
    }

    /**
     * Show delivered message
     */
    showDeliveredMessage() {
        const container = document.querySelector('[data-tracking-container]');
        if (!container) return;

        const message = document.createElement('div');
        message.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50';
        message.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-xl p-8 text-center max-w-sm mx-4">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Teslim Edildi!</h3>
                <p class="text-gray-600 dark:text-gray-400">Siparişiniz başarıyla teslim edildi. Afiyet olsun!</p>
                <button onclick="this.parentElement.parentElement.remove()"
                    class="mt-6 px-6 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80 transition">
                    Tamam
                </button>
            </div>
        `;
        container.appendChild(message);
    }

    /**
     * Show cancelled message
     */
    showCancelledMessage() {
        const container = document.querySelector('[data-tracking-container]');
        if (!container) return;

        const message = document.createElement('div');
        message.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50';
        message.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-xl p-8 text-center max-w-sm mx-4">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Sipariş İptal Edildi</h3>
                <p class="text-gray-600 dark:text-gray-400">Siparişiniz iptal edildi. Detaylı bilgi için bizimle iletişime geçin.</p>
                <button onclick="this.parentElement.parentElement.remove()"
                    class="mt-6 px-6 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80 transition">
                    Tamam
                </button>
            </div>
        `;
        container.appendChild(message);
    }

    /**
     * Set callback for location updates
     */
    setOnLocationUpdate(callback) {
        this.onLocationUpdate = callback;
    }

    /**
     * Set callback for status updates
     */
    setOnStatusUpdate(callback) {
        this.onStatusUpdate = callback;
    }
}

// Export for use in templates
window.TrackingListener = TrackingListener;
