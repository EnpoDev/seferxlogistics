@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn" x-data="{ showCancelModal: false }">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Aboneliklerim"
        subtitle="Aktif aboneliklerinizi görüntüleyin"
    >
        <x-slot name="icon">
            <x-ui.icon name="subscription" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>
    </x-layout.page-header>

    {{-- Mesajlar --}}
    @if(session('success'))
        <x-feedback.alert type="success" class="mb-6">{{ session('success') }}</x-feedback.alert>
    @endif
    @if(session('error'))
        <x-feedback.alert type="danger" class="mb-6">{{ session('error') }}</x-feedback.alert>
    @endif

    @if($subscription && $subscription->plan)
    {{-- Aktif Abonelik --}}
    <x-ui.card class="mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-1">{{ $subscription->plan->name }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $subscription->plan->getPeriodLabel() }} abonelik</p>
            </div>
            @php
                $statusTypes = [
                    'active' => 'success',
                    'cancelled' => 'danger',
                    'trial' => 'info',
                ];
            @endphp
            <x-ui.badge :type="$statusTypes[$subscription->status] ?? 'default'">
                {{ $subscription->getStatusLabel() }}
            </x-ui.badge>
        </div>

        <x-layout.grid cols="1" mdCols="3" gap="6" class="mb-6">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ $subscription->plan->getPeriodLabel() }} Ücret</p>
                <p class="text-2xl font-bold text-black dark:text-white">{{ $subscription->plan->getFormattedPrice() }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Sonraki Ödeme</p>
                <p class="text-lg font-semibold text-black dark:text-white">
                    {{ $subscription->next_billing_date ? $subscription->next_billing_date->format('d M Y') : '-' }}
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Başlangıç Tarihi</p>
                <p class="text-lg font-semibold text-black dark:text-white">
                    {{ $subscription->starts_at ? $subscription->starts_at->format('d M Y') : '-' }}
                </p>
            </div>
        </x-layout.grid>

        @if($subscription->isActive())
        <div class="flex gap-3">
            <x-ui.button href="{{ route('yonetim.paketler') }}">Planı Yükselt</x-ui.button>
            <x-ui.button @click="showCancelModal = true" variant="secondary">İptal Et</x-ui.button>
        </div>
        @elseif($subscription->isCancelled())
        <x-feedback.alert type="warning">
            Aboneliğiniz {{ $subscription->ends_at ? $subscription->ends_at->format('d M Y') : 'dönem sonunda' }} tarihinde sona erecektir.
            @if($subscription->cancel_reason)
                <br><span class="text-xs">İptal nedeni: {{ $subscription->cancel_reason }}</span>
            @endif
        </x-feedback.alert>
        @endif
    </x-ui.card>

    {{-- Plan Özellikleri --}}
    @if($subscription->plan->features)
    <x-ui.card>
        <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Plan Özellikleri</h3>
        <x-layout.grid cols="1" mdCols="2" gap="4">
            @foreach($subscription->plan->features as $feature)
            <div class="flex items-center space-x-3">
                <div class="w-5 h-5 border-2 border-black dark:border-white rounded-full flex items-center justify-center">
                    <div class="w-2 h-2 bg-black dark:bg-white rounded-full"></div>
                </div>
                <span class="text-sm text-black dark:text-white">{{ $feature }}</span>
            </div>
            @endforeach
        </x-layout.grid>

        @if($subscription->plan->max_users || $subscription->plan->max_orders || $subscription->plan->max_branches)
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <x-layout.grid cols="3" gap="4">
                @if($subscription->plan->max_users)
                <div class="text-center">
                    <p class="text-2xl font-bold text-black dark:text-white">{{ $subscription->plan->max_users }}</p>
                    <p class="text-xs text-gray-500">Kullanıcı</p>
                </div>
                @endif
                @if($subscription->plan->max_orders)
                <div class="text-center">
                    <p class="text-2xl font-bold text-black dark:text-white">{{ $subscription->plan->max_orders === -1 ? '∞' : $subscription->plan->max_orders }}</p>
                    <p class="text-xs text-gray-500">Sipariş/Ay</p>
                </div>
                @endif
                @if($subscription->plan->max_branches)
                <div class="text-center">
                    <p class="text-2xl font-bold text-black dark:text-white">{{ $subscription->plan->max_branches }}</p>
                    <p class="text-xs text-gray-500">Şube</p>
                </div>
                @endif
            </x-layout.grid>
        </div>
        @endif
    </x-ui.card>
    @endif

    @else
    {{-- Abonelik Yok --}}
    <x-ui.card class="text-center py-8">
        <x-ui.icon name="subscription" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
        <h3 class="text-lg font-semibold text-black dark:text-white mb-2">Aktif aboneliğiniz bulunmuyor</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">Hemen bir plan seçerek tüm özelliklere erişin.</p>
        <x-ui.button href="{{ route('yonetim.paketler') }}">Planları İncele</x-ui.button>
    </x-ui.card>
    @endif

    {{-- İptal Modal --}}
    <x-ui.modal name="cancelModal" title="Aboneliği İptal Et" size="md">
        <form action="{{ route('billing.subscription.cancel') }}" method="POST">
            @csrf
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Aboneliğinizi iptal etmek istediğinizden emin misiniz? Aboneliğiniz dönem sonuna kadar aktif kalacaktır.
            </p>
            <x-form.textarea name="reason" label="İptal Nedeni (Opsiyonel)" :rows="3" placeholder="İptal nedeninizi paylaşırsanız seviniriz..." />

            <div class="flex gap-3 pt-4">
                <x-ui.button type="button" @click="showCancelModal = false" variant="secondary" class="flex-1">Vazgeç</x-ui.button>
                <x-ui.button type="submit" variant="danger" class="flex-1">Aboneliği İptal Et</x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('subscriptionPage', () => ({
        showCancelModal: false
    }));
});
</script>
@endpush
@endsection
