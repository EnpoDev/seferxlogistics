@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Müşteri Yönetimi</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Müşterilerinizi görüntüleyin ve yönetin</p>
        </div>
        <button class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
            + Yeni Müşteri
        </button>
    </div>

    <!-- Arama ve Filtreler -->
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-4 mb-6">
        <div class="flex gap-4">
            <input type="text" placeholder="Müşteri ara..." class="flex-1 px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white placeholder-gray-500">
            <button class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                Ara
            </button>
        </div>
    </div>

    <!-- Müşteri Listesi -->
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">AD SOYAD</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">TELEFON</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">ADRES</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">SİPARİŞ SAYISI</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">SON SİPARİŞ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">İŞLEMLER</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($customers as $customer)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                        <td class="px-6 py-4 text-sm text-black dark:text-white">{{ $customer->customer_name ?? 'İsimsiz Müşteri' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $customer->customer_phone }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate" title="{{ $customer->customer_address }}">
                            {{ $customer->customer_address ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">{{ $customer->order_count }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $customer->last_order_date ? \Carbon\Carbon::parse($customer->last_order_date)->format('d.m.Y') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <button class="text-black dark:text-white hover:opacity-60 mr-3">Görüntüle</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-600 dark:text-gray-400">
                            Müşteri bulunamadı
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $customers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
