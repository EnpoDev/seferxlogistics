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
        <h1 class="text-2xl font-bold text-black dark:text-white">Yeni Bayi Oluştur</h1>
        <p class="text-gray-600 dark:text-gray-400">Sisteme yeni bir bayi ekleyin</p>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-[#1a1a1a] rounded-xl border border-gray-200 dark:border-gray-800 p-6">
        <form action="{{ route('admin.bayiler.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Ad Soyad -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ad Soyad</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-xl text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400 @error('name') border-red-500 @enderror">
                @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">E-posta</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-xl text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400 @error('email') border-red-500 @enderror">
                @error('email')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Sifre -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Şifre</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-xl text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400 @error('password') border-red-500 @enderror">
                @error('password')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Sifre Tekrar -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Şifre Tekrar</label>
                <input type="password" name="password_confirmation" required
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-xl text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
            </div>

            <!-- Plan -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Abonelik Planı</label>
                <select name="plan_id"
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-black border border-gray-200 dark:border-gray-700 rounded-xl text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <option value="">Varsayılan Plan ({{ $plans->first()?->name ?? 'Yok' }})</option>
                    @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                        {{ $plan->name }} - {{ number_format($plan->price, 2) }} TL/{{ $plan->billing_period === 'monthly' ? 'ay' : 'yıl' }}
                    </option>
                    @endforeach
                </select>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Boş bırakırsanız varsayılan plan atanacaktır</p>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.bayiler.index') }}" class="px-6 py-3 text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition-colors">
                    İptal
                </a>
                <button type="submit" class="px-6 py-3 bg-black dark:bg-white text-white dark:text-black rounded-xl hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
                    Bayi Oluştur
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
