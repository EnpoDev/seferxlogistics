@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Ürünler"
        subtitle="Ürünlerinizi yönetin"
    >
        <x-slot name="icon">
            <x-ui.icon name="box" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <x-ui.button icon="plus">Yeni Ürün</x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Ürün Listesi --}}
    <x-ui.card>
        <x-table.table hoverable>
            <x-table.thead>
                <x-table.tr :hoverable="false">
                    <x-table.th>Ürün Adı</x-table.th>
                    <x-table.th>Kategori</x-table.th>
                    <x-table.th>Fiyat</x-table.th>
                    <x-table.th>Durum</x-table.th>
                    <x-table.th align="right">İşlemler</x-table.th>
                </x-table.tr>
            </x-table.thead>

            <x-table.tbody>
                @forelse($products as $product)
                <x-table.tr>
                    <x-table.td>
                        <span class="text-black dark:text-white">{{ $product->name }}</span>
                    </x-table.td>
                    <x-table.td>
                        <span class="text-gray-600 dark:text-gray-400">{{ $product->category->name ?? '-' }}</span>
                    </x-table.td>
                    <x-table.td>
                        <x-data.money :amount="$product->price" />
                    </x-table.td>
                    <x-table.td>
                        @if($product->is_active)
                            <x-ui.badge type="success">Aktif</x-ui.badge>
                        @else
                            <x-ui.badge type="danger">Pasif</x-ui.badge>
                        @endif
                    </x-table.td>
                    <x-table.td align="right">
                        <x-ui.button variant="ghost" size="sm">Düzenle</x-ui.button>
                    </x-table.td>
                </x-table.tr>
                @empty
                <x-table.empty colspan="5" icon="box" message="Ürün bulunamadı" />
                @endforelse
            </x-table.tbody>
        </x-table.table>

        @if($products->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $products->links() }}
        </div>
        @endif
    </x-ui.card>
</div>
@endsection
