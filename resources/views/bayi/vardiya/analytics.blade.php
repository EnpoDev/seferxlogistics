<x-bayi-layout>
    <x-slot name="title">Vardiya Analitik - Bayi Paneli</x-slot>

    <div class="space-y-6" x-data="shiftAnalytics()">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('bayi.vardiya-saatleri') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-black dark:text-white">Vardiya Analitik</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Kurye vardiya performans ve dağılımları</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <select onchange="window.location.href='?period='+this.value" class="px-4 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg text-sm">
                    <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Bu Hafta</option>
                    <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bu Ay</option>
                    <option value="last_month" {{ $period === 'last_month' ? 'selected' : '' }}>Geçen Ay</option>
                </select>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Vardiyada -->
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-6 text-white">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="font-medium">Vardiyada</span>
                </div>
                <p class="text-3xl font-bold">{{ $onShiftCount }}</p>
                <p class="text-white/70 text-sm mt-1">Kurye aktif çalışıyor</p>
            </div>

            <!-- Vardiya Dışı -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="font-medium text-gray-600 dark:text-gray-400">Vardiya Dışı</span>
                </div>
                <p class="text-3xl font-bold text-black dark:text-white">{{ $offShiftCount }}</p>
                <p class="text-gray-500 text-sm mt-1">Kurye müsait değil</p>
            </div>

            <!-- Toplam Sipariş -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <span class="font-medium text-gray-600 dark:text-gray-400">Teslimat</span>
                </div>
                <p class="text-3xl font-bold text-black dark:text-white">{{ $shiftStats['total_orders'] ?? 0 }}</p>
                <p class="text-gray-500 text-sm mt-1">Bu dönem teslim edildi</p>
            </div>

            <!-- En Yoğun Saatler -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <span class="font-medium text-gray-600 dark:text-gray-400">Yoğun Saatler</span>
                </div>
                <p class="text-xl font-bold text-black dark:text-white">
                    @if(!empty($shiftStats['peak_hours']))
                        {{ implode(', ', array_map(fn($h) => $h . ':00', $shiftStats['peak_hours'])) }}
                    @else
                        -
                    @endif
                </p>
                <p class="text-gray-500 text-sm mt-1">En çok sipariş alınan</p>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Saatlik Kurye Dağılımı -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="font-semibold text-black dark:text-white mb-4">Saatlik Kurye Dağılımı (Bugün)</h3>
                <div class="h-64">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>

            <!-- Saatlik Sipariş Dağılımı -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <h3 class="font-semibold text-black dark:text-white mb-4">Saatlik Sipariş Dağılımı</h3>
                <div class="h-64">
                    <canvas id="orderChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Shift Templates -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-black dark:text-white">Hızlı Vardiya Şablonları</h3>
                <button @click="showBulkTemplateModal = true" class="px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg text-sm font-medium hover:opacity-90 transition-opacity">
                    Toplu Şablon Uygula
                </button>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach($templates as $key => $template)
                <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 hover:border-black dark:hover:border-white transition-colors cursor-pointer"
                     @click="selectedTemplate = '{{ $key }}'">
                    <div class="flex items-center gap-2 mb-2">
                        @if($key === 'sabah')
                            <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        @elseif($key === 'ogle')
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        @elseif($key === 'aksam')
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                            </svg>
                        @elseif($key === 'gece')
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @endif
                        <span class="font-medium text-black dark:text-white text-sm">{{ $template['name'] }}</span>
                    </div>
                    <p class="text-gray-500 text-xs">{{ $template['start'] }} - {{ $template['end'] }}</p>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Courier Shift Status -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <h3 class="font-semibold text-black dark:text-white mb-4">Kurye Vardiya Durumları</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($couriers as $courier)
                <div class="p-4 rounded-lg border {{ $courier['is_on_shift'] ? 'border-green-300 dark:border-green-800 bg-green-50 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900' }}">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center overflow-hidden">
                                @if($courier['avatar'])
                                    <img src="{{ $courier['avatar'] }}" class="w-full h-full object-cover" alt="">
                                @else
                                    <span class="text-gray-600 dark:text-gray-400 font-semibold">{{ substr($courier['name'], 0, 1) }}</span>
                                @endif
                            </div>
                            <span class="font-medium text-black dark:text-white">{{ $courier['name'] }}</span>
                        </div>
                        @if($courier['is_on_shift'])
                            <span class="px-2 py-1 bg-green-500 text-white text-xs rounded-full">Aktif</span>
                        @else
                            <span class="px-2 py-1 bg-gray-400 text-white text-xs rounded-full">Pasif</span>
                        @endif
                    </div>

                    @if($courier['is_on_shift'] && $courier['current_shift'])
                        <div class="text-sm">
                            <p class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Vardiya:</span> {{ $courier['current_shift']['start'] }} - {{ $courier['current_shift']['end'] }}
                            </p>
                            @if($courier['remaining_minutes'])
                                <p class="text-green-600 dark:text-green-400 mt-1">
                                    {{ floor($courier['remaining_minutes'] / 60) }} saat {{ $courier['remaining_minutes'] % 60 }} dk kaldı
                                </p>
                            @endif
                        </div>
                    @elseif($courier['next_shift'])
                        <div class="text-sm">
                            <p class="text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Sonraki:</span> {{ $courier['next_shift']['start'] }} - {{ $courier['next_shift']['end'] }}
                            </p>
                            @if($courier['minutes_until_shift'])
                                <p class="text-orange-600 dark:text-orange-400 mt-1">
                                    {{ floor($courier['minutes_until_shift'] / 60) }} saat {{ $courier['minutes_until_shift'] % 60 }} dk sonra
                                </p>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-gray-500">Bugün vardiyası yok</p>
                    @endif

                    <div class="flex gap-2 mt-3">
                        <button @click="suggestShift({{ $courier['id'] }})"
                                class="flex-1 px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg text-xs font-medium hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                            Öneri Al
                        </button>
                        <button @click="applyTemplateModal({{ $courier['id'] }}, '{{ $courier['name'] }}')"
                                class="flex-1 px-3 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-lg text-xs font-medium hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                            Şablon Uygula
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Courier Efficiency Table -->
        @if(!empty($shiftStats['courier_efficiency']))
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
            <h3 class="font-semibold text-black dark:text-white mb-4">Kurye Verimlilik Sıralaması</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kurye</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Teslimat</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Vardiya (Saat)</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Teslimat/Saat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach($shiftStats['courier_efficiency'] as $index => $eff)
                        <tr>
                            <td class="px-4 py-3">
                                @if($index === 0)
                                    <span class="text-yellow-500">&#x1F947;</span>
                                @elseif($index === 1)
                                    <span class="text-gray-400">&#x1F948;</span>
                                @elseif($index === 2)
                                    <span class="text-orange-500">&#x1F949;</span>
                                @else
                                    <span class="text-gray-500">{{ $index + 1 }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium text-black dark:text-white">{{ $eff['courier_name'] }}</td>
                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $eff['deliveries'] }}</td>
                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400">{{ $eff['shift_hours'] }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 {{ $eff['deliveries_per_hour'] >= 2 ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }} rounded-full text-sm font-medium">
                                    {{ $eff['deliveries_per_hour'] }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Apply Template Modal -->
        <div x-show="showTemplateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showTemplateModal = false"></div>
                <div class="relative bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 w-full max-w-md p-6">
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Şablon Uygula - <span x-text="selectedCourierName"></span></h3>

                    <div class="space-y-3 mb-6">
                        @foreach($templates as $key => $template)
                        <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors"
                               :class="selectedTemplate === '{{ $key }}' ? 'border-black dark:border-white' : 'border-gray-200 dark:border-gray-800'">
                            <input type="radio" name="template" value="{{ $key }}" x-model="selectedTemplate" class="sr-only">
                            <div class="w-4 h-4 rounded-full border-2"
                                 :class="selectedTemplate === '{{ $key }}' ? 'bg-black dark:bg-white border-black dark:border-white' : 'border-gray-300 dark:border-gray-700'"></div>
                            <div>
                                <p class="font-medium text-black dark:text-white">{{ $template['name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $template['start'] }} - {{ $template['end'] }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>

                    <div class="flex gap-3">
                        <button @click="showTemplateModal = false" class="flex-1 px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                            İptal
                        </button>
                        <button @click="applyTemplate()" class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-90 transition-opacity">
                            Uygula
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Template Modal -->
        <div x-show="showBulkTemplateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showBulkTemplateModal = false"></div>
                <div class="relative bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 w-full max-w-lg p-6">
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Toplu Şablon Uygula</h3>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Şablon Seç</label>
                        <select x-model="bulkTemplate" class="w-full px-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
                            <option value="">Şablon seçin...</option>
                            @foreach($templates as $key => $template)
                            <option value="{{ $key }}">{{ $template['name'] }} ({{ $template['start'] }} - {{ $template['end'] }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kuryeler</label>
                        <div class="max-h-48 overflow-y-auto space-y-2 border border-gray-200 dark:border-gray-800 rounded-lg p-3">
                            @foreach($couriers as $courier)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" value="{{ $courier['id'] }}" x-model="bulkCourierIds" class="rounded border-gray-300 dark:border-gray-700">
                                <span class="text-sm text-black dark:text-white">{{ $courier['name'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button @click="showBulkTemplateModal = false" class="flex-1 px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                            İptal
                        </button>
                        <button @click="applyBulkTemplate()" class="flex-1 px-4 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:opacity-90 transition-opacity">
                            Uygula (<span x-text="bulkCourierIds.length"></span> kurye)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Suggestion Modal -->
        <div x-show="showSuggestionModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showSuggestionModal = false"></div>
                <div class="relative bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 w-full max-w-md p-6">
                    <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Vardiya Önerisi</h3>

                    <template x-if="suggestion">
                        <div class="space-y-4">
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <p class="text-sm text-blue-800 dark:text-blue-200" x-text="suggestion.recommendation"></p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-gray-500 mb-1">Önerilen Başlangıç</label>
                                    <p class="text-lg font-semibold text-black dark:text-white" x-text="suggestion.suggested_start"></p>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-500 mb-1">Önerilen Bitiş</label>
                                    <p class="text-lg font-semibold text-black dark:text-white" x-text="suggestion.suggested_end"></p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-500 mb-1">Önerilen Günler</label>
                                <p class="text-sm text-black dark:text-white" x-text="suggestion.suggested_days ? suggestion.suggested_days.join(', ') : '-'"></p>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-500 mb-1">Yoğun Saatler</label>
                                <div class="flex gap-2">
                                    <template x-for="hour in suggestion.peak_hours || []">
                                        <span class="px-2 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded text-sm" x-text="hour + ':00'"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="!suggestion">
                        <p class="text-gray-500 text-center py-4">Yeterli veri bulunamadı.</p>
                    </template>

                    <button @click="showSuggestionModal = false" class="mt-6 w-full px-4 py-2 border border-gray-200 dark:border-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                        Kapat
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function shiftAnalytics() {
            return {
                showTemplateModal: false,
                showBulkTemplateModal: false,
                showSuggestionModal: false,
                selectedCourierId: null,
                selectedCourierName: '',
                selectedTemplate: 'sabah',
                bulkTemplate: '',
                bulkCourierIds: [],
                suggestion: null,

                applyTemplateModal(courierId, name) {
                    this.selectedCourierId = courierId;
                    this.selectedCourierName = name;
                    this.showTemplateModal = true;
                },

                async applyTemplate() {
                    try {
                        const response = await fetch(`/bayi/vardiya/kurye/${this.selectedCourierId}/sablon`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ template: this.selectedTemplate })
                        });

                        const data = await response.json();
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message || 'Bir hata oluştu');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Bir hata oluştu');
                    }
                },

                async applyBulkTemplate() {
                    if (!this.bulkTemplate || this.bulkCourierIds.length === 0) {
                        alert('Lütfen şablon ve en az bir kurye seçin');
                        return;
                    }

                    try {
                        const response = await fetch('/bayi/vardiya/toplu-sablon', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                template: this.bulkTemplate,
                                courier_ids: this.bulkCourierIds.map(id => parseInt(id))
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            alert(data.message);
                            window.location.reload();
                        } else {
                            alert(data.message || 'Bir hata oluştu');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Bir hata oluştu');
                    }
                },

                async suggestShift(courierId) {
                    try {
                        const response = await fetch(`/bayi/vardiya/kurye/${courierId}/oneri`);
                        const data = await response.json();

                        if (data.success) {
                            this.suggestion = data.data;
                            this.showSuggestionModal = true;
                        } else {
                            alert('Öneri alınamadı');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Bir hata oluştu');
                    }
                }
            };
        }

        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            const hourlyData = @json($hourlyDistribution);
            const orderData = @json($shiftStats['hourly_distribution'] ?? []);

            // Hourly Courier Chart
            const hourlyCtx = document.getElementById('hourlyChart')?.getContext('2d');
            if (hourlyCtx) {
                new Chart(hourlyCtx, {
                    type: 'bar',
                    data: {
                        labels: hourlyData.map(d => d.hour),
                        datasets: [{
                            label: 'Aktif Kurye',
                            data: hourlyData.map(d => d.count),
                            backgroundColor: 'rgba(34, 197, 94, 0.5)',
                            borderColor: 'rgb(34, 197, 94)',
                            borderWidth: 1,
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
            }

            // Order Distribution Chart
            const orderCtx = document.getElementById('orderChart')?.getContext('2d');
            if (orderCtx) {
                const hours = Object.keys(orderData).map(h => h + ':00');
                const counts = Object.values(orderData);

                new Chart(orderCtx, {
                    type: 'line',
                    data: {
                        labels: hours,
                        datasets: [{
                            label: 'Sipariş',
                            data: counts,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</x-bayi-layout>
