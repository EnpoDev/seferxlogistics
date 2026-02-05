@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Kuryeler</h1>
            <p class="text-gray-600 dark:text-gray-400">Tüm kuryeleri görüntüleyin</p>
        </div>
    </div>

    @if(session('success'))<div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg"><p class="text-green-700 dark:text-green-400">{{ session('success') }}</p></div>@endif

    <!-- Filters -->
    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-4 mb-6">
        <form action="{{ route('admin.kuryeler.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Kurye ara..." class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
            </div>
            <div>
                <select name="status" class="px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    <option value="">Tüm Durumlar</option>
                    <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Müsait</option>
                    <option value="busy" {{ request('status') === 'busy' ? 'selected' : '' }}>Meşgul</option>
                    <option value="offline" {{ request('status') === 'offline' ? 'selected' : '' }}>Çevrimdışı</option>
                    <option value="on_break" {{ request('status') === 'on_break' ? 'selected' : '' }}>Molada</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg">Filtrele</button>
        </form>
    </div>

    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-black">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kurye</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bayi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Aktif Sipariş</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Toplam Teslimat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nakit Bakiye</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($kuryeler as $kurye)
                    @php
                        $statusColors = [
                            'available' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                            'busy' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                            'offline' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-400',
                            'on_break' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                        ];
                        $statusLabels = ['available' => 'Müsait', 'busy' => 'Meşgul', 'offline' => 'Çevrimdışı', 'on_break' => 'Molada'];
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                                    <span class="text-purple-600 dark:text-purple-400 font-medium">{{ strtoupper(substr($kurye->name, 0, 2)) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-black dark:text-white">{{ $kurye->name }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $kurye->phone }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($kurye->owner)
                            <div class="flex items-center space-x-2">
                                <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 dark:text-blue-400 font-medium text-xs">{{ strtoupper(substr($kurye->owner->name, 0, 1)) }}</span>
                                </div>
                                <span class="text-sm text-black dark:text-white">{{ $kurye->owner->name }}</span>
                            </div>
                            @else
                            <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded text-xs {{ $statusColors[$kurye->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabels[$kurye->status] ?? $kurye->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-black dark:text-white">{{ $kurye->active_orders_count }}</td>
                        <td class="px-6 py-4 text-black dark:text-white">{{ number_format($kurye->total_deliveries) }}</td>
                        <td class="px-6 py-4 text-black dark:text-white">{{ number_format($kurye->cash_balance, 2) }} TL</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.kuryeler.show', $kurye) }}" class="p-2 text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 inline-block">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">Kurye bulunamadı</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($kuryeler->hasPages())<div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">{{ $kuryeler->links() }}</div>@endif
    </div>
</div>
@endsection
