<!DOCTYPE html>
<html lang="tr" x-data="{ darkMode: localStorage.getItem('kuryeDarkMode') === 'true' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#000000">
    <title>Kurye Girişi - SeferX</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        .dark { background-color: #0a0a0a; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-[#0a0a0a] min-h-screen flex flex-col">
    <div class="flex-1 flex flex-col justify-center px-6 py-12">
        <!-- Logo -->
        <div class="text-center mb-8">
            <img src="{{ asset('logo-dark.png') }}" alt="SeferX" class="h-12 mx-auto dark:hidden">
            <img src="{{ asset('logo-white.png') }}" alt="SeferX" class="h-12 mx-auto hidden dark:block">
            <h1 class="mt-4 text-2xl font-bold text-black dark:text-white">Kurye Paneli</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Hesabınıza giriş yapın</p>
        </div>

        <!-- Login Form -->
        <div class="w-full max-w-sm mx-auto">
            <form method="POST" action="{{ route('kurye.login.submit') }}" class="space-y-5">
                @csrf

                <!-- Phone Number -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-black dark:text-white mb-2">
                        Telefon Numarası
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               value="{{ old('phone') }}"
                               placeholder="5XX XXX XX XX"
                               class="w-full pl-12 pr-4 py-4 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-black dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-black dark:focus:ring-white focus:border-transparent text-lg"
                               required
                               autofocus>
                    </div>
                    @error('phone')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-black dark:text-white mb-2">
                        Şifre
                    </label>
                    <div class="relative" x-data="{ showPassword: false }">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input :type="showPassword ? 'text' : 'password'" 
                               id="password" 
                               name="password" 
                               placeholder="••••••••"
                               class="w-full pl-12 pr-12 py-4 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-black dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-black dark:focus:ring-white focus:border-transparent text-lg"
                               required>
                        <button type="button" 
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600">
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="remember" 
                           name="remember"
                           class="w-4 h-4 rounded border-gray-300 dark:border-gray-700 text-black dark:text-white focus:ring-black dark:focus:ring-white">
                    <label for="remember" class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                        Beni hatırla
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full py-4 bg-black dark:bg-white text-white dark:text-black rounded-xl font-semibold text-lg hover:opacity-90 transition-opacity active:scale-[0.98]">
                    Giriş Yap
                </button>
            </form>

            <!-- Help Text -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Şifrenizi mi unuttunuz?
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Yöneticinizle iletişime geçin.
                </p>
            </div>
        </div>
    </div>

    <!-- Dark Mode Toggle -->
    <div class="absolute top-4 right-4">
        <button @click="darkMode = !darkMode; localStorage.setItem('kuryeDarkMode', darkMode)" 
                class="p-2 rounded-full text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
            <svg x-show="!darkMode" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
            <svg x-show="darkMode" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </button>
    </div>

    <!-- Footer -->
    <div class="text-center py-4 text-xs text-gray-400">
        © {{ date('Y') }} SeferX Lojistik
    </div>
</body>
</html>

