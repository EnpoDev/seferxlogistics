@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Header --}}
    <x-layout.page-header :backUrl="route('bayi.isletmelerim')">
        <h1 class="text-2xl font-bold text-black dark:text-white">{{ $branch->name }}</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">İşletme detayları</p>

        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('bayi.isletme-duzenle', $branch->id)">
                Düzenle
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- İşletme Bilgileri --}}
    <x-layout.grid cols="1" mdCols="3" gap="6">
        {{-- İletişim Kartı --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">İletişim Bilgileri</h3>
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-600 dark:text-blue-400">
                        <x-ui.icon name="phone" class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Telefon</p>
                        <p class="text-base font-medium text-black dark:text-white">
                            <x-data.phone :number="$branch->phone" />
                        </p>
                    </div>
                </div>

                @if($branch->email)
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-purple-600 dark:text-purple-400">
                        <x-ui.icon name="mail" class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">E-posta</p>
                        <p class="text-base font-medium text-black dark:text-white">{{ $branch->email }}</p>
                    </div>
                </div>
                @endif
            </div>
        </x-ui.card>

        {{-- Adres Kartı --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Adres Bilgisi</h3>
            <div class="flex items-start gap-3">
                <div class="p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg text-orange-600 dark:text-orange-400">
                    <x-ui.icon name="location" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-base text-black dark:text-white">{{ $branch->address }}</p>
                    @if($branch->lat && $branch->lng)
                        <a href="https://maps.google.com/?q={{ $branch->lat }},{{ $branch->lng }}" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">Haritada Göster &rarr;</a>
                    @endif
                </div>
            </div>
        </x-ui.card>

        {{-- Durum Kartı --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Durum</h3>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded-lg text-green-600 dark:text-green-400">
                    <x-ui.icon name="success" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">İşletme Durumu</p>
                    <x-data.status-badge :status="$branch->is_active ? 'active' : 'inactive'" entity="branch" />
                </div>
            </div>
        </x-ui.card>
    </x-layout.grid>
</div>
@endsection
