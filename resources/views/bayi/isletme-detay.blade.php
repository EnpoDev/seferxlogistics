@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Header --}}
    <x-layout.page-header :backUrl="route('bayi.isletmelerim')">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                {{ substr($branch->name, 0, 2) }}
            </div>
            <div>
                <h1 class="text-2xl font-bold text-black dark:text-white">{{ $branch->name }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Kayıt: {{ $branch->created_at->format('d.m.Y') }} &bull;
                    <x-data.status-badge :status="$branch->is_active ? 'active' : 'inactive'" entity="branch" />
                </p>
            </div>
        </div>

        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('bayi.isletme-duzenle', $branch->id)">
                Düzenle
            </x-ui.button>
            <x-ui.button variant="danger" onclick="openDeleteModal()">
                Sil
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Özet İstatistikler --}}
    <x-layout.grid cols="2" mdCols="4" gap="4" class="mb-6">
        <x-ui.stat-card
            title="Bugün"
            :value="$stats['today_orders']"
            :subtitle="number_format($stats['today_revenue'], 2) . ' ₺'"
            color="blue"
            icon="package"
        />
        <x-ui.stat-card
            title="Bu Hafta"
            :value="$stats['week_orders']"
            :subtitle="number_format($stats['week_revenue'], 2) . ' ₺'"
            color="green"
            icon="calendar"
        />
        <x-ui.stat-card
            title="Bu Ay"
            :value="$stats['month_orders']"
            :subtitle="number_format($stats['month_revenue'], 2) . ' ₺'"
            color="purple"
            icon="chart"
        />
        <x-ui.stat-card
            title="Toplam"
            :value="$stats['total_orders']"
            :subtitle="number_format($stats['total_revenue'], 2) . ' ₺'"
            color="orange"
            icon="success"
        />
    </x-layout.grid>

    <x-layout.grid cols="1" lgCols="3" gap="6" class="mb-6">
        {{-- İletişim Bilgileri --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4 flex items-center gap-2">
                <x-ui.icon name="building" class="w-5 h-5 text-blue-500" />
                İşletme Bilgileri
            </h3>
            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600 dark:text-blue-400">
                        <x-ui.icon name="phone" class="w-4 h-4" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Telefon</p>
                        <p class="text-sm font-medium text-black dark:text-white">
                            <x-data.phone :number="$branch->phone" />
                        </p>
                    </div>
                </div>

                @if($branch->email)
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-purple-600 dark:text-purple-400">
                        <x-ui.icon name="mail" class="w-4 h-4" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">E-posta</p>
                        <p class="text-sm font-medium text-black dark:text-white">{{ $branch->email }}</p>
                    </div>
                </div>
                @endif

                <div class="flex items-start gap-3">
                    <div class="p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg text-orange-600 dark:text-orange-400">
                        <x-ui.icon name="location" class="w-4 h-4" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Adres</p>
                        <p class="text-sm text-black dark:text-white">{{ $branch->address }}</p>
                        @if($branch->lat && $branch->lng)
                            <a href="https://maps.google.com/?q={{ $branch->lat }},{{ $branch->lng }}" target="_blank" class="text-xs text-blue-600 dark:text-blue-400 hover:underline mt-1 inline-block">
                                Haritada Göster &rarr;
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui.card>

        {{-- Performans Metrikleri --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4 flex items-center gap-2">
                <x-ui.icon name="chart" class="w-5 h-5 text-green-500" />
                Performans
            </h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Tamamlanma Oranı</span>
                        <span class="text-sm font-bold text-black dark:text-white">%{{ $stats['completion_rate'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $stats['completion_rate'] }}%"></div>
                    </div>
                </div>

                <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Ort. Teslimat Süresi</span>
                    <span class="text-sm font-bold text-black dark:text-white">{{ $stats['avg_delivery_time'] }} dk</span>
                </div>

                <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Teslim Edilen</span>
                    <span class="text-sm font-bold text-green-600">{{ $stats['delivered_orders'] }}</span>
                </div>

                <div class="flex items-center justify-between py-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">İptal Edilen</span>
                    <span class="text-sm font-bold text-red-600">{{ $stats['cancelled_orders'] }}</span>
                </div>
            </div>
        </x-ui.card>

        {{-- Aktif Siparişler --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4 flex items-center gap-2">
                <x-ui.icon name="package" class="w-5 h-5 text-orange-500" />
                Anlık Durum
            </h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-yellow-500 rounded-full animate-pulse"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Bekleyen</span>
                    </div>
                    <span class="text-lg font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending_orders'] }}</span>
                </div>

                <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-blue-500 rounded-full animate-pulse"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Yolda</span>
                    </div>
                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $stats['on_delivery_orders'] }}</span>
                </div>

                <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                        Son güncelleme: {{ now()->format('H:i') }}
                    </p>
                </div>
            </div>
        </x-ui.card>
    </x-layout.grid>

    {{-- Son Siparişler --}}
    <x-ui.card>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-black dark:text-white flex items-center gap-2">
                <x-ui.icon name="list" class="w-5 h-5 text-purple-500" />
                Son Siparişler
            </h3>
            <span class="text-sm text-gray-500 dark:text-gray-400">Son 10 sipariş</span>
        </div>

        @if($recentOrders->count() > 0)
        <x-table.table hoverable>
            <x-table.thead>
                <x-table.tr :hoverable="false">
                    <x-table.th>Sipariş No</x-table.th>
                    <x-table.th>Tarih</x-table.th>
                    <x-table.th>Kurye</x-table.th>
                    <x-table.th>Tutar</x-table.th>
                    <x-table.th>Durum</x-table.th>
                </x-table.tr>
            </x-table.thead>
            <x-table.tbody>
                @foreach($recentOrders as $order)
                <x-table.tr>
                    <x-table.td>
                        <span class="text-sm font-mono text-black dark:text-white">#{{ $order->id }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $order->created_at->format('d.m.Y H:i') }}</span>
                    </x-table.td>
                    <x-table.td>
                        @if($order->courier)
                            <span class="text-sm text-black dark:text-white">{{ $order->courier->name }}</span>
                        @else
                            <span class="text-sm text-gray-400">-</span>
                        @endif
                    </x-table.td>
                    <x-table.td>
                        <span class="text-sm font-medium text-black dark:text-white">{{ number_format($order->total, 2) }} ₺</span>
                    </x-table.td>
                    <x-table.td>
                        <x-data.status-badge :status="$order->status" entity="order" />
                    </x-table.td>
                </x-table.tr>
                @endforeach
            </x-table.tbody>
        </x-table.table>
        @else
        <div class="text-center py-8">
            <x-ui.icon name="package" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
            <p class="text-gray-500 dark:text-gray-400">Henüz sipariş bulunmuyor</p>
        </div>
        @endif
    </x-ui.card>
</div>

{{-- Silme Onay Modalı --}}
<div id="deleteModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeDeleteModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 relative">
            <button onclick="closeDeleteModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <x-ui.icon name="close" class="w-5 h-5" />
            </button>

            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <x-ui.icon name="warning" class="w-8 h-8 text-red-600 dark:text-red-400" />
                </div>
                <h3 class="text-xl font-bold text-black dark:text-white">İşletmeyi Sil</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    Bu işlem geri alınamaz. İşletmeyi silmek için aşağıya işletme adını yazın:
                </p>
                <p class="text-base font-bold text-red-600 dark:text-red-400 mt-2">{{ $branch->name }}</p>
            </div>

            <form action="{{ route('bayi.isletme-sil', $branch->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="mb-4">
                    <input
                        type="text"
                        id="confirmName"
                        name="confirm_name"
                        placeholder="İşletme adını yazın..."
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-black dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                        oninput="checkDeleteConfirmation()"
                        autocomplete="off"
                    >
                </div>
                <div class="flex gap-3">
                    <button
                        type="button"
                        onclick="closeDeleteModal()"
                        class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        İptal
                    </button>
                    <button
                        type="submit"
                        id="deleteButton"
                        disabled
                        class="flex-1 px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Kalıcı Olarak Sil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const branchName = @json($branch->name);

    function openDeleteModal() {
        document.getElementById('deleteModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.body.style.overflow = '';
        document.getElementById('confirmName').value = '';
        document.getElementById('deleteButton').disabled = true;
    }

    function checkDeleteConfirmation() {
        const input = document.getElementById('confirmName').value;
        const button = document.getElementById('deleteButton');
        button.disabled = input !== branchName;
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    });
</script>
@endsection
