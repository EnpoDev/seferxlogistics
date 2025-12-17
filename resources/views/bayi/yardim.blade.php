<x-bayi-layout>
    <x-slot name="title">Yardım - Bayi Paneli</x-slot>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-black dark:text-white">Yardım & Destek</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Sıkça sorulan sorular ve destek kaynakları</p>
            </div>
        </div>

        <!-- İletişim Kartları -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Teknik Destek -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 hover:border-black dark:hover:border-white transition-colors p-6">
                <div class="p-4 bg-black dark:bg-white rounded-xl w-fit mb-4">
                    <svg class="w-8 h-8 text-white dark:text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-black dark:text-white mb-2">Teknik Destek</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">7/24 teknik destek hattımız</p>
                <button class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors font-medium">
                    İletişime Geç
                </button>
            </div>

            <!-- Dokümantasyon -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 hover:border-black dark:hover:border-white transition-colors p-6">
                <div class="p-4 bg-black dark:bg-white rounded-xl w-fit mb-4">
                    <svg class="w-8 h-8 text-white dark:text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-black dark:text-white mb-2">Dokümantasyon</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Kullanım kılavuzu ve rehberler</p>
                <button class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors font-medium">
                    Görüntüle
                </button>
            </div>

            <!-- Video Eğitimler -->
            <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800 hover:border-black dark:hover:border-white transition-colors p-6">
                <div class="p-4 bg-black dark:bg-white rounded-xl w-fit mb-4">
                    <svg class="w-8 h-8 text-white dark:text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-black dark:text-white mb-2">Video Eğitimler</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Adım adım video kılavuzlar</p>
                <button class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-900 text-black dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors font-medium">
                    İzle
                </button>
            </div>
        </div>

        <!-- SSS -->
        <div class="bg-white dark:bg-[#181818] rounded-xl border border-gray-200 dark:border-gray-800">
            <div class="p-6">
                <h3 class="text-xl font-semibold text-black dark:text-white mb-6">Sıkça Sorulan Sorular</h3>
                <div class="space-y-4">
                    <div class="border-b border-gray-200 dark:border-gray-800 pb-4">
                        <h4 class="text-base font-medium text-black dark:text-white mb-2">Kurye nasıl eklerim?</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Kuryelerim sayfasından "Yeni Kurye Ekle" butonuna tıklayarak kurye ekleyebilirsiniz.</p>
                    </div>
                    <div class="border-b border-gray-200 dark:border-gray-800 pb-4">
                        <h4 class="text-base font-medium text-black dark:text-white mb-2">Vardiya saatleri nasıl düzenlenir?</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Vardiya Saatleri menüsünden kurye seçerek haftalık vardiya planı oluşturabilirsiniz.</p>
                    </div>
                    <div class="pb-4">
                        <h4 class="text-base font-medium text-black dark:text-white mb-2">Bölge nasıl tanımlanır?</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Bölgelendirme sayfasından harita üzerinde bölge çizerek tanımlama yapabilirsiniz.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-bayi-layout>

