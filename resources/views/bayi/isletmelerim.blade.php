@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="İşletmelerim"
        subtitle="İşletmelerinizi yönetin ve takip edin"
    >
        <x-slot name="icon">
            <x-ui.icon name="building" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <form action="{{ route('bayi.isletmelerim') }}" method="GET">
                <x-form.search-input
                    name="search"
                    placeholder="İşletme ara..."
                    :value="request('search')"
                    :autoSubmit="true"
                />
            </form>

            <x-ui.button href="{{ route('bayi.isletme-ekle') }}" icon="plus">
                İşletme Ekle
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- İstatistik Kartları --}}
    <x-layout.grid cols="1" mdCols="2" gap="4" class="mb-6">
        <x-ui.stat-card title="Toplam İşletme" :value="$branches->count()" color="blue" icon="building" />
        <x-ui.stat-card title="Aktif İşletme" :value="$branches->where('is_active', true)->count()" color="green" icon="success" />
    </x-layout.grid>

    {{-- Şube Listesi --}}
    <x-table.table hoverable>
        <x-table.thead>
            <x-table.tr :hoverable="false">
                <x-table.th>İşletme Adı</x-table.th>
                <x-table.th>İletişim</x-table.th>
                <x-table.th>Durum</x-table.th>
                <x-table.th align="right">İşlemler</x-table.th>
            </x-table.tr>
        </x-table.thead>

        <x-table.tbody>
            @forelse($branches as $branch)
                <x-table.tr class="cursor-pointer" onclick="window.location='{{ route('bayi.isletme-detay', $branch->id) }}'">
                    {{-- İşletme Adı --}}
                    <x-table.td>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold overflow-hidden">
                                {{ substr($branch->name, 0, 2) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-black dark:text-white">{{ $branch->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">ID: #{{ $branch->id }}</p>
                            </div>
                        </div>
                    </x-table.td>

                    {{-- İletişim --}}
                    <x-table.td>
                        <div>
                            <p class="text-sm text-black dark:text-white flex items-center gap-1.5">
                                <x-ui.icon name="phone" class="w-4 h-4 text-gray-400" />
                                <x-data.phone :number="$branch->phone" :clickable="false" />
                            </p>
                            @if($branch->email)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1.5">
                                <x-ui.icon name="mail" class="w-3.5 h-3.5" />
                                {{ $branch->email }}
                            </p>
                            @endif
                        </div>
                    </x-table.td>

                    {{-- Durum --}}
                    <x-table.td>
                        <x-data.status-badge :status="$branch->is_active ? 'active' : 'inactive'" entity="branch" />
                    </x-table.td>

                    {{-- İşlemler --}}
                    <x-table.td align="right" nowrap onclick="event.stopPropagation()">
                        <div class="flex items-center justify-end gap-2">
                            <form action="{{ route('bayi.isletme.giris', $branch->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium bg-black dark:bg-white text-white dark:text-black hover:bg-gray-800 dark:hover:bg-gray-200 rounded-lg transition-colors">
                                    <x-ui.icon name="login" class="w-4 h-4" />
                                    Geçiş Yap
                                </button>
                            </form>
                            <a href="{{ route('bayi.isletme-duzenle', $branch->id) }}"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                                <x-ui.icon name="edit" class="w-4 h-4" />
                                Düzenle
                            </a>
                        </div>
                    </x-table.td>
                </x-table.tr>
            @empty
                <x-table.empty
                    colspan="4"
                    icon="building"
                    message="Henüz işletme eklenmemiş"
                >
                    <x-slot name="action">
                        <x-ui.button href="{{ route('bayi.isletme-ekle') }}" icon="plus" size="sm">
                            İşletme Ekle
                        </x-ui.button>
                    </x-slot>
                </x-table.empty>
            @endforelse
        </x-table.tbody>
    </x-table.table>
</div>
@endsection
