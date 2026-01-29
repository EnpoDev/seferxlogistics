/**
 * Battery Monitor - Kurye pil durumu izleme
 */
class BatteryMonitor {
    constructor(options = {}) {
        this.updateInterval = options.updateInterval || 60000; // 1 dakika
        this.updateUrl = options.updateUrl || '/kurye/pil';
        this.csrfToken = options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content;
        this.lowBatteryThreshold = options.lowBatteryThreshold || 20;
        this.criticalBatteryThreshold = options.criticalBatteryThreshold || 10;
        this.battery = null;
        this.intervalId = null;
        this.onLowBattery = options.onLowBattery || null;
        this.onCriticalBattery = options.onCriticalBattery || null;
        this.onUpdate = options.onUpdate || null;
    }

    async init() {
        if (!('getBattery' in navigator)) {
            console.warn('Battery API desteklenmiyor');
            return false;
        }

        try {
            this.battery = await navigator.getBattery();
            this.setupEventListeners();
            this.startPeriodicUpdates();
            await this.sendUpdate(); // İlk güncellemeyi hemen gönder
            return true;
        } catch (error) {
            console.error('Battery API başlatılamadı:', error);
            return false;
        }
    }

    setupEventListeners() {
        if (!this.battery) return;

        // Pil seviyesi değiştiğinde
        this.battery.addEventListener('levelchange', () => {
            this.handleLevelChange();
        });

        // Şarj durumu değiştiğinde
        this.battery.addEventListener('chargingchange', () => {
            this.sendUpdate();
        });
    }

    handleLevelChange() {
        const level = Math.round(this.battery.level * 100);

        // Kritik seviye kontrolü
        if (level <= this.criticalBatteryThreshold && this.onCriticalBattery) {
            this.onCriticalBattery(level);
        }
        // Düşük seviye kontrolü
        else if (level <= this.lowBatteryThreshold && this.onLowBattery) {
            this.onLowBattery(level);
        }

        // Güncelleme gönder
        this.sendUpdate();
    }

    startPeriodicUpdates() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }

        this.intervalId = setInterval(() => {
            this.sendUpdate();
        }, this.updateInterval);
    }

    stopPeriodicUpdates() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    async sendUpdate() {
        if (!this.battery) return;

        const data = {
            level: Math.round(this.battery.level * 100),
            is_charging: this.battery.charging
        };

        try {
            const response = await fetch(this.updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify(data)
            });

            if (response.ok && this.onUpdate) {
                this.onUpdate(data);
            }
        } catch (error) {
            console.warn('Pil durumu gönderilemedi:', error);
        }
    }

    getStatus() {
        if (!this.battery) return null;

        return {
            level: Math.round(this.battery.level * 100),
            charging: this.battery.charging,
            chargingTime: this.battery.chargingTime,
            dischargingTime: this.battery.dischargingTime
        };
    }

    getBatteryIcon(level, isCharging) {
        if (isCharging) {
            return '🔌';
        }

        if (level >= 80) return '🔋';
        if (level >= 50) return '🔋';
        if (level >= 20) return '🪫';
        return '⚠️';
    }

    getBatteryColor(level) {
        if (level >= 50) return 'green';
        if (level >= 20) return 'yellow';
        return 'red';
    }

    destroy() {
        this.stopPeriodicUpdates();
        this.battery = null;
    }
}

// Global instance
window.BatteryMonitor = BatteryMonitor;

// Auto-init if data attribute is present
document.addEventListener('DOMContentLoaded', () => {
    const batteryContainer = document.querySelector('[data-battery-monitor]');
    if (batteryContainer) {
        const monitor = new BatteryMonitor({
            onLowBattery: (level) => {
                // Düşük pil uyarısı
                if (window.Alpine) {
                    window.dispatchEvent(new CustomEvent('battery-low', { detail: { level } }));
                }
            },
            onCriticalBattery: (level) => {
                // Kritik pil uyarısı
                if (window.Alpine) {
                    window.dispatchEvent(new CustomEvent('battery-critical', { detail: { level } }));
                }
            },
            onUpdate: (data) => {
                // UI güncelleme
                const levelEl = document.querySelector('[data-battery-level]');
                const iconEl = document.querySelector('[data-battery-icon]');

                if (levelEl) levelEl.textContent = data.level + '%';
                if (iconEl) iconEl.textContent = monitor.getBatteryIcon(data.level, data.is_charging);
            }
        });

        monitor.init();
        window.batteryMonitor = monitor;
    }
});
