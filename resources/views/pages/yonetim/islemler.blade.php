@extends('layouts.app')

@section('content')
<div class="p-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-black dark:text-white">Islemlerim</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Odeme gecmisinizi goruntuleyin</p>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6 mb-6">
        <form method="GET" action="{{ route('yonetim.islemler') }}">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Baslangic Tarihi</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bitis Tarihi</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
                        Filtrele
                    </button>
                    @if(request('start_date') || request('end_date'))
                        <a href="{{ route('yonetim.islemler') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors">
                            Temizle
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Islem Listesi -->
    <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tarih</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aciklama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tutar</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Durum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fatura</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($transactions as $transaction)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-black dark:text-white">{{ ($transaction->paid_at ?? $transaction->created_at)->locale('tr')->isoFormat('D MMMM YYYY') }}</span>
                        <br>
                        <span class="text-xs text-gray-500">{{ ($transaction->paid_at ?? $transaction->created_at)->format('H:i') }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-black dark:text-white">{{ $transaction->description }}</p>
                        @if($transaction->invoice_number)
                            <p class="text-xs text-gray-500">{{ $transaction->invoice_number }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-medium text-black dark:text-white">{{ $transaction->getFormattedAmount() }}</span>
                        @if($transaction->refund_amount)
                            <br><span class="text-xs text-red-500">Iade: -{{ number_format($transaction->refund_amount, 2) }} TL</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $statusColors = [
                                'completed' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
                                'pending' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
                                'failed' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
                                'refunded' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
                                'partially_refunded' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
                            ];
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$transaction->status] ?? 'bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-400' }}">
                            {{ $transaction->getStatusLabel() }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($transaction->invoice_number && $transaction->status === 'completed')
                            <a href="{{ route('billing.invoice.download', $transaction) }}" class="text-black dark:text-white hover:underline text-sm">
                                Indir
                            </a>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">Islem bulunamadi</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($transactions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $transactions->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
