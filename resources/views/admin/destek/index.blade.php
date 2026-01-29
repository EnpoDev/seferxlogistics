@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Destek Talepleri</h1>
            <p class="text-gray-600 dark:text-gray-400">Kullanıcı destek taleplerini yönetin</p>
        </div>
    </div>

    @if(session('success'))<div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg"><p class="text-green-700 dark:text-green-400">{{ session('success') }}</p></div>@endif

    <!-- Filters -->
    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-4 mb-6">
        <form action="{{ route('admin.destek.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div>
                <select name="status" class="px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    <option value="">Tüm Durumlar</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Açık</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>İşlemde</option>
                    <option value="waiting_response" {{ request('status') === 'waiting_response' ? 'selected' : '' }}>Yanıt Bekleniyor</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Çözüldü</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Kapandı</option>
                </select>
            </div>
            <div>
                <select name="priority" class="px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    <option value="">Tüm Öncelikler</option>
                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Acil</option>
                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>Yüksek</option>
                    <option value="normal" {{ request('priority') === 'normal' ? 'selected' : '' }}>Normal</option>
                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Düşük</option>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Talep No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kullanıcı</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Konu</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Öncelik</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tarih</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($tickets as $ticket)
                    @php
                        $statusColors = [
                            'open' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                            'in_progress' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'waiting_response' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                            'resolved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                            'closed' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-400',
                        ];
                        $priorityColors = [
                            'urgent' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                            'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                            'normal' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                            'low' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-400',
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                        <td class="px-6 py-4 font-medium text-black dark:text-white">{{ $ticket->ticket_number }}</td>
                        <td class="px-6 py-4">
                            <p class="text-black dark:text-white">{{ $ticket->user?->name ?? '-' }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $ticket->user?->email ?? '' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-black dark:text-white">{{ Str::limit($ticket->subject, 40) }}</p>
                            <p class="text-xs text-gray-500 capitalize">{{ $ticket->category }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded text-xs {{ $priorityColors[$ticket->priority] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded text-xs {{ $statusColors[$ticket->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $ticket->created_at->format('d.m.Y H:i') }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.destek.show', $ticket) }}" class="px-3 py-1 bg-black dark:bg-white text-white dark:text-black rounded text-sm hover:bg-gray-800 dark:hover:bg-gray-200">
                                Incele
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">Destek talebi bulunamadı</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())<div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">{{ $tickets->links() }}</div>@endif
    </div>
</div>
@endsection
