@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Yardım & Destek"
        subtitle="Sıkça sorulan sorular ve destek kaynakları"
    >
        <x-slot name="icon">
            <x-ui.icon name="help" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>
    </x-layout.page-header>

    {{-- İletişim Kartları --}}
    <x-layout.grid cols="1" mdCols="3" gap="6" class="mb-6">
        {{-- Teknik Destek --}}
        <x-ui.card class="hover:border-black dark:hover:border-white transition-colors">
            <div class="p-4 bg-black dark:bg-white rounded-xl w-fit mb-4">
                <x-ui.icon name="support" class="w-8 h-8 text-white dark:text-black" />
            </div>
            <h3 class="text-xl font-semibold text-black dark:text-white mb-2">Teknik Destek</h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">7/24 teknik destek hattımız</p>
            <x-ui.button variant="secondary" class="w-full">
                İletişime Geç
            </x-ui.button>
        </x-ui.card>

        {{-- Dokumantasyon --}}
        <x-ui.card class="hover:border-black dark:hover:border-white transition-colors">
            <div class="p-4 bg-black dark:bg-white rounded-xl w-fit mb-4">
                <x-ui.icon name="document" class="w-8 h-8 text-white dark:text-black" />
            </div>
            <h3 class="text-xl font-semibold text-black dark:text-white mb-2">Dokümantasyon</h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Kullanım kılavuzu ve rehberler</p>
            <x-ui.button variant="secondary" class="w-full">
                Görüntüle
            </x-ui.button>
        </x-ui.card>

        {{-- Video Egitimler --}}
        <x-ui.card class="hover:border-black dark:hover:border-white transition-colors">
            <div class="p-4 bg-black dark:bg-white rounded-xl w-fit mb-4">
                <x-ui.icon name="play" class="w-8 h-8 text-white dark:text-black" />
            </div>
            <h3 class="text-xl font-semibold text-black dark:text-white mb-2">Video Eğitimler</h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Adım adım video kılavuzlar</p>
            <x-ui.button variant="secondary" class="w-full">
                İzle
            </x-ui.button>
        </x-ui.card>
    </x-layout.grid>

    {{-- SSS --}}
    <x-ui.card>
        <x-layout.section title="Sıkça Sorulan Sorular">
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
        </x-layout.section>
    </x-ui.card>
</div>
@endsection
