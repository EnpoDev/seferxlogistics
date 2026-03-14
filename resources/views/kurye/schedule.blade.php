@extends('layouts.kurye')

@section('content')
<div class="p-4 space-y-4 slide-up" x-data="scheduleApp()">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-black dark:text-white">Haftalik Takvim</h2>
    </div>

    <!-- Week Navigation -->
    <div class="flex items-center justify-between bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-3">
        <a href="{{ route('kurye.schedule', ['week' => $weekOffset - 1]) }}" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 touch-active">
            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div class="text-center">
            <p class="text-sm font-semibold text-black dark:text-white">
                {{ $startOfWeek->translatedFormat('d M') }} - {{ $endOfWeek->translatedFormat('d M Y') }}
            </p>
            @if($weekOffset === 0)
                <p class="text-xs text-green-600 dark:text-green-400 font-medium">Bu Hafta</p>
            @elseif($weekOffset === 1)
                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Gelecek Hafta</p>
            @elseif($weekOffset === -1)
                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Gecen Hafta</p>
            @endif
        </div>
        <a href="{{ route('kurye.schedule', ['week' => $weekOffset + 1]) }}" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 touch-active">
            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    @if($weekOffset !== 0)
        <div class="text-center">
            <a href="{{ route('kurye.schedule') }}" class="text-sm text-blue-600 dark:text-blue-400 font-medium">Bu haftaya don</a>
        </div>
    @endif

    <!-- Weekly Calendar -->
    <div class="space-y-3">
        @foreach($weekDays as $day)
            @php
                $isToday = $day['date']->isToday();
                $isPast = $day['date']->isPast() && !$isToday;
                $hasShifts = $day['shifts']->isNotEmpty();
                $hasBenefits = $day['benefits']->isNotEmpty();
            @endphp
            <div class="bg-white dark:bg-gray-900 border {{ $isToday ? 'border-blue-500 dark:border-blue-400 ring-1 ring-blue-500/20' : 'border-gray-200 dark:border-gray-800' }} rounded-xl overflow-hidden {{ $isPast ? 'opacity-60' : '' }}">
                <!-- Day Header -->
                <div class="flex items-center justify-between px-4 py-3 {{ $isToday ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-gray-50 dark:bg-gray-800/50' }}">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $isToday ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                            <span class="text-sm font-bold">{{ $day['date']->format('d') }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-semibold {{ $isToday ? 'text-blue-600 dark:text-blue-400' : 'text-black dark:text-white' }}">
                                {{ $day['date']->translatedFormat('l') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $day['date']->translatedFormat('d F') }}</p>
                        </div>
                    </div>
                    @if($isToday)
                        <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-medium rounded-full">Bugun</span>
                    @endif
                </div>

                <!-- Day Content -->
                <div class="px-4 py-3">
                    @if(!$hasShifts && !$hasBenefits)
                        <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-2">Vardiya veya yemek bilgisi yok</p>
                    @else
                        <div class="space-y-3">
                            <!-- Shifts -->
                            @foreach($day['shifts'] as $shift)
                                <div class="flex items-start space-x-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                                        {{ $shift->meal_type === 'breakfast' ? 'bg-amber-100 dark:bg-amber-900/30' : '' }}
                                        {{ $shift->meal_type === 'lunch' ? 'bg-orange-100 dark:bg-orange-900/30' : '' }}
                                        {{ $shift->meal_type === 'dinner' ? 'bg-purple-100 dark:bg-purple-900/30' : '' }}">
                                        @if($shift->meal_type === 'breakfast')
                                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                            </svg>
                                        @elseif($shift->meal_type === 'lunch')
                                            <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-medium text-black dark:text-white">
                                                @if($shift->meal_type === 'breakfast') Kahvalti Vardiyasi
                                                @elseif($shift->meal_type === 'lunch') Ogle Vardiyasi
                                                @else Aksam Vardiyasi
                                                @endif
                                            </p>
                                            @if($shift->isCurrentlyActive())
                                                <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-medium rounded-full">Aktif</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ $shift->start_time->format('H:i') }} - {{ $shift->end_time->format('H:i') }}
                                            <span class="text-gray-400 dark:text-gray-500">({{ number_format($shift->getDurationHours(), 1) }} saat)</span>
                                        </p>
                                        @if($shift->notes)
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $shift->notes }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            <!-- Meal Benefits (Restaurant assignments) -->
                            @foreach($day['benefits'] as $benefit)
                                <div class="flex items-start space-x-3 {{ $day['shifts']->isNotEmpty() ? 'pt-2 border-t border-gray-100 dark:border-gray-800' : '' }}">
                                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-medium text-black dark:text-white">
                                                @if($benefit->meal_type === 'breakfast') Kahvalti
                                                @elseif($benefit->meal_type === 'lunch') Ogle Yemegi
                                                @else Aksam Yemegi
                                                @endif
                                            </p>
                                            @if($benefit->is_used)
                                                <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 text-xs font-medium rounded-full">Kullanildi</span>
                                            @elseif($benefit->isExpired())
                                                <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-500 dark:text-red-400 text-xs font-medium rounded-full">Suresi Doldu</span>
                                            @else
                                                <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-medium rounded-full">Gecerli</span>
                                            @endif
                                        </div>
                                        @if($benefit->branch)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                <span class="font-medium">{{ $benefit->branch->name }}</span>
                                            </p>
                                        @endif
                                        @if($benefit->meal_value > 0)
                                            <p class="text-xs text-green-600 dark:text-green-400 mt-0.5 font-medium">{{ number_format($benefit->meal_value, 2, ',', '.') }} TL</p>
                                        @endif
                                        @if($benefit->notes)
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $benefit->notes }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
    function scheduleApp() {
        return {
            // Swipe navigation for week change
            touchStartX: 0,
            touchEndX: 0,

            init() {
                const container = this.$el;
                container.addEventListener('touchstart', (e) => {
                    this.touchStartX = e.changedTouches[0].screenX;
                }, { passive: true });

                container.addEventListener('touchend', (e) => {
                    this.touchEndX = e.changedTouches[0].screenX;
                    this.handleSwipe();
                }, { passive: true });
            },

            handleSwipe() {
                const diff = this.touchStartX - this.touchEndX;
                const threshold = 80;

                if (Math.abs(diff) < threshold) return;

                if (diff > 0) {
                    // Swipe left -> next week
                    window.location.href = '{{ route("kurye.schedule", ["week" => $weekOffset + 1]) }}';
                } else {
                    // Swipe right -> previous week
                    window.location.href = '{{ route("kurye.schedule", ["week" => $weekOffset - 1]) }}';
                }
            }
        }
    }
</script>
@endpush
