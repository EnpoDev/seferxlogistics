@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Teknik Destek"
        subtitle="Yardım alın ve destek talebi oluşturun"
    >
        <x-slot name="icon">
            <x-ui.icon name="support" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>
    </x-layout.page-header>

    {{-- Mesajlar --}}
    @if(session('success'))
        <x-feedback.alert type="success" class="mb-6">{{ session('success') }}</x-feedback.alert>
    @endif
    @if(session('error'))
        <x-feedback.alert type="danger" class="mb-6">{{ session('error') }}</x-feedback.alert>
    @endif

    <x-layout.grid cols="1" lgCols="3" gap="6">
        {{-- İletişim Bilgileri --}}
        <div class="lg:col-span-1 space-y-6">
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">İletişim</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Telefon</p>
                        <p class="text-sm text-black dark:text-white">0850 XXX XX XX</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">E-posta</p>
                        <p class="text-sm text-black dark:text-white">destek@seferxlogistics.com</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Çalışma Saatleri</p>
                        <p class="text-sm text-black dark:text-white">Hafta içi 09:00 - 18:00</p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Hızlı Linkler</h3>
                <div class="space-y-2">
                    <a href="#" class="block text-sm text-black dark:text-white hover:opacity-60">Kullanım Kılavuzu</a>
                    <a href="#" class="block text-sm text-black dark:text-white hover:opacity-60">Sıkça Sorulan Sorular</a>
                    <a href="#" class="block text-sm text-black dark:text-white hover:opacity-60">Video Eğitimler</a>
                    <a href="#" class="block text-sm text-black dark:text-white hover:opacity-60">Yenilikler</a>
                </div>
            </x-ui.card>
        </div>

        {{-- Destek Formu --}}
        <div class="lg:col-span-2 space-y-6">
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Destek Talebi Oluştur</h3>
                <form action="{{ route('destek.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <x-form.input name="subject" label="Konu" placeholder="Sorun başlığını yazın" :value="old('subject')" required :error="$errors->first('subject')" />

                    <x-layout.grid cols="2" gap="4">
                        <x-form.select name="category" label="Kategori" required :options="[
                            'technical' => 'Teknik Sorun',
                            'payment' => 'Ödeme Sorunu',
                            'order' => 'Sipariş Sorunu',
                            'integration' => 'Entegrasyon Sorunu',
                            'feature' => 'Özellik İsteği',
                            'other' => 'Diğer',
                        ]" :selected="old('category', 'technical')" />

                        <x-form.select name="priority" label="Öncelik" required :options="[
                            'low' => 'Düşük',
                            'normal' => 'Normal',
                            'high' => 'Yüksek',
                            'urgent' => 'Acil',
                        ]" :selected="old('priority', 'normal')" />
                    </x-layout.grid>

                    <x-form.textarea name="description" label="Açıklama" :rows="6" placeholder="Sorununuzu detaylı olarak açıklayın..." required :error="$errors->first('description')">{{ old('description') }}</x-form.textarea>

                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Dosya Ekle (Opsiyonel)</label>
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-6 text-center">
                            <input type="file" name="attachment" id="attachment" class="hidden" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
                            <label for="attachment" class="cursor-pointer">
                                <x-ui.icon name="upload" class="w-8 h-8 mx-auto text-gray-400 mb-2" />
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Dosya sürükleyin veya tıklayın</p>
                                <p class="text-xs text-gray-500">JPG, PNG, PDF, DOC - Max 5MB</p>
                            </label>
                        </div>
                        @error('attachment')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-ui.button type="submit" class="w-full">Destek Talebi Gönder</x-ui.button>
                </form>
            </x-ui.card>

            {{-- Önceki Talepler --}}
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Önceki Talepleriniz</h3>
                <div class="space-y-3">
                    @forelse($tickets as $ticket)
                    <a href="{{ route('destek.show', $ticket) }}" class="block p-4 border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-black dark:text-white truncate">{{ $ticket->subject }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ $ticket->getCategoryLabel() }}</p>
                            </div>
                            @php
                                $statusTypes = [
                                    'open' => 'warning',
                                    'in_progress' => 'info',
                                    'waiting_response' => 'default',
                                    'resolved' => 'success',
                                    'closed' => 'default',
                                ];
                            @endphp
                            <x-ui.badge :type="$statusTypes[$ticket->status] ?? 'default'" class="ml-2">
                                {{ $ticket->getStatusLabel() }}
                            </x-ui.badge>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                {{ $ticket->created_at->format('d M Y') }} - {{ $ticket->ticket_number }}
                            </p>
                            @php
                                $priorityTypes = [
                                    'urgent' => 'danger',
                                    'high' => 'warning',
                                    'normal' => 'info',
                                    'low' => 'default',
                                ];
                            @endphp
                            <x-ui.badge :type="$priorityTypes[$ticket->priority] ?? 'default'" size="sm">
                                {{ $ticket->getPriorityLabel() }}
                            </x-ui.badge>
                        </div>
                    </a>
                    @empty
                    <x-ui.empty-state
                        title="Talep bulunamadı"
                        description="Henüz destek talebi oluşturmadınız"
                        icon="support"
                    />
                    @endforelse
                </div>

                @if($tickets->hasPages())
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $tickets->links() }}
                </div>
                @endif
            </x-ui.card>
        </div>
    </x-layout.grid>
</div>
@endsection
