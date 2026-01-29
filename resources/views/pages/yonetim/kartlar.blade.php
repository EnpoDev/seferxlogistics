@extends('layouts.app')

@section('content')
<div class="p-6" x-data="{ showAddCard: false }">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-black dark:text-white">Kayitli Kartlarim</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Odeme yontemlerinizi yonetin</p>
        </div>
        <button @click="showAddCard = true" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
            Yeni Kart Ekle
        </button>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg mb-6">
            <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg mb-6">
            <p class="text-red-700 dark:text-red-400">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Kart Listesi -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($cards as $card)
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Kart Numarasi</p>
                    <p class="text-lg font-semibold text-black dark:text-white">{{ $card->getMaskedNumber() }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">{{ $card->getBrandLabel() }}</span>
                    @if($card->is_default)
                        <span class="px-2 py-1 text-xs bg-black dark:bg-white text-white dark:text-black rounded-full">Varsayilan</span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Son Kullanma</p>
                    <p class="text-sm {{ $card->isExpired() ? 'text-red-500' : 'text-black dark:text-white' }}">
                        {{ $card->getExpiryDate() }}
                        @if($card->isExpired())
                            <span class="text-xs text-red-500">(Suresi dolmus)</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Kart Sahibi</p>
                    <p class="text-sm text-black dark:text-white">{{ $card->card_holder_name }}</p>
                </div>
            </div>

            <div class="flex gap-2">
                @if(!$card->is_default)
                <form action="{{ route('billing.cards.default', $card) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
                        Varsayilan Yap
                    </button>
                </form>
                @endif
                <form action="{{ route('billing.cards.destroy', $card) }}" method="POST" class="flex-1"
                    onsubmit="return confirm('Bu karti silmek istediginizden emin misiniz?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                        Sil
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Kayitli kartiniz yok</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">Henuz kayitli kartiniz bulunmuyor</p>
            <button @click="showAddCard = true" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
                Ilk Kartinizi Ekleyin
            </button>
        </div>
        @endforelse

        @if($cards->count() > 0)
        <!-- Yeni Kart Ekle Alani -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700 p-6 flex flex-col items-center justify-center min-h-[200px]">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Yeni odeme yontemi ekle</p>
            <button @click="showAddCard = true" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
                Kart Ekle
            </button>
        </div>
        @endif
    </div>

    <!-- Kart Ekleme Modal -->
    <div x-show="showAddCard" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showAddCard = false"></div>
            <div class="relative bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Yeni Kart Ekle</h3>

                <form action="{{ route('billing.cards.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kart Uzerindeki Isim</label>
                        <input type="text" name="card_holder_name" placeholder="AHMET YILMAZ" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kart Numarasi</label>
                        <input type="text" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" required
                            class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ay</label>
                            <select name="expiry_month" required
                                class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Yil</label>
                            <select name="expiry_year" required
                                class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                                @for($i = date('Y'); $i <= date('Y') + 10; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CVV</label>
                            <input type="text" name="cvv" placeholder="123" maxlength="4" required
                                class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_default" value="1" id="is_default" class="rounded border-gray-300 dark:border-gray-700">
                        <label for="is_default" class="text-sm text-gray-700 dark:text-gray-300">Varsayilan odeme yontemi olarak ayarla</label>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showAddCard = false" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
                            Iptal
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
                            Karti Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
