@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Kuryelerim"
        subtitle="Kurye ekibinizi yönetin ve performanslarını takip edin"
    >
        <x-slot name="icon">
            <x-ui.icon name="users" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <form action="{{ route('bayi.kuryelerim') }}" method="GET">
                <x-form.search-input
                    name="search"
                    placeholder="Kurye ara..."
                    :value="request('search')"
                    :autoSubmit="true"
                />
            </form>

            <x-ui.button href="{{ route('bayi.kurye-ekle') }}" icon="plus">
                Kurye Ekle
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Istatistik Kartlari --}}
    <x-layout.grid cols="1" mdCols="2" lgCols="4" gap="4" class="mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-xl p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Toplam Kurye</p>
                    <p class="text-3xl font-bold">{{ $couriers->count() }}</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <x-ui.icon name="users" class="w-8 h-8" />
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-xl p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Aktif</p>
                    <p class="text-3xl font-bold">{{ $couriers->whereIn('status', ['available', 'active'])->count() }}</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <x-ui.icon name="success" class="w-8 h-8" />
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 dark:from-orange-600 dark:to-orange-700 rounded-xl p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Teslimat</p>
                    <p class="text-3xl font-bold">{{ $couriers->where('status', 'delivering')->count() }}</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <x-ui.icon name="truck" class="w-8 h-8" />
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 rounded-xl p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Bugün Teslim</p>
                    <p class="text-3xl font-bold">{{ $couriers->sum('today_deliveries') }}</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <x-ui.icon name="check" class="w-8 h-8" />
                </div>
            </div>
        </div>
    </x-layout.grid>

    {{-- Kurye Listesi --}}
    <x-table.table hoverable>
        <x-table.thead>
            <x-table.tr :hoverable="false">
                <x-table.th>Sınıf</x-table.th>
                <x-table.th>Durum</x-table.th>
                <x-table.th>Kurye Adı</x-table.th>
                <x-table.th>Telefon</x-table.th>
                <x-table.th>Çalışma Şekli</x-table.th>
                <x-table.th>KDV</x-table.th>
                <x-table.th>Tevkifat</x-table.th>
                <x-table.th align="center">Ödeme</x-table.th>
                <x-table.th align="center">Durum Değ.</x-table.th>
                <x-table.th align="center">Paket Limiti</x-table.th>
                <x-table.th align="right">İşlemler</x-table.th>
            </x-table.tr>
        </x-table.thead>

        <x-table.tbody>
            @forelse($couriers as $courier)
                <x-table.tr>
                    {{-- Sınıf --}}
                    <x-table.td>
                        <x-data.tier-badge :tier="$courier->tier ?? 'bronze'" />
                    </x-table.td>

                    {{-- Durum --}}
                    <x-table.td>
                        <x-data.status-badge :status="$courier->status" entity="courier" />
                    </x-table.td>

                    {{-- Kurye Adı --}}
                    <x-table.td>
                        <x-data.courier-avatar
                            :courier="$courier"
                            size="sm"
                            :showStatus="false"
                            :showPhone="false"
                        />
                    </x-table.td>

                    {{-- Telefon --}}
                    <x-table.td>
                        <x-data.phone :number="$courier->phone" :clickable="false" />
                    </x-table.td>

                    {{-- Çalışma Şekli --}}
                    <x-table.td>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $courier->getWorkingTypeLabel() }}
                        </span>
                    </x-table.td>

                    {{-- KDV Orani --}}
                    <x-table.td>
                        <span class="text-sm font-medium">{{ $courier->vat_rate ?? 0 }}</span>
                    </x-table.td>

                    {{-- Tevkifat Orani --}}
                    <x-table.td>
                        <span class="text-sm font-medium">{{ $courier->withholding_rate ?? 0 }}</span>
                    </x-table.td>

                    {{-- Ödeme Düzenleme --}}
                    <x-table.td align="center">
                        @if($courier->payment_editing_enabled ?? true)
                            <x-ui.badge type="success" size="sm">Açık</x-ui.badge>
                        @else
                            <x-ui.badge type="danger" size="sm">Kapalı</x-ui.badge>
                        @endif
                    </x-table.td>

                    {{-- Durum Değiştirme --}}
                    <x-table.td align="center">
                        @if($courier->status_change_enabled ?? true)
                            <x-ui.badge type="success" size="sm">Açık</x-ui.badge>
                        @else
                            <x-ui.badge type="danger" size="sm">Kapalı</x-ui.badge>
                        @endif
                    </x-table.td>

                    {{-- Paket Taşıma Limiti --}}
                    <x-table.td align="center">
                        <span class="text-sm font-bold">{{ $courier->max_package_limit ?? 5 }}</span>
                    </x-table.td>

                    {{-- İşlemler --}}
                    <x-table.td align="right" nowrap>
                        <div class="flex items-center justify-end gap-1">
                            {{-- Detay --}}
                            <a href="{{ route('bayi.kurye-detay', $courier->id) }}"
                                class="p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                title="Detay">
                                <x-ui.icon name="info" class="w-4 h-4" />
                            </a>

                            {{-- Şifre/Mobil Erişim --}}
                            <button
                                onclick="openPasswordModal({{ $courier->id }}, '{{ $courier->name }}', {{ $courier->password ? 'true' : 'false' }}, {{ $courier->is_app_enabled ? 'true' : 'false' }})"
                                class="p-1.5 {{ $courier->password ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400' }} hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
                                title="{{ $courier->password ? 'Şifre Ayarlanmış' : 'Şifre Ayarlanmamış' }}">
                                <x-ui.icon name="phone" class="w-4 h-4" />
                            </button>

                            {{-- Düzenle --}}
                            <a href="{{ route('bayi.kurye-duzenle', $courier->id) }}"
                                class="p-1.5 text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
                                title="Düzenle">
                                <x-ui.icon name="edit" class="w-4 h-4" />
                            </a>
                        </div>
                    </x-table.td>
                </x-table.tr>
            @empty
                <x-table.empty
                    colspan="11"
                    icon="users"
                    message="Henüz kurye eklenmemiş"
                >
                    <x-slot name="action">
                        <x-ui.button href="{{ route('bayi.kurye-ekle') }}" icon="plus" size="sm">
                            Kurye Ekle
                        </x-ui.button>
                    </x-slot>
                </x-table.empty>
            @endforelse
        </x-table.tbody>
    </x-table.table>
