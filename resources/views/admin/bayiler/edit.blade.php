@extends('layouts.admin')

@section('content')
<div class="animate-fadeIn max-w-2xl">
    <!-- Page Header -->
    <div class="mb-6">
        <a href="{{ route('admin.bayiler.index') }}" class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white mb-4">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Bayilere Dön
        </a>
        <h1 class="text-2xl font-bold text-black dark:text-white">Bayi Düzenle</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ $user->name }} - {{ $user->email }}</p>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
        <form action="{{ route('admin.bayiler.update', $user) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Ad Soyad -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ad Soyad</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-xl text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400 @error('name') border-red-500 @enderror">
                @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">E-posta</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-xl text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400 @error('email') border-red-500 @enderror">
                @error('email')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Sifre -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Yeni Şifre</label>
                <input type="password" name="password"
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-xl text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400 @error('password') border-red-500 @enderror">
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Değiştirmek istemiyorsanız boş bırakın</p>
                @error('password')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Sifre Tekrar -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Şifre Tekrar</label>
                <input type="password" name="password_confirmation"
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-xl text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
            </div>

            <!-- Plan -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Abonelik Planı</label>
                <select name="plan_id"
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-xl text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <option value="">Plan Değiştirme</option>
                    @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" {{ ($user->activeSubscription?->plan_id ?? old('plan_id')) == $plan->id ? 'selected' : '' }}>
                        {{ $plan->name }} - {{ number_format($plan->price, 2) }} TL/{{ $plan->billing_period === 'monthly' ? 'ay' : 'yıl' }}
                    </option>
                    @endforeach
                </select>
                @if($user->activeSubscription)
                <p class="mt-1 text-sm text-green-600 dark:text-green-400">Mevcut plan: {{ $user->activeSubscription->plan->name ?? 'Bilinmiyor' }}</p>
                @else
                <p class="mt-1 text-sm text-yellow-600 dark:text-yellow-400">Bu bayinin aktif aboneliği yok</p>
                @endif
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.bayiler.index') }}" class="px-6 py-3 text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition-colors">
                    İptal
                </a>
                <button type="submit" class="px-6 py-3 bg-black dark:bg-white text-white dark:text-black rounded-xl hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
                    Değişiklikleri Kaydet
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
