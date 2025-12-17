@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Ürünler</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Ürünlerinizi yönetin</p>
        </div>
        <button class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
            + Yeni Ürün
        </button>
    </div>

    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">ÜRÜN ADI</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">KATEGORİ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">FİYAT</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">DURUM</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">İŞLEMLER</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($products as $product)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                        <td class="px-6 py-4 text-sm text-black dark:text-white">{{ $product->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $product->category->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">₺{{ number_format($product->price, 2) }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if($product->is_active)
                                <span class="px-2 py-1 text-xs border border-green-200 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800 rounded">Aktif</span>
                            @else
                                <span class="px-2 py-1 text-xs border border-red-200 bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800 rounded">Pasif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <button class="text-black dark:text-white hover:opacity-60">Düzenle</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-600 dark:text-gray-400">
                            Ürün bulunamadı
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $products->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
