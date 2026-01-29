@extends('layouts.app')

@section('content')
<div class="p-6" x-data="integrationManager()">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-black dark:text-white">Entegrasyonlar</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Yemek platformlarini baglayin ve siparisleri otomatik alin</p>
        </div>
    </div>

    <!-- Platform Kartlari -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Yemeksepeti (Yakinda) -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6 opacity-60">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-8 h-8 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-black dark:text-white">Yemeksepeti</h3>
                        <span class="px-2 py-0.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full">Yakinda</span>
                    </div>
                </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Yemeksepeti siparislerini otomatik olarak alin.</p>
            <button disabled class="w-full px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-lg cursor-not-allowed">
                Yakinda Aktif Olacak
            </button>
        </div>

        <!-- Getir Yemek (Yakinda) -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6 opacity-60">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-8 h-8 text-purple-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-black dark:text-white">Getir Yemek</h3>
                        <span class="px-2 py-0.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full">Yakinda</span>
                    </div>
                </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Getir Yemek siparislerini otomatik olarak alin.</p>
            <button disabled class="w-full px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-lg cursor-not-allowed">
                Yakinda Aktif Olacak
            </button>
        </div>

        <!-- Trendyol Yemek -->
        @php $trendyol = $integrations['trendyol'] ?? null; @endphp
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-8 h-8 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-black dark:text-white">Trendyol Yemek</h3>
                        @if($trendyol['is_connected'] ?? false)
                            <span class="px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">Aktif</span>
                        @else
                            <span class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-400 rounded-full">Aktif Degil</span>
                        @endif
                    </div>
                </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Trendyol Yemek siparislerini otomatik olarak alin.</p>

            @if($trendyol['is_connected'] ?? false)
                <div class="flex gap-2">
                    <button @click="syncOrders('trendyol')" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors text-sm">
                        Senkronize Et
                    </button>
                    <button @click="disconnect('trendyol')" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm">
                        Kes
                    </button>
                </div>
            @else
                <button @click="openConnectModal('trendyol', {{ json_encode($trendyol['credentials'] ?? []) }})" class="w-full px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
                    Baglan
                </button>
            @endif
        </div>
    </div>

    <!-- Bilgi Kutusu -->
    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <p class="text-blue-700 dark:text-blue-400">
            <strong>Entegrasyon Bilgisi:</strong> Platformlarla entegrasyon icin API anahtarlarina ihtiyaciniz var. Bu bilgileri ilgili platform partnerlik sayfalarindan alabilirsiniz.
        </p>
    </div>

    <!-- Baglanti Modal -->
    <div x-show="showConnectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showConnectModal = false"></div>
            <div class="relative bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">
                    <span x-text="platformNames[currentPlatform]"></span> Baglantisi
                </h3>

                <form @submit.prevent="connect()">
                    <div class="space-y-4">
                        <template x-for="(config, key) in currentCredentials" :key="key">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" x-text="config.label"></label>
                                <input :type="config.type || 'text'"
                                       x-model="credentials[key]"
                                       :required="config.required"
                                       class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-6">
                        <button type="button" @click="testConnection()" :disabled="isLoading" class="px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors disabled:opacity-50">
                            Test Et
                        </button>
                        <button type="button" @click="showConnectModal = false" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition-colors">
                            Iptal
                        </button>
                        <button type="submit" :disabled="isLoading" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors disabled:opacity-50">
                            <span x-show="!isLoading">Baglan</span>
                            <span x-show="isLoading">Baglaniyor...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function integrationManager() {
    return {
        currentPlatform: null,
        currentCredentials: {},
        credentials: {},
        isLoading: false,
        showConnectModal: false,
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
                alert(data.message);
            } catch (error) {
                alert('Baglanti testi basarisiz.');
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
                    alert(data.message);
                    this.showConnectModal = false;
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                alert('Baglanti kurulurken bir hata olustu.');
            } finally {
                this.isLoading = false;
            }
        },

        async disconnect(platform) {
            if (!confirm('Bu entegrasyonu kesmek istediginizden emin misiniz?')) return;

            try {
                const response = await fetch(`/integrations/${platform}/disconnect`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                alert(data.message);

                if (data.success) {
                    location.reload();
                }
            } catch (error) {
                alert('Baglanti kesilirken bir hata olustu.');
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
                alert(data.message);
            } catch (error) {
                alert('Senkronizasyon basarisiz.');
            }
        }
    }
}
</script>
@endpush
@endsection
