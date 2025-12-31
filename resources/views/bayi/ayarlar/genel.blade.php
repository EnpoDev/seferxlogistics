<x-bayi-layout>
    <x-slot name="title">Genel Ayarlar - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Genel Ayarlar</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Hesap bilgilerinizi duzenleyin</p>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-green-700 dark:text-green-400">{{ session('success') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                @foreach($errors->all() as $error)
                    <p class="text-red-700 dark:text-red-400">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- Ayarlar Formu -->
        <form action="{{ route('bayi.ayarlar.genel.update') }}" method="POST">
            @csrf

            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Ad Soyad</label>
                        <input type="text"
                               name="name"
                               value="{{ old('name', $user->name) }}"
                               class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-black dark:focus:border-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">E-posta</label>
                        <input type="email"
                               name="email"
                               value="{{ old('email', $user->email) }}"
                               class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-black dark:focus:border-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-black dark:text-white mb-2">Telefon</label>
                        <input type="tel"
                               name="phone"
                               value="{{ old('phone', $user->phone ?? '') }}"
                               class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg border border-gray-200 dark:border-gray-800 focus:outline-none focus:border-black dark:focus:border-white">
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-black dark:bg-white text-white dark:text-black rounded-lg hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors font-medium">
                        Kaydet
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-bayi-layout>
