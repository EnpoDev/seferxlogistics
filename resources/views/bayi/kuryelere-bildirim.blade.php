@extends('layouts.bayi')

@section('content')
<div class="p-6" x-data="bildirimForm()">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Kuryelere Bildirim</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Kuryelerinize anlik bildirim gonderin</p>
        </div>
    </div>

    <!-- Bildirim Formu -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Yeni Bildirim</h2>

                <form method="POST" action="{{ route('bayi.kuryelere-bildirim.send') }}" @submit="sending = true">
                    @csrf

                    <!-- Kurye Secimi -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Kuryeler</label>
                        <div class="flex items-center gap-3 mb-3">
                            <button type="button" @click="selectAll()" class="text-sm px-3 py-1 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                                Tumunu Sec
                            </button>
                            <button type="button" @click="deselectAll()" class="text-sm px-3 py-1 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                                Secimi Kaldir
                            </button>
                            <span class="text-sm text-gray-500 dark:text-gray-400" x-text="selectedCount + ' kurye secili'"></span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                            @foreach($couriers as $courier)
                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                                <input type="checkbox" name="courier_ids[]" value="{{ $courier->id }}"
                                       x-model="selectedCouriers"
                                       class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-black dark:text-white">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0
                                        {{ $courier->status === 'available' ? 'bg-green-500' : '' }}
                                        {{ $courier->status === 'busy' ? 'bg-orange-500' : '' }}
                                        {{ $courier->status === 'offline' ? 'bg-gray-400' : '' }}
                                        {{ $courier->status === 'on_break' ? 'bg-yellow-500' : '' }}"></span>
                                    <span class="text-sm text-black dark:text-white truncate">{{ $courier->name }}</span>
                                </div>
                            </label>
                            @endforeach
                        </div>
                        @error('courier_ids')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Mesaj -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Mesaj</label>
                        <textarea name="message" required maxlength="500" rows="3"
                                  placeholder="Bildirim mesaji..."
                                  class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white resize-none">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Gonder -->
                    <button type="submit" :disabled="sending || selectedCount === 0"
                            class="w-full px-6 py-3 bg-black dark:bg-white text-white dark:text-black rounded-lg font-medium hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!sending">Bildirim Gonder</span>
                        <span x-show="sending">Gonderiliyor...</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Hizli Sablonlar -->
        <div>
            <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Hazir Sablonlar</h2>
                <div class="space-y-2">
                    <button type="button" @click="applyTemplate('Lutfen en kisa surede sisteme baglantinizi kontrol edin.')"
                            class="w-full text-left p-3 rounded-lg bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <p class="text-sm font-medium text-black dark:text-white">Acil Bildirim</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Sistem baglanti kontrolu</p>
                    </button>
                    <button type="button" @click="applyTemplate('Yeni siparis bolgesi tanimlanmistir. Lutfen bolgelendirme ayarlarinizi kontrol edin.')"
                            class="w-full text-left p-3 rounded-lg bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <p class="text-sm font-medium text-black dark:text-white">Yeni Bolge</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Bolge guncelleme bildirimi</p>
                    </button>
                    <button type="button" @click="applyTemplate('Vardiyaniz baslamak uzere. Lutfen uygulamada musait duruma gecin.')"
                            class="w-full text-left p-3 rounded-lg bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <p class="text-sm font-medium text-black dark:text-white">Vardiya Hatirlatma</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Vardiya baslangic bildirimi</p>
                    </button>
                    <button type="button" @click="applyTemplate('Bugun saat 18:00 da toplanti yapilacaktir. Katilim zorunludur.')"
                            class="w-full text-left p-3 rounded-lg bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <p class="text-sm font-medium text-black dark:text-white">Toplanti</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Toplanti bildirimi</p>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Son Gonderilen Bildirimler -->
    @if($recentNotifications->count() > 0)
    <div class="mt-6">
        <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-black dark:text-white">Son Gonderilen Bildirimler</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-black">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kurye</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Baslik</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Mesaj</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tarih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach($recentNotifications as $notification)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                            <td class="px-6 py-3 text-sm text-black dark:text-white font-medium">{{ $notification->courier?->name ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm text-black dark:text-white">{{ $notification->title }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate">{{ $notification->message }}</td>
                            <td class="px-6 py-3">
                                @if($notification->read_at)
                                    <span class="px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">Okundu</span>
                                @else
                                    <span class="px-2 py-0.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full">Okunmadi</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $notification->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function bildirimForm() {
    return {
        selectedCouriers: [],
        sending: false,

        get selectedCount() {
            return this.selectedCouriers.length;
        },

        selectAll() {
            this.selectedCouriers = [
                @foreach($couriers as $courier)
                '{{ $courier->id }}',
                @endforeach
            ];
        },

        deselectAll() {
            this.selectedCouriers = [];
        },

        applyTemplate(message) {
            document.querySelector('textarea[name="message"]').value = message;
        }
    }
}
</script>
@endpush
@endsection
