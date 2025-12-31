@extends('layouts.kurye')

@section('content')
<div class="p-4 space-y-6 slide-up">
    <!-- Profile Header -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 text-center">
        <div class="w-24 h-24 bg-gray-200 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
            @if($courier->photo_path)
                <img src="{{ Storage::url($courier->photo_path) }}" alt="{{ $courier->name }}" class="w-24 h-24 rounded-full object-cover">
            @else
                <span class="text-3xl font-bold text-gray-600 dark:text-gray-400">{{ substr($courier->name, 0, 2) }}</span>
            @endif
        </div>
        <h2 class="text-xl font-bold text-black dark:text-white">{{ $courier->name }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $courier->phone }}</p>
        
        <div class="flex items-center justify-center mt-3">
            <span class="px-3 py-1 text-sm font-medium rounded-full
                {{ $courier->status === 'available' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                {{ $courier->status === 'busy' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : '' }}
                {{ $courier->status === 'offline' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' : '' }}
                {{ $courier->status === 'on_break' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}">
                {{ $courier->getStatusLabel() }}
            </span>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 text-center">
            <p class="text-3xl font-bold text-black dark:text-white">{{ $courier->total_deliveries ?? 0 }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Toplam Teslimat</p>
        </div>
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 text-center">
            <p class="text-3xl font-bold text-black dark:text-white">{{ number_format($courier->average_delivery_time ?? 0, 0) }} dk</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ort. Teslimat</p>
        </div>
    </div>

    <!-- Monthly Stats -->
    <div class="rounded-2xl p-6 text-white" style="background: linear-gradient(to bottom right, #22c55e, #059669);">
        <h3 class="text-sm font-medium opacity-80 mb-4">Bu Ay</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-3xl font-bold">{{ $monthlyDelivered }}</p>
                <p class="text-sm opacity-80">Teslimat</p>
            </div>
            <div>
                <p class="text-3xl font-bold">₺{{ number_format($monthlyEarnings, 0, ',', '.') }}</p>
                <p class="text-sm opacity-80">Kazanç</p>
            </div>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl divide-y divide-gray-200 dark:divide-gray-800">
        @if($courier->vehicle_plate)
        <div class="flex items-center justify-between p-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
                <span class="text-sm text-gray-600 dark:text-gray-400">Araç Plakası</span>
            </div>
            <span class="text-sm font-medium text-black dark:text-white">{{ $courier->vehicle_plate }}</span>
        </div>
        @endif
        
        @if($courier->email)
        <div class="flex items-center justify-between p-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-sm text-gray-600 dark:text-gray-400">E-posta</span>
            </div>
            <span class="text-sm font-medium text-black dark:text-white">{{ $courier->email }}</span>
        </div>
        @endif
        
        <div class="flex items-center justify-between p-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-sm text-gray-600 dark:text-gray-400">Son Giriş</span>
            </div>
            <span class="text-sm font-medium text-black dark:text-white">
                {{ $courier->last_login_at ? $courier->last_login_at->diffForHumans() : 'Bilinmiyor' }}
            </span>
        </div>
    </div>

    <!-- Logout Button -->
    <form method="POST" action="{{ route('kurye.logout') }}">
        @csrf
        <button type="submit" class="w-full py-4 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 rounded-xl font-semibold touch-active">
            Çıkış Yap
        </button>
    </form>
</div>
@endsection

