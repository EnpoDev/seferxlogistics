/**
 * Modal Store
 * Global modal yonetimi icin Alpine.js store
 */
export default {
    // Acik modallarin listesi
    openModals: [],

    // Onay dialog state
    confirmDialog: {
        open: false,
        title: 'Emin misiniz?',
        message: 'Bu islem geri alinamaz.',
        confirmText: 'Onayla',
        cancelText: 'Iptal',
        type: 'danger', // danger, warning, info
        loading: false,
        onConfirm: null,
        onCancel: null,
    },

    /**
     * Modal ac
     * @param {string} name - Modal ismi
     */
    open(name) {
        if (!this.openModals.includes(name)) {
            this.openModals.push(name);
            document.body.classList.add('overflow-hidden');
        }
    },

    /**
     * Modal kapat
     * @param {string} name - Modal ismi
     */
    close(name) {
        this.openModals = this.openModals.filter(m => m !== name);
        if (this.openModals.length === 0) {
            document.body.classList.remove('overflow-hidden');
        }
    },

    /**
     * Modal toggle
     */
    toggle(name) {
        if (this.isOpen(name)) {
            this.close(name);
        } else {
            this.open(name);
        }
    },

    /**
     * Modal acik mi kontrol et
     */
    isOpen(name) {
        return this.openModals.includes(name);
    },

    /**
     * Tum modallari kapat
     */
    closeAll() {
        this.openModals = [];
        document.body.classList.remove('overflow-hidden');
    },

    /**
     * Onay dialog goster
     * @param {Object} options - Dialog ayarlari
     */
    confirm(options = {}) {
        return new Promise((resolve, reject) => {
            this.confirmDialog = {
                open: true,
                title: options.title || 'Emin misiniz?',
                message: options.message || 'Bu islem geri alinamaz.',
                confirmText: options.confirmText || 'Onayla',
                cancelText: options.cancelText || 'Iptal',
                type: options.type || 'danger',
                loading: false,
                onConfirm: async () => {
                    this.confirmDialog.loading = true;
                    try {
                        if (options.onConfirm) {
                            await options.onConfirm();
                        }
                        resolve(true);
                    } catch (error) {
                        reject(error);
                    } finally {
                        this.confirmDialog.loading = false;
                        this.closeConfirm();
                    }
                },
                onCancel: () => {
                    if (options.onCancel) {
                        options.onCancel();
                    }
                    resolve(false);
                    this.closeConfirm();
                },
            };
        });
    },

    /**
     * Silme onay dialog goster (kisayol)
     */
    confirmDelete(options = {}) {
        return this.confirm({
            title: options.title || 'Silmek istediginize emin misiniz?',
            message: options.message || 'Bu islem geri alinamaz ve veri kalici olarak silinecektir.',
            confirmText: options.confirmText || 'Sil',
            cancelText: options.cancelText || 'Iptal',
            type: 'danger',
            onConfirm: options.onConfirm,
            onCancel: options.onCancel,
        });
    },

    /**
     * Onay dialog kapat
     */
    closeConfirm() {
        this.confirmDialog.open = false;
        this.confirmDialog.loading = false;
    },

    /**
     * Dialog tipine gore ikon class'i
     */
    getConfirmIconClass() {
        const classes = {
            danger: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
            warning: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400',
            info: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
        };
        return classes[this.confirmDialog.type] || classes.danger;
    },

    /**
     * Onay butonu class'i
     */
    getConfirmButtonClass() {
        const classes = {
            danger: 'bg-red-600 hover:bg-red-700',
            warning: 'bg-yellow-600 hover:bg-yellow-700',
            info: 'bg-blue-600 hover:bg-blue-700',
        };
        return classes[this.confirmDialog.type] || classes.danger;
    }
};
