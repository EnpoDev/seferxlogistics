@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-black dark:text-white">Entegrasyonlar</h1>
            <p class="text-gray-600 dark:text-gray-400">Platform entegrasyonlarını görüntüleyin</p>
        </div>
    </div>

    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-black">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Platform</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Son Sync</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($entegrasyonlar as $entegrasyon)
                    @php
                        $statusColors = [
                            'connected' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                            'connecting' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'error' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                            'inactive' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-400',
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                        <td class="px-6 py-4">
                            <span class="font-medium text-black dark:text-white capitalize">{{ $entegrasyon->platform }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $entegrasyon->name ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded text-xs {{ $statusColors[$entegrasyon->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($entegrasyon->status) }}
                            </span>
                            @if($entegrasyon->error_message)
                            <p class="text-xs text-red-500 mt-1">{{ Str::limit($entegrasyon->error_message, 50) }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $entegrasyon->last_sync_at?->diffForHumans() ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-12 text-center text-gray-500">Entegrasyon bulunamadı</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($entegrasyonlar->hasPages())<div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">{{ $entegrasyonlar->links() }}</div>@endif
    </div>
</div>
@endsection
