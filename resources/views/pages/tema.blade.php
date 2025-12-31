@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn" x-data="{
    themeMode: '{{ $settings->theme_mode }}',
    compactMode: {{ $settings->compact_mode ? 'true' : 'false' }},
    animationsEnabled: {{ $settings->animations_enabled ? 'true' : 'false' }},
    sidebarAutoHide: {{ $settings->sidebar_auto_hide ? 'true' : 'false' }},
    sidebarWidth: '{{ $settings->sidebar_width }}',
    setThemeMode(mode) {
        this.themeMode = mode;
        if (mode === 'dark') {
            document.documentElement.classList.add('dark');
        } else if (mode === 'light') {
            document.documentElement.classList.remove('dark');
        } else {
            if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    }
}">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Tema Yapılandırması"
        subtitle="Görünüm ve tema ayarlarınızı özelleştirin"
    >
        <x-slot name="icon">
            <x-ui.icon name="palette" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>
    </x-layout.page-header>

    {{-- Mesajlar --}}
    @if(session('success'))
        <x-feedback.alert type="success" class="mb-6 max-w-2xl">{{ session('success') }}</x-feedback.alert>
    @endif

    @if($errors->any())
        <x-feedback.alert type="danger" class="mb-6 max-w-2xl">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </x-feedback.alert>
    @endif

    <form action="{{ route('tema.update') }}" method="POST">
        @csrf
        <input type="hidden" name="theme_mode" x-model="themeMode">
        <input type="hidden" name="compact_mode" :value="compactMode ? '1' : '0'">
        <input type="hidden" name="animations_enabled" :value="animationsEnabled ? '1' : '0'">
        <input type="hidden" name="sidebar_auto_hide" :value="sidebarAutoHide ? '1' : '0'">
        <input type="hidden" name="sidebar_width" x-model="sidebarWidth">

        <div class="max-w-2xl space-y-6">
            {{-- Tema Modu --}}
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Tema Modu</h3>
                <x-layout.grid cols="3" gap="4">
                    <div @click="setThemeMode('light')"
                         class="cursor-pointer p-4 border-2 rounded-lg bg-white text-black transition-all hover:border-gray-400"
                         :class="themeMode === 'light' ? 'border-black dark:border-white ring-2 ring-black dark:ring-white' : 'border-gray-300 dark:border-gray-700'">
                        <div class="mb-2 text-2xl text-center">☀️</div>
                        <p class="text-sm font-medium text-center">Açık Mod</p>
                    </div>
                    <div @click="setThemeMode('dark')"
                         class="cursor-pointer p-4 border-2 rounded-lg bg-[#181818] text-white transition-all hover:border-gray-600"
                         :class="themeMode === 'dark' ? 'border-black dark:border-white ring-2 ring-black dark:ring-white' : 'border-gray-300 dark:border-gray-700'">
                        <div class="mb-2 text-2xl text-center">🌙</div>
                        <p class="text-sm font-medium text-center">Koyu Mod</p>
                    </div>
                    <div @click="setThemeMode('system')"
                         class="cursor-pointer p-4 border-2 rounded-lg bg-gradient-to-r from-white to-[#181818] transition-all hover:border-gray-500"
                         :class="themeMode === 'system' ? 'border-black dark:border-white ring-2 ring-black dark:ring-white' : 'border-gray-300 dark:border-gray-700'">
                        <div class="mb-2 text-2xl text-center">⚙️</div>
                        <p class="text-sm font-medium text-center text-gray-700">Sistem</p>
                    </div>
                </x-layout.grid>
            </x-ui.card>

            {{-- Görünüm Ayarları --}}
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Görünüm Ayarları</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">Kompakt mod</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Daha az boşluk kullan</p>
                        </div>
                        <button type="button" @click="compactMode = !compactMode"
                                class="relative w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black dark:focus:ring-white"
                                :class="compactMode ? 'bg-black dark:bg-white' : 'bg-gray-300 dark:bg-gray-700'">
                            <span class="absolute top-1 left-1 w-4 h-4 rounded-full bg-white dark:bg-black transition-transform duration-200"
                                  :class="compactMode ? 'translate-x-5' : 'translate-x-0'"></span>
                        </button>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">Animasyonlar</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Geçiş animasyonlarını göster</p>
                        </div>
                        <button type="button" @click="animationsEnabled = !animationsEnabled"
                                class="relative w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black dark:focus:ring-white"
                                :class="animationsEnabled ? 'bg-black dark:bg-white' : 'bg-gray-300 dark:bg-gray-700'">
                            <span class="absolute top-1 left-1 w-4 h-4 rounded-full bg-white dark:bg-black transition-transform duration-200"
                                  :class="animationsEnabled ? 'translate-x-5' : 'translate-x-0'"></span>
                        </button>
                    </div>
                </div>
            </x-ui.card>

            {{-- Sidebar Ayarları --}}
            <x-ui.card>
                <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Sidebar Ayarları</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-black dark:text-white">Otomatik gizle</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Küçük ekranlarda otomatik gizle</p>
                        </div>
                        <button type="button" @click="sidebarAutoHide = !sidebarAutoHide"
                                class="relative w-11 h-6 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black dark:focus:ring-white"
                                :class="sidebarAutoHide ? 'bg-black dark:bg-white' : 'bg-gray-300 dark:bg-gray-700'">
                            <span class="absolute top-1 left-1 w-4 h-4 rounded-full bg-white dark:bg-black transition-transform duration-200"
                                  :class="sidebarAutoHide ? 'translate-x-5' : 'translate-x-0'"></span>
                        </button>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Sidebar Genişliği</label>
                        <x-layout.grid cols="3" gap="2">
                            <button type="button" @click="sidebarWidth = 'narrow'"
                                    class="px-3 py-2 text-sm rounded-lg border transition-colors"
                                    :class="sidebarWidth === 'narrow' ? 'bg-black dark:bg-white text-white dark:text-black border-black dark:border-white' : 'bg-white dark:bg-black text-black dark:text-white border-gray-300 dark:border-gray-700 hover:border-gray-400'">
                                Dar
                            </button>
                            <button type="button" @click="sidebarWidth = 'normal'"
                                    class="px-3 py-2 text-sm rounded-lg border transition-colors"
                                    :class="sidebarWidth === 'normal' ? 'bg-black dark:bg-white text-white dark:text-black border-black dark:border-white' : 'bg-white dark:bg-black text-black dark:text-white border-gray-300 dark:border-gray-700 hover:border-gray-400'">
                                Normal
                            </button>
                            <button type="button" @click="sidebarWidth = 'wide'"
                                    class="px-3 py-2 text-sm rounded-lg border transition-colors"
                                    :class="sidebarWidth === 'wide' ? 'bg-black dark:bg-white text-white dark:text-black border-black dark:border-white' : 'bg-white dark:bg-black text-black dark:text-white border-gray-300 dark:border-gray-700 hover:border-gray-400'">
                                Geniş
                            </button>
                        </x-layout.grid>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.button type="submit" class="w-full">Değişiklikleri Kaydet</x-ui.button>
        </div>
    </form>
</div>
@endsection
