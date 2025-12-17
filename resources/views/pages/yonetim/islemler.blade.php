@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-black dark:text-white">İşlemlerim</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Ödeme geçmişinizi görüntüleyin</p>
    </div>

    <!-- Filtreler -->
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-black dark:text-white mb-2">Başlangıç Tarihi</label>
                <input type="date" class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-black dark:text-white mb-2">Bitiş Tarihi</label>
                <input type="date" class="w-full px-3 py-2 bg-white dark:bg-black border border-gray-300 dark:border-gray-700 rounded-lg text-black dark:text-white">
            </div>
            <div class="flex items-end">
                <button class="w-full px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-80">
                    Filtrele
                </button>
            </div>
        </div>
    </div>

    <!-- İşlem Listesi -->
    <div class="bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">TARİH</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">AÇIKLAMA</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">TUTAR</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">DURUM</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-400">FATURA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                        <td class="px-6 py-4 text-sm text-black dark:text-white">{{ $transaction['date'] }}</td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">{{ $transaction['description'] }}</td>
                        <td class="px-6 py-4 text-sm text-black dark:text-white">₺{{ number_format($transaction['amount'], 2) }}</td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 text-xs border border-gray-300 dark:border-gray-700 rounded">{{ $transaction['status'] }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ $transaction['invoice_url'] }}" class="text-black dark:text-white hover:opacity-60">İndir</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-600 dark:text-gray-400">
                            İşlem bulunamadı
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
