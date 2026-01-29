@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Siparişler</h1>
            <p class="text-gray-600 dark:text-gray-400">Tüm siparişleri görüntüleyin</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-4 mb-6">
        <form action="{{ route('admin.siparisler.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Sipariş no, müşteri ara..." class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
            </div>
            <div>
                <select name="status" class="px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    <option value="">Tüm Durumlar</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Bekliyor</option>
                    <option value="preparing" {{ request('status') === 'preparing' ? 'selected' : '' }}>Hazırlanıyor</option>
                    <option value="ready" {{ request('status') === 'ready' ? 'selected' : '' }}>Hazır</option>
                    <option value="on_delivery" {{ request('status') === 'on_delivery' ? 'selected' : '' }}>Yolda</option>
                    <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Teslim Edildi</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>İptal</option>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sipariş No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Müşteri</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Şube</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kurye</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tutar</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tarih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($siparisler as $siparis)
                    @php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'preparing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                            'ready' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                            'on_delivery' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                            'delivered' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                        ];
                        $statusLabels = ['pending' => 'Bekliyor', 'preparing' => 'Hazırlanıyor', 'ready' => 'Hazır', 'on_delivery' => 'Yolda', 'delivered' => 'Teslim', 'cancelled' => 'İptal'];
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                        <td class="px-6 py-4 font-medium text-black dark:text-white">{{ $siparis->order_number }}</td>
                        <td class="px-6 py-4">
                            <p class="text-black dark:text-white">{{ $siparis->customer_name ?? '-' }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $siparis->customer_phone ?? '' }}</p>
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $siparis->branch?->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $siparis->courier?->name ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded text-xs {{ $statusColors[$siparis->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabels[$siparis->status] ?? $siparis->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-black dark:text-white">{{ number_format($siparis->total, 2) }} TL</td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $siparis->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">Sipariş bulunamadı</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($siparisler->hasPages())<div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">{{ $siparisler->links() }}</div>@endif
    </div>
</div>
@endsection