</div>

{{-- Photo Modal --}}
<x-ui.modal name="photoModal" size="3xl" :title="null">
    <div class="flex flex-col items-center justify-center" x-data="{ src: '', caption: '' }">
        <img :src="src" :alt="caption" class="max-w-full max-h-[70vh] rounded-lg shadow-2xl object-contain">
        <p class="mt-4 text-black dark:text-white font-medium text-lg" x-text="caption"></p>
    </div>
</x-ui.modal>

{{-- Password Modal --}}
<div
    x-data="passwordModal()"
    x-on:open-password-modal.window="openModal($event.detail)"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
>
    <!-- Backdrop -->
    <div
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-background/80 backdrop-blur-sm"
        @click="open = false"
    ></div>

    <!-- Modal Panel -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative bg-card text-card-foreground rounded-xl shadow-lg border border-border w-full max-w-md transform transition-all"
            @click.stop
        >
            <div class="flex items-center justify-between px-6 py-4 border-b border-border">
                <h3 class="text-lg font-semibold leading-none tracking-tight text-foreground">Kurye Mobil Erişimi</h3>
                <button type="button" @click="open = false" class="rounded-lg p-1.5 opacity-70 hover:opacity-100 transition-opacity">
                    <x-ui.icon name="x" class="w-4 h-4" />
                </button>
            </div>

            <div class="p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <span x-text="courierName" class="font-medium text-black dark:text-white"></span> için mobil uygulama erişim ayarları
                </p>

                <p :class="hasPassword ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" class="text-sm mb-4">
                    <span x-text="hasPassword ? '✓ Şifre ayarlanmış' : '✗ Şifre ayarlanmamış'"></span>
                </p>

                {{-- App Access Toggle --}}
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-xl mb-4">
                    <div>
                        <p class="text-sm font-medium text-black dark:text-white">Uygulama Erişimi</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Kurye uygulamaya girebilir</p>
                    </div>
                    <button type="button" @click="toggleAppAccess()"
                            :class="isAppEnabled ? 'bg-green-500' : 'bg-gray-300'"
                            class="relative w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none">
                        <span :class="isAppEnabled ? 'translate-x-5' : 'translate-x-0'"
                              class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform duration-200"></span>
                    </button>
                </div>

                {{-- Password Form --}}
                <form :action="'/bayi/kuryeler/' + courierId + '/sifre'" method="POST" class="space-y-4">
                    @csrf
                    <x-form.input
                        type="password"
                        name="password"
                        label="Yeni Şifre"
                        required
                        hint="En az 6 karakter"
                    />

                    <x-form.input
                        type="password"
                        name="password_confirmation"
                        label="Şifre Tekrar"
                        required
                    />

                    <div class="flex gap-3 mt-6">
                        <x-ui.button type="button" variant="secondary" @click="open = false" class="flex-1">
                            İptal
                        </x-ui.button>
                        <x-ui.button type="submit" class="flex-1">
                            Şifreyi Kaydet
                        </x-ui.button>
                    </div>
                </form>

                <x-feedback.alert type="info" class="mt-4">
                    <strong>Kurye Uygulaması:</strong> Kuryeler <code class="bg-blue-100 dark:bg-blue-900 px-1 rounded">{{ url('/kurye') }}</code> adresinden telefon numarası ve şifre ile giriş yapabilir.
                </x-feedback.alert>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Password Modal Alpine Component
    function passwordModal() {
        return {
            open: false,
            courierId: null,
            courierName: '',
            hasPassword: false,
            isAppEnabled: false,

            openModal(data) {
                if (!data || !data.id) return;
                this.courierId = data.id;
                this.courierName = data.name;
                this.hasPassword = data.hasPassword;
                this.isAppEnabled = data.isAppEnabled;
                this.open = true;
            },

            async toggleAppAccess() {
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                    const response = await fetch('/bayi/kuryeler/' + this.courierId + '/app-toggle', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.isAppEnabled = data.is_app_enabled;
                        showToast(data.message, 'success');
                    }
                } catch (error) {
                    showToast('Bir hata olustu', 'error');
                }
            }
        };
    }

    // Photo Modal
    function showPhotoModal(imgSrc, imgCaption) {
        const modal = document.querySelector('[x-data*="src"]');
        if (modal && modal._x_dataStack) {
            modal._x_dataStack[0].src = imgSrc;
            modal._x_dataStack[0].caption = imgCaption;
        }
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'photoModal' }));
    }

    // Open Password Modal
    function openPasswordModal(courierId, courierName, hasPassword, isAppEnabled) {
        window.dispatchEvent(new CustomEvent('open-password-modal', {
            detail: {
                id: courierId,
                name: courierName,
                hasPassword: hasPassword,
                isAppEnabled: isAppEnabled
            }
        }));
    }
</script>
@endpush
@endsection
