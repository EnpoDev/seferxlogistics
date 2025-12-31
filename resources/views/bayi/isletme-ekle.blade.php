@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        :title="(isset($parent) && $parent) ? 'Yeni Şube Ekle' : 'Yeni İşletme Ekle'"
        :subtitle="(isset($parent) && $parent) ? $parent->name . ' işletmesine yeni bir şube ekleyin' : 'Sisteme yeni bir işletme ekleyin'"
        :backUrl="(isset($parent) && $parent) ? route('bayi.isletme-detay', $parent->id) : route('bayi.isletmelerim')"
    />

    {{-- Form --}}
    <x-ui.card class="max-w-7xl mx-auto">
        <form action="{{ route('bayi.isletme-kaydet') }}" method="POST" class="space-y-8">
            @csrf

            @if(isset($parent) && $parent)
                <input type="hidden" name="parent_id" value="{{ $parent->id }}">
                <x-feedback.alert type="info">
                    <div class="flex items-center gap-3">
                        <div>
                            <p class="font-medium">Bağlı İşletme</p>
                            <p>Bu şube <strong>{{ $parent->name }}</strong> altına eklenecektir.</p>
                        </div>
                    </div>
                </x-feedback.alert>
            @endif

            {{-- İşletme Bilgileri --}}
            <x-layout.section title="İşletme Bilgileri" border>
                <x-layout.grid cols="1" mdCols="2">
                    <x-form.input name="name" label="İşletme Adı" placeholder="Örn: Merkez Şube" :value="old('name')" required />
                    <x-form.select name="status" label="Durum" required :options="['active' => 'Aktif', 'passive' => 'Pasif']" :selected="old('status', 'active')" />
                </x-layout.grid>
            </x-layout.section>

            {{-- İletişim Bilgileri --}}
            <x-layout.section title="İletişim Bilgileri" border>
                <x-layout.grid cols="1" mdCols="2">
                    <x-form.input name="phone" label="Telefon" placeholder="0212 555 55 55" :value="old('phone')" required />
                    <x-form.input type="email" name="email" label="E-posta" placeholder="ornek@email.com" :value="old('email')" />
                    <div class="md:col-span-2">
                        <x-form.textarea name="address" label="Adres" placeholder="Açık adres giriniz" :value="old('address')" :rows="3" required />
                    </div>
                </x-layout.grid>
            </x-layout.section>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                <x-ui.button variant="secondary" :href="(isset($parent) && $parent) ? route('bayi.isletme-detay', $parent->id) : route('bayi.isletmelerim')">
                    İptal
                </x-ui.button>
                <x-ui.button type="submit">
                    {{ (isset($parent) && $parent) ? 'Şube Oluştur' : 'İşletme Oluştur' }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
