@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Header --}}
    <x-layout.page-header :backUrl="route('bayi.isletmelerim')">
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-bold text-black dark:text-white">{{ $branch->name }}</h1>
            @if($branch->is_main)
                <x-ui.badge type="info">Merkez</x-ui.badge>
            @endif
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Isletme detaylari ve alt subeleri</p>

        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('bayi.isletme-duzenle', $branch->id)">
                Duzenle
            </x-ui.button>
            <x-ui.button :href="route('bayi.isletme-ekle', ['parent_id' => $branch->id])" icon="plus">
                Sube Ekle
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Isletme Bilgileri --}}
    <x-layout.grid cols="1" mdCols="3" gap="6" class="mb-8">
        {{-- Iletisim Karti --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Iletisim Bilgileri</h3>
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

        {{-- Adres Karti --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Adres Bilgisi</h3>
            <div class="flex items-start gap-3">
                <div class="p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg text-orange-600 dark:text-orange-400">
                    <x-ui.icon name="location" class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-base text-black dark:text-white">{{ $branch->address }}</p>
                    @if($branch->lat && $branch->lng)
                        <a href="https://maps.google.com/?q={{ $branch->lat }},{{ $branch->lng }}" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">Haritada Goster &rarr;</a>
                    @endif
                </div>
            </div>
        </x-ui.card>

        {{-- Istatistik Karti --}}
        <x-ui.card>
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Sube Ozeti</h3>
            <x-layout.grid cols="2" gap="4">
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Alt Subeler</p>
                    <p class="text-2xl font-bold text-black dark:text-white">{{ $children->count() }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Durum</p>
                    <p class="text-base font-bold mt-1">
                        <x-data.status-badge :status="$branch->is_active ? 'active' : 'inactive'" entity="branch" />
                    </p>
                </div>
            </x-layout.grid>
        </x-ui.card>
    </x-layout.grid>

    {{-- Alt Subeler Listesi --}}
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-black dark:text-white">Bagli Subeler</h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $children->count() }} sube listeleniyor</span>
        </div>

        <x-table.table hoverable>
            <x-table.thead>
                <x-table.tr :hoverable="false">
                    <x-table.th>Sube Adi</x-table.th>
                    <x-table.th>Iletisim</x-table.th>
                    <x-table.th>Adres</x-table.th>
                    <x-table.th>Durum</x-table.th>
                    <x-table.th align="right">Islemler</x-table.th>
                </x-table.tr>
            </x-table.thead>
            <x-table.tbody>
                @forelse($children as $child)
                    <x-table.tr>
                        {{-- Sube Adi --}}
                        <x-table.td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-500 dark:text-gray-400 font-bold overflow-hidden">
                                    {{ substr($child->name, 0, 2) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-black dark:text-white">{{ $child->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">ID: #{{ $child->id }}</p>
                                </div>
                            </div>
                        </x-table.td>

                        {{-- Iletisim --}}
                        <x-table.td>
                            <div>
                                <p class="text-sm text-black dark:text-white flex items-center gap-1.5">
                                    <x-ui.icon name="phone" class="w-4 h-4 text-gray-400" />
                                    <x-data.phone :number="$child->phone" :clickable="false" />
                                </p>
                                @if($child->email)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1.5">
                                    <x-ui.icon name="mail" class="w-3.5 h-3.5" />
                                    {{ $child->email }}
                                </p>
                                @endif
                            </div>
                        </x-table.td>

                        {{-- Adres --}}
                        <x-table.td>
                            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 max-w-[200px]">
                                {{ $child->address }}
                            </p>
                        </x-table.td>

                        {{-- Durum --}}
                        <x-table.td>
                            <x-data.status-badge :status="$child->is_active ? 'active' : 'inactive'" entity="branch" />
                        </x-table.td>

                        {{-- Islemler --}}
                        <x-table.td align="right" nowrap>
                            <a href="{{ route('bayi.isletme-duzenle', $child->id) }}"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                                <x-ui.icon name="edit" class="w-4 h-4" />
                                Duzenle
                            </a>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.empty
                        colspan="5"
                        icon="building"
                        message="Henuz sube eklenmemis"
                    >
                        <x-slot name="action">
                            <x-ui.button :href="route('bayi.isletme-ekle', ['parent_id' => $branch->id])" icon="plus" size="sm">
                                Sube Ekle
                            </x-ui.button>
                        </x-slot>
                    </x-table.empty>
                @endforelse
            </x-table.tbody>
        </x-table.table>
    </div>
</div>
@endsection
