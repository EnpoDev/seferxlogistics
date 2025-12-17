@extends('layouts.app')

@section('content')
<div class="p-6" x-data="integrationManager()">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-black dark:text-white">Entegrasyonlar</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Yemek platformlarını bağlayın ve siparişleri otomatik alın</p>
    </div>

    <!-- Platform Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Yemeksepeti -->
        @php $yemeksepeti = $integrations['yemeksepeti'] ?? null; @endphp
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white">Yemeksepeti</h3>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ ($yemeksepeti['is_connected'] ?? false) ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-500' }}">
                                {{ ($yemeksepeti['is_connected'] ?? false) ? 'Aktif' : 'Aktif Değil' }}
                            </span>
                        </div>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Yemeksepeti siparişlerini otomatik olarak alın.</p>
                
                @if($yemeksepeti['is_connected'] ?? false)
                    <div class="flex gap-2">
                        <button @click="syncOrders('yemeksepeti')" 
                                class="flex-1 px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm font-medium">
                            Senkronize Et
                        </button>
                        <button @click="disconnect('yemeksepeti')" 
                                class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 transition-colors text-sm font-medium">
                            Bağlantıyı Kes
                        </button>
                    </div>
                @else
                    <button @click="openConnectModal('yemeksepeti', @js($yemeksepeti['credentials'] ?? []))" 
                            class="w-full px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80 transition-opacity font-medium">
                        Bağlan
                    </button>
                @endif
            </div>
        </div>

        <!-- Getir Yemek -->
        @php $getir = $integrations['getir'] ?? null; @endphp
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-8 h-8 text-purple-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white">Getir Yemek</h3>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ ($getir['is_connected'] ?? false) ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-500' }}">
                                {{ ($getir['is_connected'] ?? false) ? 'Aktif' : 'Aktif Değil' }}
                            </span>
                        </div>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Getir Yemek siparişlerini otomatik olarak alın.</p>
                
                @if($getir['is_connected'] ?? false)
                    <div class="flex gap-2">
                        <button @click="syncOrders('getir')" 
                                class="flex-1 px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm font-medium">
                            Senkronize Et
                        </button>
                        <button @click="disconnect('getir')" 
                                class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 transition-colors text-sm font-medium">
                            Bağlantıyı Kes
                        </button>
                    </div>
                @else
                    <button @click="openConnectModal('getir', @js($getir['credentials'] ?? []))" 
                            class="w-full px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80 transition-opacity font-medium">
                        Bağlan
                    </button>
                @endif
            </div>
        </div>

        <!-- Trendyol Yemek -->
        @php $trendyol = $integrations['trendyol'] ?? null; @endphp
        <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-8 h-8 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-black dark:text-white">Trendyol Yemek</h3>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ ($trendyol['is_connected'] ?? false) ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-500' }}">
                                {{ ($trendyol['is_connected'] ?? false) ? 'Aktif' : 'Aktif Değil' }}
                            </span>
                        </div>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Trendyol Yemek siparişlerini otomatik olarak alın.</p>
                
                @if($trendyol['is_connected'] ?? false)
                    <div class="flex gap-2">
                        <button @click="syncOrders('trendyol')" 
                                class="flex-1 px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm font-medium">
                            Senkronize Et
                        </button>
                        <button @click="disconnect('trendyol')" 
                                class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 transition-colors text-sm font-medium">
                            Bağlantıyı Kes
                        </button>
                    </div>
                @else
                    <button @click="openConnectModal('trendyol', @js($trendyol['credentials'] ?? []))" 
                            class="w-full px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80 transition-opacity font-medium">
                        Bağlan
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Info Box -->
    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h4 class="font-medium text-blue-700 dark:text-blue-400">Entegrasyon Bilgisi</h4>
                <p class="text-sm text-blue-600 dark:text-blue-300 mt-1">
                    Platformlarla entegrasyon için API anahtarlarına ihtiyacınız var. Bu bilgileri ilgili platform partnerlik sayfalarından alabilirsiniz.
                </p>
            </div>
        </div>
    </div>

    <!-- Connect Modal -->
    <div x-show="showConnectModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showConnectModal = false"></div>
            
            <div class="relative bg-white dark:bg-[#1a1a1a] rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">
                    <span x-text="platformNames[currentPlatform]"></span> Bağlantısı
                </h3>
                
                <form @submit.prevent="connect()">
                    <div class="space-y-4">
                        <template x-for="(config, key) in currentCredentials" :key="key">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" x-text="config.label"></label>
                                <input :type="config.type || 'text'" 
                                       x-model="credentials[key]"
                                       :required="config.required"
                                       class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                            </div>
                        </template>
                    </div>
                    
                    <div class="flex items-center justify-end gap-3 mt-6">
                        <button type="button" @click="testConnection()" 
                                :disabled="isLoading"
                                class="px-4 py-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors">
                            Test Et
                        </button>
                        <button type="button" @click="showConnectModal = false"
                                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                            İptal
                        </button>
                        <button type="submit"
                                :disabled="isLoading"
                                class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80 transition-opacity disabled:opacity-50">
                            <span x-show="!isLoading">Bağlan</span>
                            <span x-show="isLoading">Bağlanıyor...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function integrationManager() {
    return {
        showConnectModal: false,
        currentPlatform: null,
        currentCredentials: {},
        credentials: {},
        isLoading: false,
        platformNames: {
            yemeksepeti: 'Yemeksepeti',
            getir: 'Getir Yemek',
            trendyol: 'Trendyol Yemek'
        },
        
        openConnectModal(platform, credentialFields) {
            this.currentPlatform = platform;
            this.currentCredentials = credentialFields;
            this.credentials = {};
            this.showConnectModal = true;
        },
        
        async testConnection() {
            this.isLoading = true;
            try {
                const response = await fetch(`/integrations/${this.currentPlatform}/test`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ credentials: this.credentials })
                });
                
                const data = await response.json();
                window.showToast(data.message, data.success ? 'success' : 'error');
            } catch (error) {
                window.showToast('Bağlantı testi başarısız.', 'error');
            } finally {
                this.isLoading = false;
            }
        },
        
        async connect() {
            this.isLoading = true;
            try {
                const response = await fetch(`/integrations/${this.currentPlatform}/connect`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ credentials: this.credentials })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.showToast(data.message, 'success');
                    this.showConnectModal = false;
                    location.reload();
                } else {
                    window.showToast(data.message, 'error');
                }
            } catch (error) {
                window.showToast('Bağlantı kurulurken bir hata oluştu.', 'error');
            } finally {
                this.isLoading = false;
            }
        },
        
        async disconnect(platform) {
            if (!confirm('Bu entegrasyonu kesmek istediğinizden emin misiniz?')) return;
            
            try {
                const response = await fetch(`/integrations/${platform}/disconnect`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                window.showToast(data.message, data.success ? 'success' : 'error');
                
                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                window.showToast('Bağlantı kesilirken bir hata oluştu.', 'error');
            }
        },
        
        async syncOrders(platform) {
            try {
                const response = await fetch(`/integrations/${platform}/sync`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                window.showToast(data.message, data.success ? 'success' : 'error');
            } catch (error) {
                window.showToast('Senkronizasyon başarısız.', 'error');
            }
        }
    }
}
</script>
@endsection
