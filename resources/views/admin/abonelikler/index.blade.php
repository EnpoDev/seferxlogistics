@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn" x-data="{ showCreateModal: false }">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Abonelikler</h1>
            <p class="text-gray-600 dark:text-gray-400">Tüm abonelikleri yönetin</p>
        </div>
        <button @click="showCreateModal = true" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Yeni Abonelik</span>
        </button>
    </div>

    @if(session('success'))<div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg"><p class="text-green-700 dark:text-green-400">{{ session('success') }}</p></div>@endif

    <!-- Filters -->
    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-4 mb-6">
        <form action="{{ route('admin.abonelikler.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div>
                <select name="status" class="px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                    <option value="">Tüm Durumlar</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Deneme</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>İptal</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Süresi Dolmuş</option>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kullanıcı</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Başlangıç</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sonraki Ödeme</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($abonelikler as $abonelik)
                    @php
                        $statusColors = [
                            'active' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                            'trial' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                            'expired' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-400',
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                        <td class="px-6 py-4">
                            <p class="font-medium text-black dark:text-white">{{ $abonelik->user?->name ?? 'Bilinmiyor' }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $abonelik->user?->email ?? '' }}</p>
                        </td>
                        <td class="px-6 py-4 text-black dark:text-white">{{ $abonelik->plan?->name ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded text-xs {{ $statusColors[$abonelik->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($abonelik->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $abonelik->starts_at?->format('d.m.Y') ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $abonelik->next_billing_date?->format('d.m.Y') ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">Abonelik bulunamadı</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($abonelikler->hasPages())<div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">{{ $abonelikler->links() }}</div>@endif
    </div>

    <!-- Create Modal -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showCreateModal = false"></div>
            <div class="relative bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Yeni Abonelik</h3>
                <form action="{{ route('admin.abonelikler.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bayi</label>
                        <select name="user_id" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                            <option value="">Bayi Seçin</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Plan</label>
                        <select name="plan_id" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                            <option value="">Plan Seçin</option>
                            @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} - {{ number_format($plan->price, 2) }} TL</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durum</label>
                        <select name="status" required class="w-full px-4 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-black dark:text-white">
                            <option value="active">Aktif</option>
                            <option value="trial">Deneme</option>
                        </select>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showCreateModal = false" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg">İptal</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg">Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
