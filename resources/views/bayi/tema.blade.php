@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn" x-data="{
    themeMode: '{{ $themeSettings->theme_mode ?? 'system' }}',
    accentColor: '{{ $themeSettings->accent_color ?? '#000000' }}',
    compactMode: {{ $themeSettings->compact_mode ? 'true' : 'false' }},
    animationsEnabled: {{ $themeSettings->animations_enabled ? 'true' : 'false' }},
    sidebarAutoHide: {{ $themeSettings->sidebar_auto_hide ? 'true' : 'false' }},
    sidebarWidth: '{{ $themeSettings->sidebar_width ?? 'normal' }}'
}">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Tema Yapilandirmasi"
        subtitle="Arayuz gorunumunu ozellestirin"
    >
        <x-slot name="icon">
            <x-ui.icon name="palette" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>

        <x-slot name="actions">
            <x-ui.button type="submit" form="themeForm">
                Kaydet
            </x-ui.button>
        </x-slot>
    </x-layout.page-header>

    {{-- Mesajlar --}}
    @if(session('success'))
        <x-feedback.alert type="success" class="mb-6 max-w-2xl">{{ session('success') }}</x-feedback.alert>
    @endif

    <form id="themeForm" action="{{ route('bayi.tema.update') }}" method="POST" class="space-y-6 max-w-4xl">
        @csrf

        {{-- Hidden fields for Alpine.js data --}}
        <input type="hidden" name="theme_mode" :value="themeMode">
        <input type="hidden" name="accent_color" :value="accentColor">
        <input type="hidden" name="compact_mode" :value="compactMode ? '1' : '0'">
        <input type="hidden" name="animations_enabled" :value="animationsEnabled ? '1' : '0'">
        <input type="hidden" name="sidebar_auto_hide" :value="sidebarAutoHide ? '1' : '0'">
        <input type="hidden" name="sidebar_width" :value="sidebarWidth">

        {{-- Tema Secenekleri --}}
        <x-layout.grid cols="1" mdCols="3" gap="6">
            {{-- Acik Tema --}}
            <div @click="themeMode = 'light'"
                 :class="themeMode === 'light' ? 'border-black dark:border-white ring-2 ring-black dark:ring-white' : 'border-gray-200 dark:border-gray-800'"
                 class="border-2 rounded-xl p-6 cursor-pointer transition-all hover:border-black dark:hover:border-white bg-white dark:bg-gray-950">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-black dark:text-white">Acik Tema</h3>
                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center"
                         :class="themeMode === 'light' ? 'border-black dark:border-white bg-black dark:bg-white' : 'border-gray-300 dark:border-gray-700'">
                        <x-ui.icon x-show="themeMode === 'light'" name="check" class="w-4 h-4 text-white dark:text-black" />
                    </div>
                </div>
                <div class="bg-gray-100 rounded-lg h-24 flex items-center justify-center">
                    <x-ui.icon name="sun" class="w-10 h-10 text-gray-600" />
                </div>
                <p class="text-xs text-gray-500 mt-3">Gunisigi icin ideal, parlak arayuz</p>
            </div>

            {{-- Koyu Tema --}}
            <div @click="themeMode = 'dark'"
                 :class="themeMode === 'dark' ? 'border-black dark:border-white ring-2 ring-black dark:ring-white' : 'border-gray-200 dark:border-gray-800'"
                 class="border-2 rounded-xl p-6 cursor-pointer transition-all hover:border-black dark:hover:border-white bg-white dark:bg-gray-950">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-black dark:text-white">Koyu Tema</h3>
                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center"
                         :class="themeMode === 'dark' ? 'border-black dark:border-white bg-black dark:bg-white' : 'border-gray-300 dark:border-gray-700'">
                        <x-ui.icon x-show="themeMode === 'dark'" name="check" class="w-4 h-4 text-white dark:text-black" />
                    </div>
                </div>
                <div class="bg-gray-900 rounded-lg h-24 flex items-center justify-center">
                    <x-ui.icon name="moon" class="w-10 h-10 text-white" />
                </div>
                <p class="text-xs text-gray-500 mt-3">Goz yorgunlugunu azaltir, gece icin ideal</p>
            </div>

            {{-- Sistem --}}
            <div @click="themeMode = 'system'"
                 :class="themeMode === 'system' ? 'border-black dark:border-white ring-2 ring-black dark:ring-white' : 'border-gray-200 dark:border-gray-800'"
                 class="border-2 rounded-xl p-6 cursor-pointer transition-all hover:border-black dark:hover:border-white bg-white dark:bg-gray-950">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-black dark:text-white">Sistem</h3>
                    <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center"
                         :class="themeMode === 'system' ? 'border-black dark:border-white bg-black dark:bg-white' : 'border-gray-300 dark:border-gray-700'">
                        <x-ui.icon x-show="themeMode === 'system'" name="check" class="w-4 h-4 text-white dark:text-black" />
                    </div>
                </div>
                <div class="bg-gradient-to-r from-gray-100 to-gray-900 rounded-lg h-24 flex items-center justify-center">
                    <x-ui.icon name="settings" class="w-10 h-10 text-gray-600" />
                </div>
                <p class="text-xs text-gray-500 mt-3">Cihazinizin tema ayarina gore otomatik</p>
            </div>
        </x-layout.grid>

        {{-- Renk Ayarlari --}}
        <x-ui.card>
            <x-layout.section title="Renk Ayarlari">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Vurgu Rengi</label>
                        <div class="flex items-center gap-4">
                            <input type="color" x-model="accentColor" class="w-16 h-10 rounded-lg cursor-pointer border border-gray-200 dark:border-gray-800">
                            <span class="text-sm text-gray-600 dark:text-gray-400" x-text="accentColor"></span>
                            <button type="button" @click="accentColor = '#000000'" class="text-xs text-gray-500 hover:text-black dark:hover:text-white underline">
                                Varsayilana Dondur
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Butonlar ve vurgular icin kullanilacak renk</p>
                    </div>
                </div>
            </x-layout.section>
        </x-ui.card>

        {{-- Arayuz Ayarlari --}}
        <x-ui.card>
            <x-layout.section title="Arayuz Ayarlari">
                <div class="space-y-4">
                    {{-- Kompakt Mod --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">Kompakt Mod</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Daha yogun bir arayuz icin bosluklari azaltir</p>
                        </div>
                        <button type="button" @click="compactMode = !compactMode"
                                class="relative w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black dark:focus:ring-white"
                                :class="compactMode ? 'bg-black dark:bg-white' : 'bg-gray-300 dark:bg-gray-700'">
                            <span class="absolute top-1 left-1 w-4 h-4 rounded-full bg-white dark:bg-black transition-transform duration-200"
                                  :class="compactMode ? 'translate-x-5' : 'translate-x-0'"></span>
                        </button>
                    </div>

                    {{-- Animasyonlar --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">Animasyonlar</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Gecis animasyonlarini etkinlestirir</p>
                        </div>
                        <button type="button" @click="animationsEnabled = !animationsEnabled"
                                class="relative w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black dark:focus:ring-white"
                                :class="animationsEnabled ? 'bg-black dark:bg-white' : 'bg-gray-300 dark:bg-gray-700'">
                            <span class="absolute top-1 left-1 w-4 h-4 rounded-full bg-white dark:bg-black transition-transform duration-200"
                                  :class="animationsEnabled ? 'translate-x-5' : 'translate-x-0'"></span>
                        </button>
                    </div>

                    {{-- Sidebar Otomatik Gizle --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">Sidebar Otomatik Gizle</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Mobil cihazlarda sidebari otomatik gizler</p>
                        </div>
                        <button type="button" @click="sidebarAutoHide = !sidebarAutoHide"
                                class="relative w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black dark:focus:ring-white"
                                :class="sidebarAutoHide ? 'bg-black dark:bg-white' : 'bg-gray-300 dark:bg-gray-700'">
                            <span class="absolute top-1 left-1 w-4 h-4 rounded-full bg-white dark:bg-black transition-transform duration-200"
                                  :class="sidebarAutoHide ? 'translate-x-5' : 'translate-x-0'"></span>
                        </button>
                    </div>
                </div>
            </x-layout.section>
        </x-ui.card>

        {{-- Sidebar Genislik --}}
        <x-ui.card>
            <x-layout.section title="Sidebar Genisligi">
                <div class="grid grid-cols-3 gap-4">
                    <div @click="sidebarWidth = 'narrow'"
                         :class="sidebarWidth === 'narrow' ? 'border-black dark:border-white bg-gray-50 dark:bg-gray-900' : 'border-gray-200 dark:border-gray-800'"
                         class="border-2 rounded-lg p-4 cursor-pointer transition-all text-center">
                        <div class="w-4 h-12 bg-gray-400 dark:bg-gray-600 mx-auto rounded"></div>
                        <p class="text-sm font-medium text-black dark:text-white mt-2">Dar</p>
                    </div>
                    <div @click="sidebarWidth = 'normal'"
                         :class="sidebarWidth === 'normal' ? 'border-black dark:border-white bg-gray-50 dark:bg-gray-900' : 'border-gray-200 dark:border-gray-800'"
                         class="border-2 rounded-lg p-4 cursor-pointer transition-all text-center">
                        <div class="w-8 h-12 bg-gray-400 dark:bg-gray-600 mx-auto rounded"></div>
                        <p class="text-sm font-medium text-black dark:text-white mt-2">Normal</p>
                    </div>
                    <div @click="sidebarWidth = 'wide'"
                         :class="sidebarWidth === 'wide' ? 'border-black dark:border-white bg-gray-50 dark:bg-gray-900' : 'border-gray-200 dark:border-gray-800'"
                         class="border-2 rounded-lg p-4 cursor-pointer transition-all text-center">
                        <div class="w-12 h-12 bg-gray-400 dark:bg-gray-600 mx-auto rounded"></div>
                        <p class="text-sm font-medium text-black dark:text-white mt-2">Genis</p>
                    </div>
                </div>
            </x-layout.section>
        </x-ui.card>

        {{-- Kaydet Butonu (Mobil icin) --}}
        <div class="md:hidden">
            <x-ui.button type="submit" class="w-full">Degisiklikleri Kaydet</x-ui.button>
        </div>
    </form>
</div>
@endsection
