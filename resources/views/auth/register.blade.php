<!DOCTYPE html>
<html lang="tr" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kayıt Ol - SeferX Lojistik</title>

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
                <h1 class="text-3xl font-bold text-black dark:text-white">SeferX Lojistik</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Yeni hesap oluşturun</p>
            </div>

            <!-- Register Form -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 p-8">
                @if ($errors->any())
                    <div class="mb-6 p-4 bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg">
                        <ul class="text-sm text-gray-800 dark:text-gray-200 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Ad Soyad</label>
                        <input type="text" name="name" value="{{ old('name') }}" required autofocus
                               class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-black dark:text-white placeholder-gray-500 focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                               placeholder="Ahmet Yılmaz">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">E-posta</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-black dark:text-white placeholder-gray-500 focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                               placeholder="ornek@email.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Şifre</label>
                        <input type="password" name="password" required
                               class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-black dark:text-white placeholder-gray-500 focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                               placeholder="••••••••">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">En az 8 karakter olmalıdır</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Şifre Tekrar</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-black dark:text-white placeholder-gray-500 focus:outline-none focus:border-black dark:focus:border-white transition-colors"
                               placeholder="••••••••">
                    </div>

                    <button type="submit"
                            class="w-full px-4 py-3 bg-black dark:bg-white text-white dark:text-black font-semibold rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
                        Kayıt Ol
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Zaten hesabınız var mı?
                        <a href="{{ route('login') }}" class="font-medium text-black dark:text-white hover:underline">
                            Giriş yapın
                        </a>
                    </p>
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
