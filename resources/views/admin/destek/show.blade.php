@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('admin.destek.index') }}" class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white mb-4">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Destek Taleplerine Dön
        </a>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-black dark:text-white">{{ $ticket->ticket_number }}</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $ticket->subject }}</p>
            </div>
            <form action="{{ route('admin.destek.status', $ticket) }}" method="POST" class="flex items-center space-x-2">
                @csrf @method('PUT')
                <select name="status" class="px-3 py-2 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-black dark:text-white">
                    <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Açık</option>
                    <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>İşlemde</option>
                    <option value="waiting_response" {{ $ticket->status === 'waiting_response' ? 'selected' : '' }}>Yanıt Bekleniyor</option>
                    <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Çözüldü</option>
                    <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Kapandı</option>
                </select>
                <button type="submit" class="px-3 py-2 bg-gray-100 dark:bg-gray-800 text-black dark:text-white rounded-lg text-sm">Güncelle</button>
            </form>
        </div>
    </div>

    @if(session('success'))<div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg"><p class="text-green-700 dark:text-green-400">{{ session('success') }}</p></div>@endif

    <!-- Talep Bilgileri -->
    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div><p class="text-sm text-gray-600 dark:text-gray-400">Kullanıcı</p><p class="font-medium text-black dark:text-white">{{ $ticket->user?->name ?? '-' }}</p></div>
            <div><p class="text-sm text-gray-600 dark:text-gray-400">Kategori</p><p class="font-medium text-black dark:text-white capitalize">{{ $ticket->category }}</p></div>
            <div><p class="text-sm text-gray-600 dark:text-gray-400">Öncelik</p><p class="font-medium text-black dark:text-white capitalize">{{ $ticket->priority }}</p></div>
            <div><p class="text-sm text-gray-600 dark:text-gray-400">Oluşturulma</p><p class="font-medium text-black dark:text-white">{{ $ticket->created_at->format('d.m.Y H:i') }}</p></div>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Açıklama</p>
            <p class="text-black dark:text-white">{{ $ticket->description }}</p>
        </div>
    </div>

    <!-- Mesajlar -->
    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 mb-6">
        <div class="p-4 border-b border-gray-200 dark:border-gray-800">
            <h2 class="font-semibold text-black dark:text-white">Mesajlar</h2>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-800 max-h-96 overflow-y-auto">
            @forelse($ticket->messages as $message)
            <div class="p-4 {{ $message->is_admin ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}">
                <div class="flex items-center justify-between mb-2">
                    <p class="font-medium text-black dark:text-white">
                        {{ $message->user?->name ?? 'Sistem' }}
                        @if($message->is_admin)<span class="ml-2 px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 text-xs rounded">Admin</span>@endif
                    </p>
                    <p class="text-xs text-gray-500">{{ $message->created_at->format('d.m.Y H:i') }}</p>
                </div>
                <p class="text-gray-700 dark:text-gray-300">{{ $message->message }}</p>
            </div>
            @empty
            <div class="p-8 text-center text-gray-500">Henüz mesaj yok</div>
            @endforelse
        </div>
    </div>

    <!-- Yanit Formu -->
    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
        <h2 class="font-semibold text-black dark:text-white mb-4">Yanıt Gönder</h2>
        <form action="{{ route('admin.destek.reply', $ticket) }}" method="POST">
            @csrf
            <textarea name="message" rows="4" required placeholder="Yanıtınızı yazın..." class="w-full px-4 py-3 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-xl text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400"></textarea>
            <div class="mt-4 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200">Yanıt Gönder</button>
            </div>
        </form>
    </div>
</div>
@endsection
