<x-bayi-layout>
    <x-slot name="title">Tema Ayarlari - Bayi Paneli</x-slot>

    <div class="space-y-6" x-data="{
        themeMode: '{{ $themeSettings?->theme_mode ?? 'system' }}',
        accentColor: '{{ $themeSettings?->accent_color ?? '#000000' }}',
        compactMode: {{ ($themeSettings?->compact_mode ?? false) ? 'true' : 'false' }},
        animationsEnabled: {{ ($themeSettings?->animations_enabled ?? true) ? 'true' : 'false' }},
        sidebarAutoHide: {{ ($themeSettings?->sidebar_auto_hide ?? false) ? 'true' : 'false' }},
        sidebarWidth: '{{ $themeSettings?->sidebar_width ?? 'normal' }}'
    }">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Tema Ayarlari</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Arayuz gorunumunu ozellestirin</p>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
            </div>
        @endif

        <form action="{{ route('bayi.tema.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Hidden fields for Alpine.js data -->
            <input type="hidden" name="theme_mode" :value="themeMode">
            <input type="hidden" name="accent_color" :value="accentColor">
            <input type="hidden" name="compact_mode" :value="compactMode ? '1' : '0'">
            <input type="hidden" name="animations_enabled" :value="animationsEnabled ? '1' : '0'">
            <input type="hidden" name="sidebar_auto_hide" :value="sidebarAutoHide ? '1' : '0'">
            <input type="hidden" name="sidebar_width" :value="sidebarWidth">

            <!-- Tema Secenekleri -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Tema Modu</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Acik Tema -->
                    <div @click="themeMode = 'light'"
                         :class="themeMode === 'light' ? 'border-black dark:border-white ring-2 ring-black dark:ring-white' : 'border-gray-200 dark:border-gray-700'"
                         class="border-2 rounded-xl p-4 cursor-pointer transition-all hover:border-gray-400 bg-gray-50 dark:bg-gray-900">
                        <div class="flex items-center justify-between mb-3">
                            <span class="font-medium text-black dark:text-white">Acik</span>
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                 :class="themeMode === 'light' ? 'border-black dark:border-white bg-black dark:bg-white' : 'border-gray-300 dark:border-gray-600'">
                                <svg x-show="themeMode === 'light'" class="w-3 h-3 text-white dark:text-black" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div class="bg-white border border-gray-200 rounded-lg h-16 flex items-center justify-center">
                            <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Koyu Tema -->
                    <div @click="themeMode = 'dark'"
                         :class="themeMode === 'dark' ? 'border-black dark:border-white ring-2 ring-black dark:ring-white' : 'border-gray-200 dark:border-gray-700'"
                         class="border-2 rounded-xl p-4 cursor-pointer transition-all hover:border-gray-400 bg-gray-50 dark:bg-gray-900">
                        <div class="flex items-center justify-between mb-3">
                            <span class="font-medium text-black dark:text-white">Koyu</span>
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                 :class="themeMode === 'dark' ? 'border-black dark:border-white bg-black dark:bg-white' : 'border-gray-300 dark:border-gray-600'">
                                <svg x-show="themeMode === 'dark'" class="w-3 h-3 text-white dark:text-black" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div class="bg-gray-800 border border-gray-700 rounded-lg h-16 flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Sistem -->
                    <div @click="themeMode = 'system'"
                         :class="themeMode === 'system' ? 'border-black dark:border-white ring-2 ring-black dark:ring-white' : 'border-gray-200 dark:border-gray-700'"
                         class="border-2 rounded-xl p-4 cursor-pointer transition-all hover:border-gray-400 bg-gray-50 dark:bg-gray-900">
                        <div class="flex items-center justify-between mb-3">
                            <span class="font-medium text-black dark:text-white">Sistem</span>
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                 :class="themeMode === 'system' ? 'border-black dark:border-white bg-black dark:bg-white' : 'border-gray-300 dark:border-gray-600'">
                                <svg x-show="themeMode === 'system'" class="w-3 h-3 text-white dark:text-black" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div class="bg-gradient-to-r from-white to-gray-800 border border-gray-300 rounded-lg h-16 flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Arayuz Ayarlari -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Arayuz Ayarlari</h2>
                <div class="space-y-4">
                    <!-- Kompakt Mod -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div>
                            <p class="font-medium text-black dark:text-white">Kompakt Mod</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Daha yogun bir arayuz icin bosluklari azaltir</p>
                        </div>
                        <button type="button" @click="compactMode = !compactMode"
                                class="relative h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                                :class="compactMode ? 'bg-black dark:bg-white' : 'bg-gray-300 dark:bg-gray-700'">
                            <span class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white dark:bg-gray-900 shadow-lg transition-transform duration-200"
                                  :class="compactMode ? 'translate-x-5' : 'translate-x-0'"></span>
                        </button>
                    </div>

                    <!-- Animasyonlar -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div>
                            <p class="font-medium text-black dark:text-white">Animasyonlar</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Gecis animasyonlarini etkinlestirir</p>
                        </div>
                        <button type="button" @click="animationsEnabled = !animationsEnabled"
                                class="relative h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                                :class="animationsEnabled ? 'bg-black dark:bg-white' : 'bg-gray-300 dark:bg-gray-700'">
                            <span class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white dark:bg-gray-900 shadow-lg transition-transform duration-200"
                                  :class="animationsEnabled ? 'translate-x-5' : 'translate-x-0'"></span>
                        </button>
                    </div>

                    <!-- Sidebar Otomatik Gizle -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <div>
                            <p class="font-medium text-black dark:text-white">Sidebar Otomatik Gizle</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Mobil cihazlarda sidebari otomatik gizler</p>
                        </div>
                        <button type="button" @click="sidebarAutoHide = !sidebarAutoHide"
                                class="relative h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2"
                                :class="sidebarAutoHide ? 'bg-black dark:bg-white' : 'bg-gray-300 dark:bg-gray-700'">
                            <span class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white dark:bg-gray-900 shadow-lg transition-transform duration-200"
                                  :class="sidebarAutoHide ? 'translate-x-5' : 'translate-x-0'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar Genislik -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-black dark:text-white mb-4">Sidebar Genisligi</h2>
                <div class="grid grid-cols-3 gap-4">
                    <div @click="sidebarWidth = 'narrow'"
                         :class="sidebarWidth === 'narrow' ? 'border-black dark:border-white bg-gray-100 dark:bg-gray-800' : 'border-gray-200 dark:border-gray-700'"
                         class="border-2 rounded-lg p-4 cursor-pointer transition-all text-center">
                        <div class="w-4 h-12 bg-gray-400 dark:bg-gray-500 mx-auto rounded"></div>
                        <p class="text-sm font-medium text-black dark:text-white mt-2">Dar</p>
                    </div>
                    <div @click="sidebarWidth = 'normal'"
                         :class="sidebarWidth === 'normal' ? 'border-black dark:border-white bg-gray-100 dark:bg-gray-800' : 'border-gray-200 dark:border-gray-700'"
                         class="border-2 rounded-lg p-4 cursor-pointer transition-all text-center">
                        <div class="w-8 h-12 bg-gray-400 dark:bg-gray-500 mx-auto rounded"></div>
                        <p class="text-sm font-medium text-black dark:text-white mt-2">Normal</p>
                    </div>
                    <div @click="sidebarWidth = 'wide'"
                         :class="sidebarWidth === 'wide' ? 'border-black dark:border-white bg-gray-100 dark:bg-gray-800' : 'border-gray-200 dark:border-gray-700'"
                         class="border-2 rounded-lg p-4 cursor-pointer transition-all text-center">
                        <div class="w-12 h-12 bg-gray-400 dark:bg-gray-500 mx-auto rounded"></div>
                        <p class="text-sm font-medium text-black dark:text-white mt-2">Genis</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors font-medium">
                    Kaydet
                </button>
            </div>
        </form>
    </div>
</x-bayi-layout>
