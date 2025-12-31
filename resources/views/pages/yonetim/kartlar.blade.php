@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn" x-data="{ showAddCard: false }">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Kayıtlı Kartlarım"
        subtitle="Ödeme yöntemlerinizi yönetin"
    >
        <x-slot name="icon">
            <x-ui.icon name="credit-card" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <x-ui.button @click="showAddCard = true" icon="plus">Yeni Kart Ekle</x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Mesajlar --}}
    @if(session('success'))
        <x-feedback.alert type="success" class="mb-6">{{ session('success') }}</x-feedback.alert>
    @endif
    @if(session('error'))
        <x-feedback.alert type="danger" class="mb-6">{{ session('error') }}</x-feedback.alert>
    @endif

    {{-- Kart Listesi --}}
    <x-layout.grid cols="1" mdCols="2" gap="6">
        @forelse($cards as $card)
        <x-ui.card>
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Kart Numarası</p>
                    <p class="text-lg font-semibold text-black dark:text-white">{{ $card->getMaskedNumber() }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">{{ $card->getBrandLabel() }}</span>
                    @if($card->is_default)
                        <x-ui.badge type="default">Varsayılan</x-ui.badge>
                    @endif
                </div>
            </div>

            <x-layout.grid cols="2" gap="4" class="mb-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Son Kullanma</p>
                    <p class="text-sm {{ $card->isExpired() ? 'text-red-500' : 'text-black dark:text-white' }}">
                        {{ $card->getExpiryDate() }}
                        @if($card->isExpired())
                            <span class="text-xs text-red-500">(Süresi dolmuş)</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Kart Sahibi</p>
                    <p class="text-sm text-black dark:text-white">{{ $card->card_holder_name }}</p>
                </div>
            </x-layout.grid>

            <div class="flex gap-2">
                @if(!$card->is_default)
                <form action="{{ route('billing.cards.default', $card) }}" method="POST" class="flex-1">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" class="w-full">Varsayılan Yap</x-ui.button>
                </form>
                @endif
                <form action="{{ route('billing.cards.destroy', $card) }}" method="POST" class="flex-1"
                    onsubmit="return confirm('Bu kartı silmek istediğinizden emin misiniz?')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" class="w-full">Sil</x-ui.button>
                </form>
            </div>
        </x-ui.card>
        @empty
        <div class="col-span-full">
            <x-ui.empty-state
                title="Kayıtlı kartınız yok"
                description="Henüz kayıtlı kartınız bulunmuyor"
                icon="credit-card"
                actionText="İlk Kartınızı Ekleyin"
                actionUrl="#"
                @click="showAddCard = true"
            />
        </div>
        @endforelse

        @if($cards->count() > 0)
        {{-- Yeni Kart Ekle Alani --}}
        <x-ui.card class="border-2 border-dashed flex flex-col items-center justify-center min-h-[200px]">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Yeni ödeme yöntemi ekle</p>
            <x-ui.button @click="showAddCard = true">Kart Ekle</x-ui.button>
        </x-ui.card>
        @endif
    </x-layout.grid>

    {{-- Kart Ekleme Modal --}}
    <x-ui.modal name="addCardModal" title="Yeni Kart Ekle" size="md">
        <form action="{{ route('billing.cards.store') }}" method="POST" class="space-y-4">
            @csrf
            <x-form.input name="card_holder_name" label="Kart Üzerindeki İsim" placeholder="AHMET YILMAZ" required />
            <x-form.input name="card_number" label="Kart Numarası" placeholder="1234 5678 9012 3456" maxlength="19" required />

            <x-layout.grid cols="3" gap="4">
                <x-form.select name="expiry_month" label="Ay" required :options="collect(range(1, 12))->mapWithKeys(fn($i) => [$i => str_pad($i, 2, '0', STR_PAD_LEFT)])->toArray()" />
                <x-form.select name="expiry_year" label="Yıl" required :options="collect(range(date('Y'), date('Y') + 10))->mapWithKeys(fn($i) => [$i => $i])->toArray()" />
                <x-form.input name="cvv" label="CVV" placeholder="123" maxlength="4" required />
            </x-layout.grid>

            <x-form.checkbox name="is_default" label="Varsayılan ödeme yöntemi olarak ayarla" value="1" />

            <div class="flex gap-3 pt-4">
                <x-ui.button type="button" variant="secondary" @click="showAddCard = false" class="flex-1">İptal</x-ui.button>
                <x-ui.button type="submit" class="flex-1">Kartı Kaydet</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.effect(() => {
        const showAddCard = Alpine.store('showAddCard');
    });
});

// Manual modal control for showAddCard
const originalShowAddCard = document.querySelector('[x-data]');
if (originalShowAddCard) {
    const xData = originalShowAddCard.__x;
    if (xData) {
        xData.$watch('showAddCard', (value) => {
            if (value) {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'addCardModal' }));
            } else {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'addCardModal' }));
            }
        });
    }
}
</script>
@endpush
@endsection
