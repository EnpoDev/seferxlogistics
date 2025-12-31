/**
 * Theme Store
 * Tema ayarlari icin Alpine.js store
 */
export default {
    // Tema modu: 'light', 'dark', 'system'
    mode: 'system',

    // Dark mode aktif mi
    darkMode: false,

    // Compact mode
    compactMode: false,

    // Animasyonlar aktif mi
    animationsEnabled: true,

    // Sidebar auto hide
    sidebarAutoHide: true,

    // Sidebar genisligi: 'narrow', 'normal', 'wide'
    sidebarWidth: 'normal',

    // Sidebar acik mi
    sidebarOpen: true,

    /**
     * Store'u baslat
     */
    init() {
        // LocalStorage'dan ayarlari yukle
        this.loadFromStorage();

        // Sistem tercihini dinle
        if (this.mode === 'system') {
            this.darkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (this.mode === 'system') {
                    this.darkMode = e.matches;
                    this.applyTheme();
                }
            });
        }

        // Sidebar auto hide icin resize dinle
        if (this.sidebarAutoHide) {
            this.sidebarOpen = window.innerWidth >= 1024;
            window.addEventListener('resize', () => {
                if (this.sidebarAutoHide) {
                    this.sidebarOpen = window.innerWidth >= 1024;
                }
            });
        }

        this.applyTheme();
    },

    /**
     * LocalStorage'dan ayarlari yukle
     */
    loadFromStorage() {
        const stored = localStorage.getItem('themeSettings');
        if (stored) {
            try {
                const settings = JSON.parse(stored);
                this.mode = settings.mode || 'system';
                this.compactMode = settings.compactMode || false;
                this.animationsEnabled = settings.animationsEnabled !== false;
                this.sidebarAutoHide = settings.sidebarAutoHide !== false;
                this.sidebarWidth = settings.sidebarWidth || 'normal';

                // Dark mode hesapla
                if (this.mode === 'dark') {
                    this.darkMode = true;
                } else if (this.mode === 'light') {
                    this.darkMode = false;
                } else {
                    this.darkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
                }
            } catch (e) {
                console.error('Theme settings parse error:', e);
            }
        }
    },

    /**
     * Ayarlari LocalStorage'a kaydet
     */
    saveToStorage() {
        const settings = {
            mode: this.mode,
            compactMode: this.compactMode,
            animationsEnabled: this.animationsEnabled,
            sidebarAutoHide: this.sidebarAutoHide,
            sidebarWidth: this.sidebarWidth,
        };
        localStorage.setItem('themeSettings', JSON.stringify(settings));
    },

    /**
     * Temayi HTML'e uygula
     */
    applyTheme() {
        const html = document.documentElement;

        // Dark mode
        if (this.darkMode) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }

        // Compact mode
        if (this.compactMode) {
            html.classList.add('compact-mode');
        } else {
            html.classList.remove('compact-mode');
        }

        // Animations
        if (!this.animationsEnabled) {
            html.classList.add('no-animations');
        } else {
            html.classList.remove('no-animations');
        }
    },

    /**
     * Dark mode toggle
     */
    toggleDarkMode() {
        this.darkMode = !this.darkMode;
        this.mode = this.darkMode ? 'dark' : 'light';
        this.applyTheme();
        this.saveToStorage();
    },

    /**
     * Tema modunu ayarla
     * @param {string} mode - 'light', 'dark', 'system'
     */
    setMode(mode) {
        this.mode = mode;

        if (mode === 'dark') {
            this.darkMode = true;
        } else if (mode === 'light') {
            this.darkMode = false;
        } else {
            this.darkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        this.applyTheme();
        this.saveToStorage();
    },

    /**
     * Compact mode toggle
     */
    toggleCompactMode() {
        this.compactMode = !this.compactMode;
        this.applyTheme();
        this.saveToStorage();
    },

    /**
     * Animasyonlari toggle
     */
    toggleAnimations() {
        this.animationsEnabled = !this.animationsEnabled;
        this.applyTheme();
        this.saveToStorage();
    },

    /**
     * Sidebar toggle
     */
    toggleSidebar() {
        this.sidebarOpen = !this.sidebarOpen;
    },

    /**
     * Sidebar genisligini ayarla
     */
    setSidebarWidth(width) {
        this.sidebarWidth = width;
        this.saveToStorage();
    },

    /**
     * Sidebar genislik class'i
     */
    getSidebarWidthClass() {
        const classes = {
            narrow: 'w-52',
            normal: 'w-64',
            wide: 'w-80',
        };
        return classes[this.sidebarWidth] || classes.normal;
    }
};
