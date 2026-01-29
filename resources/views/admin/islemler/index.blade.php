@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">İşlemler</h1>
            <p class="text-gray-600 dark:text-gray-400">Tüm finansal işlemleri görüntüleyin</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-4 mb-6">
        <form action="{{ route('admin.islemler.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div>
                <select name="type" class="px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    <option value="">Tüm Tipler</option>
                    <option value="subscription" {{ request('type') === 'subscription' ? 'selected' : '' }}>Abonelik</option>
                    <option value="one_time" {{ request('type') === 'one_time' ? 'selected' : '' }}>Tek Seferlik</option>
                    <option value="refund" {{ request('type') === 'refund' ? 'selected' : '' }}>Iade</option>
                </select>
            </div>
            <div>
                <select name="status" class="px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    <option value="">Tüm Durumlar</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Tamamlandı</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Bekliyor</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Başarısız</option>
                </select>
            </div>
            <div><input type="date" name="date_from" value="{{ request('date_from') }}" class="px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
            <div><input type="date" name="date_to" value="{{ request('date_to') }}" class="px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white"></div>
            <button type="submit" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg">Filtrele</button>
        </form>
    </div>

    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-black">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fatura No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kullanıcı</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tip</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tutar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tarih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($islemler as $islem)
                    @php
                        $statusColors = [
                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                            'refunded' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                        ];
                        $typeLabels = ['subscription' => 'Abonelik', 'one_time' => 'Tek Seferlik', 'refund' => 'İade', 'adjustment' => 'Düzeltme'];
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                        <td class="px-6 py-4 font-medium text-black dark:text-white">{{ $islem->invoice_number ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <p class="text-black dark:text-white">{{ $islem->user?->name ?? '-' }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $islem->user?->email ?? '' }}</p>
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $typeLabels[$islem->type] ?? $islem->type }}</td>
                        <td class="px-6 py-4 font-medium {{ $islem->type === 'refund' ? 'text-red-600' : 'text-black dark:text-white' }}">
                            {{ $islem->type === 'refund' ? '-' : '' }}{{ number_format($islem->amount, 2) }} {{ $islem->currency }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded text-xs {{ $statusColors[$islem->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($islem->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $islem->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">İşlem bulunamadı</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($islemler->hasPages())<div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">{{ $islemler->links() }}</div>@endif
    </div>
</div>
@endsection
