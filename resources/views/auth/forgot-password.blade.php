<!DOCTYPE html>
<html lang="tr" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Şifremi Unuttum - SeferX Lojistik</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .dark { background-color: #181818; }
    </style>
</head>
<body class="bg-white dark:bg-[#181818] transition-colors duration-200">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="w-full max-w-md">
            <!-- Logo / Header -->
            <div class="text-center mb-8">
                <img src="{{ asset('logo-dark.png') }}" alt="SeferX Logo" class="h-16 w-auto mx-auto mb-4 dark:hidden">
                <img src="{{ asset('logo-white.png') }}" alt="SeferX Logo" class="h-16 w-auto mx-auto mb-4 hidden dark:block">
                <h1 class="text-3xl font-bold text-black dark:text-white">Şifremi Unuttum</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Şifrenizi sıfırlamak için e-posta adresinizi girin</p>
            </div>

            <!-- Forgot Password Form -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-8">
                @if (session('status'))
                    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm text-green-800 dark:text-green-200">{{ session('status') }}</p>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 p-4 bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
                        <p class="text-sm text-gray-800 dark:text-gray-200">{{ $errors->first() }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">E-posta Adresi</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-black dark:text-white placeholder-gray-500 focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                               placeholder="ornek@email.com">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Hesabınıza kayıtlı e-posta adresini girin. Size şifre sıfırlama bağlantısı göndereceğiz.
                        </p>
                    </div>

                    <button type="submit"
                            class="w-full px-4 py-3 bg-black dark:bg-white text-white dark:text-black font-semibold rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
                        Şifre Sıfırlama Bağlantısı Gönder
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('login') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white inline-flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Giriş sayfasına dön
                    </a>
                </div>
            </div>

            <!-- Dark Mode Toggle -->
            <div class="mt-6 flex justify-center">
                <button @click="darkMode = !darkMode"
                        class="p-3 rounded-lg border border-gray-200 dark:border-gray-800 text-black dark:text-white hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</body>
</html>

