@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Kullanıcı Yönetimi"
        subtitle="Sistem kullanıcılarını yönetin"
    >
        <x-slot name="icon">
            <x-ui.icon name="users" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <x-ui.button icon="plus">
                Yeni Kullanıcı Ekle
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Kullanıcı Listesi --}}
    <x-ui.card>
        <x-table.table hoverable>
            <x-table.thead>
                <x-table.tr :hoverable="false">
                    <x-table.th>Kullanıcı</x-table.th>
                    <x-table.th>E-posta</x-table.th>
                    <x-table.th>Kayıt Tarihi</x-table.th>
                    <x-table.th align="right">İşlemler</x-table.th>
                </x-table.tr>
            </x-table.thead>

            <x-table.tbody>
                @forelse($users as $user)
                <x-table.tr>
                    <x-table.td>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-700 to-black flex items-center justify-center text-white font-bold">
                                {{ substr($user->name, 0, 2) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-black dark:text-white">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">ID: #{{ $user->id }}</p>
                            </div>
                        </div>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $user->email }}</span>
                    </x-table.td>
                    <x-table.td>
                        <x-data.date-time :date="$user->created_at" />
                    </x-table.td>
                    <x-table.td align="right">
                        <x-ui.button variant="ghost" size="sm" icon="edit">
                            Düzenle
                        </x-ui.button>
                    </x-table.td>
                </x-table.tr>
                @empty
                <x-table.empty colspan="4" icon="users" message="Kullanıcı bulunamadı" />
                @endforelse
            </x-table.tbody>
        </x-table.table>

        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
                {{ $users->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
