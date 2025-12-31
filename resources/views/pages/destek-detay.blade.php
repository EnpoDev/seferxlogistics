@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Back Link & Header --}}
    <div class="mb-6">
        <a href="{{ route('destek') }}" class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white mb-4">
            <x-ui.icon name="arrow-left" class="w-4 h-4 mr-2" />
            Destek Taleplerine Don
        </a>
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-black dark:text-white">{{ $ticket->subject }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $ticket->ticket_number }} • {{ $ticket->created_at->format('d M Y H:i') }}</p>
            </div>
            <div class="flex items-center gap-2">
                @php
                    $statusTypes = [
                        'open' => 'warning',
                        'in_progress' => 'info',
                        'waiting_response' => 'default',
                        'resolved' => 'success',
                        'closed' => 'default',
                    ];
                @endphp
                <x-ui.badge :type="$statusTypes[$ticket->status] ?? 'default'" size="lg">
                    {{ $ticket->getStatusLabel() }}
                </x-ui.badge>
                @if($ticket->isOpen())
                <form action="{{ route('destek.close', $ticket) }}" method="POST" class="inline">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" size="sm" onclick="return confirm('Bu talebi kapatmak istediginizden emin misiniz?')">
                        Talebi Kapat
                    </x-ui.button>
                </form>
                @else
                <form action="{{ route('destek.reopen', $ticket) }}" method="POST" class="inline">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" size="sm">Yeniden Ac</x-ui.button>
                </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Mesajlar --}}
    @if(session('success'))
        <x-feedback.alert type="success" class="mb-6">{{ session('success') }}</x-feedback.alert>
    @endif
    @if(session('error'))
        <x-feedback.alert type="danger" class="mb-6">{{ session('error') }}</x-feedback.alert>
    @endif

    <x-layout.grid cols="1" lgCols="3" gap="6">
        {{-- Mesajlar --}}
        <div class="lg:col-span-2 space-y-6">
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Mesajlar</h3>
                <div class="space-y-4">
                    @foreach($ticket->messages as $message)
                    <div class="p-4 rounded-lg {{ $message->is_staff_reply ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500' : 'bg-gray-50 dark:bg-gray-900' }}">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-700 flex items-center justify-center text-sm font-medium text-black dark:text-white">
                                    {{ strtoupper(substr($message->user->name ?? 'D', 0, 1)) }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-black dark:text-white">
                                        {{ $message->user->name ?? 'Destek Ekibi' }}
                                        @if($message->is_staff_reply)
                                            <x-ui.badge type="info" size="sm" class="ml-2">Destek</x-ui.badge>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500">{{ $message->created_at->format('d M Y H:i') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="text-sm text-black dark:text-white whitespace-pre-wrap">{{ $message->message }}</div>
                        @if($message->attachment_path)
                        <div class="mt-3">
                            <a href="{{ Storage::url($message->attachment_path) }}" target="_blank" class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                <x-ui.icon name="attachment" class="w-4 h-4 mr-1" />
                                Ek Dosya
                            </a>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </x-ui.card>

            {{-- Yanit Formu --}}
            @if($ticket->isOpen())
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Yanit Ekle</h3>
                <form action="{{ route('destek.reply', $ticket) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <x-form.textarea name="message" :rows="4" placeholder="Yanitinizi yazin..." required :error="$errors->first('message')" />

                    <div class="flex items-center justify-between">
                        <div>
                            <label class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 cursor-pointer hover:text-black dark:hover:text-white">
                                <x-ui.icon name="attachment" class="w-4 h-4 mr-2" />
                                <input type="file" name="attachment" class="hidden" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
                                Dosya Ekle
                            </label>
                        </div>
                        <x-ui.button type="submit">Yanit Gonder</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
            @else
            <x-feedback.alert type="warning" class="text-center">
                Bu talep kapatilmis. Yanit eklemek icin talebi yeniden acin.
            </x-feedback.alert>
            @endif
        </div>

        {{-- Talep Detaylari --}}
        <div class="lg:col-span-1">
            <x-ui.card class="sticky top-6">
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Talep Detaylari</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Kategori</p>
                        <p class="text-sm text-black dark:text-white">{{ $ticket->getCategoryLabel() }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Oncelik</p>
                        @php
                            $priorityTypes = [
                                'urgent' => 'danger',
                                'high' => 'warning',
                                'normal' => 'info',
                                'low' => 'default',
                            ];
                        @endphp
                        <x-ui.badge :type="$priorityTypes[$ticket->priority] ?? 'default'">
                            {{ $ticket->getPriorityLabel() }}
                        </x-ui.badge>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Olusturulma Tarihi</p>
                        <p class="text-sm text-black dark:text-white">{{ $ticket->created_at->format('d M Y H:i') }}</p>
                    </div>
                    @if($ticket->closed_at)
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Kapatilma Tarihi</p>
                        <p class="text-sm text-black dark:text-white">{{ $ticket->closed_at->format('d M Y H:i') }}</p>
                    </div>
                    @endif
                    @if($ticket->attachment_path)
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Ek Dosya</p>
                        <a href="{{ Storage::url($ticket->attachment_path) }}" target="_blank" class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            <x-ui.icon name="download" class="w-4 h-4 mr-1" />
                            Indir
                        </a>
                    </div>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </x-layout.grid>
</div>
@endsection
