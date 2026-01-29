<x-bayi-layout>
    <x-slot name="title">Haftalik Karsilastirma - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('bayi.analytics.index') }}" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-black dark:text-white">Haftalik Karsilastirma</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Bu hafta vs gecen hafta performansi</p>
                </div>
            </div>
            <select onchange="window.location.href='?branch_id='+this.value" class="px-4 py-2 bg-white dark:bg-[#181818] border border-gray-200 dark:border-gray-800 rounded-lg text-sm">
                <option value="">Tum Subeler</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Comparison Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- This Week -->
            <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">{{ $comparison['this_week']['label'] }}</h3>
                    <span class="text-sm text-white/70">{{ $comparison['this_week']['start'] }} - {{ $comparison['this_week']['end'] }}</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-white/70 text-sm">Toplam Siparis</p>
                        <p class="text-3xl font-bold">{{ number_format($comparison['this_week']['stats']['total_orders']) }}</p>
                    </div>
                    <div>
                        <p class="text-white/70 text-sm">Toplam Gelir</p>
                        <p class="text-3xl font-bold">{{ number_format($comparison['this_week']['stats']['total_revenue'], 0) }} TL</p>
                    </div>
                    <div>
                        <p class="text-white/70 text-sm">Teslimat Orani</p>
                        <p class="text-2xl font-bold">%{{ $comparison['this_week']['stats']['delivery_rate'] }}</p>
                    </div>
                    <div>
                        <p class="text-white/70 text-sm">Ort. Siparis</p>
                        <p class="text-2xl font-bold">{{ number_format($comparison['this_week']['stats']['avg_order_value'], 0) }} TL</p>
                    </div>
                </div>
            </div>

            <!-- Last Week -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-black dark:text-white">{{ $comparison['last_week']['label'] }}</h3>
                    <span class="text-sm text-gray-500">{{ $comparison['last_week']['start'] }} - {{ $comparison['last_week']['end'] }}</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-500 text-sm">Toplam Siparis</p>
                        <p class="text-3xl font-bold text-black dark:text-white">{{ number_format($comparison['last_week']['stats']['total_orders']) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Toplam Gelir</p>
                        <p class="text-3xl font-bold text-black dark:text-white">{{ number_format($comparison['last_week']['stats']['total_revenue'], 0) }} TL</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Teslimat Orani</p>
                        <p class="text-2xl font-bold text-black dark:text-white">%{{ $comparison['last_week']['stats']['delivery_rate'] }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Ort. Siparis</p>
                        <p class="text-2xl font-bold text-black dark:text-white">{{ number_format($comparison['last_week']['stats']['avg_order_value'], 0) }} TL</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Difference Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Siparis Farki</p>
                        <p class="text-3xl font-bold {{ $comparison['comparison']['orders_diff'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $comparison['comparison']['orders_diff'] >= 0 ? '+' : '' }}{{ number_format($comparison['comparison']['orders_diff']) }}
                        </p>
                    </div>
                    <div class="p-3 {{ $comparison['comparison']['orders_diff'] >= 0 ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }} rounded-lg">
                        @if($comparison['comparison']['orders_diff'] >= 0)
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                        @else
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Gelir Farki</p>
                        <p class="text-3xl font-bold {{ $comparison['comparison']['revenue_diff'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $comparison['comparison']['revenue_diff'] >= 0 ? '+' : '' }}{{ number_format($comparison['comparison']['revenue_diff'], 0) }} TL
                        </p>
                    </div>
                    <div class="p-3 {{ $comparison['comparison']['revenue_diff'] >= 0 ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }} rounded-lg">
                        @if($comparison['comparison']['revenue_diff'] >= 0)
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                        @else
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Teslimat Orani Farki</p>
                        <p class="text-3xl font-bold {{ $comparison['comparison']['delivery_rate_diff'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $comparison['comparison']['delivery_rate_diff'] >= 0 ? '+' : '' }}{{ $comparison['comparison']['delivery_rate_diff'] }}%
                        </p>
                    </div>
                    <div class="p-3 {{ $comparison['comparison']['delivery_rate_diff'] >= 0 ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }} rounded-lg">
                        @if($comparison['comparison']['delivery_rate_diff'] >= 0)
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                        @else
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-bayi-layout>
