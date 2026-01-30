@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="Yeni Kurye Ekle"
        subtitle="Ekibinize yeni bir kurye dahil edin"
        :backUrl="route('bayi.kuryelerim')"
    />

    {{-- Form --}}
    <x-ui.card class="max-w-7xl mx-auto">
        <form action="{{ route('bayi.kurye-kaydet') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf

            {{-- Kişisel Bilgiler --}}
            <x-layout.section title="Kişisel Bilgiler" border>
                <x-layout.grid cols="1" mdCols="2" lgCols="3">
                    {{-- Fotoğraf --}}
                    <div class="col-span-1 md:col-span-2 lg:col-span-1">
                        <x-form.form-group label="Kurye Fotoğrafı">
                            <div class="flex items-center gap-4">
                                <div class="w-20 h-20 shrink-0 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center overflow-hidden border border-gray-200 dark:border-gray-700">
                                    <x-ui.icon name="user" class="w-8 h-8 text-gray-400" />
                                </div>
                                <input type="file" name="photo" accept="image/*"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-colors">
                            </div>
                        </x-form.form-group>
                    </div>

                    <x-form.input name="name" label="Ad Soyad" placeholder="Örn: Ahmet Yılmaz" :value="old('name')" required />
                    <x-form.input name="phone" label="Cep Telefonu" placeholder="05XX XXX XX XX" :value="old('phone')" required />
                    <x-form.input type="password" name="password" label="Şifre" placeholder="Mobil uygulama şifresi" hint="Kurye mobil uygulaması için şifre" />

                    <x-form.select name="platform" label="Platform" :options="['android' => 'Android', 'ios' => 'iOS']" :selected="old('platform')" placeholder="Seçiniz" />
                    <x-form.input type="email" name="email" label="E-posta" placeholder="ornek@email.com" :value="old('email')" />
                </x-layout.grid>
            </x-layout.section>

            {{-- Vergi ve Finans Bilgileri --}}
            <x-layout.section title="Vergi ve Finans Bilgileri" border>
                <x-layout.grid cols="1" mdCols="2" lgCols="3">
                    <x-form.input type="number" name="vat_rate" label="KDV Oranı (%)" placeholder="20" :value="old('vat_rate', 0)" />
                    <x-form.input type="number" name="withholding_rate" label="Tevkifat Oranı (%)" placeholder="0" :value="old('withholding_rate', 0)" />
                    <x-form.input name="tax_number" label="Vergi Numarası/TCKN" placeholder="11 haneli numara" :value="old('tax_number')" />
                    <x-form.input name="iban" label="IBAN" placeholder="TR00 0000 0000 0000 0000 0000 00" :value="old('iban')" />
                    <x-form.input name="kobi_key" label="SeferXYemek Key" placeholder="SeferXYemek Key" :value="old('kobi_key')" />
                </x-layout.grid>
            </x-layout.section>

            {{-- Şirket Bilgileri --}}
            <x-layout.section title="Şirket Bilgileri" border>
                <x-layout.grid cols="1" mdCols="2" lgCols="3">
                    <x-form.input name="company_name" label="Şirket Adı" placeholder="Şirket Adı" :value="old('company_name')" />
                    <x-form.input name="tax_office" label="Vergi Dairesi" placeholder="Vergi Dairesi" :value="old('tax_office')" />
                    <x-form.textarea name="company_address" label="Şirket Adresi" placeholder="Şirket Adresi" :value="old('company_address')" :rows="2" />
                </x-layout.grid>
            </x-layout.section>

            {{-- Araç ve Çalışma Bilgileri --}}
            <x-layout.section title="Araç ve Çalışma Bilgileri" border>
                <x-layout.grid cols="1" mdCols="2" lgCols="3">
                    <x-form.input name="vehicle_plate" label="Araç Plakası" placeholder="34 AB 123" :value="old('vehicle_plate')" />

                    <x-form.select name="work_type" label="Çalışma Tipi" placeholder="Seçiniz" :selected="old('work_type')" :options="[
                        'full_time' => 'Tam Zamanlı',
                        'part_time' => 'Yarı Zamanlı',
                        'freelance' => 'Serbest'
                    ]" />

                    <x-form.select name="tier" label="Sınıf" :selected="old('tier', 'bronze')" :options="[
                        'bronze' => 'Bronz',
                        'silver' => 'Gümüş',
                        'gold' => 'Altın',
                        'platinum' => 'Platin'
                    ]" />

                    <x-form.input type="number" name="max_package_limit" label="Paket Taşıma Limiti" :value="old('max_package_limit', 5)" hint="Aynı anda taşınabilecek maksimum paket sayısı" />

                    <x-form.select name="status" label="Başlangıç Durumu" required :selected="old('status', 'offline')" :options="[
                        'available' => 'Müsait - Hemen iş alabilir',
                        'active' => 'Aktif - Sistemde görünür',
                        'offline' => 'Çevrimdışı - Pasif durumda'
                    ]" />
                </x-layout.grid>
            </x-layout.section>

            {{-- Çalışma Şekli ve Fiyatlandırma --}}
            <x-layout.section title="Çalışma Şekli ve Fiyatlandırma" border>
                <div class="mb-6">
                    <x-form.select name="working_type" label="Kurye Çalışma Şekli" id="workingType" :selected="old('working_type', 'per_package')" :options="[
                        'per_package' => 'Paket Başı',
                        'per_km' => 'Kilometre Başı',
                        'km_range' => 'Kilometre Aralığı',
                        'package_plus_km' => 'Paket Başı + Km Başı',
                        'fixed_km_plus_km' => 'Belirli Km + Km Başı',
                        'commission' => 'Komisyon Oranı',
                        'tiered_package' => 'Kademeli Paket Başı'
                    ]" />
                </div>

                {{-- Fiyatlandırma Sekmeleri --}}
                <div x-data="{ activeTab: 1 }">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="flex space-x-4">
                            @for($i = 1; $i <= 5; $i++)
                            <button type="button" @click="activeTab = {{ $i }}"
                                :class="activeTab === {{ $i }} ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-b-2 border-blue-500' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                                class="px-4 py-2 text-sm font-medium rounded-t-lg transition-colors">
                                Fiyatlandırma {{ $i }}
                            </button>
                            @endfor
                        </nav>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-black/30 rounded-b-lg border border-t-0 border-gray-200 dark:border-gray-700">
                        @for($i = 1; $i <= 5; $i++)
                        <div x-show="activeTab === {{ $i }}" x-cloak>
                            <x-layout.grid cols="1" mdCols="2" lgCols="3" gap="4">
                                <div class="pricing-field" data-types="per_package,package_plus_km,tiered_package">
                                    <x-form.input type="number" name="pricing_data[{{ $i }}][package_price]" label="Paket Başı Ücreti (TL)" placeholder="55.00" :value="old('pricing_data.' . $i . '.package_price')" />
                                </div>
                                <div class="pricing-field" data-types="per_km,package_plus_km,fixed_km_plus_km">
                                    <x-form.input type="number" name="pricing_data[{{ $i }}][km_price]" label="Km Başı Ücreti (TL)" placeholder="3.50" :value="old('pricing_data.' . $i . '.km_price')" />
                                </div>
                                <div class="pricing-field" data-types="fixed_km_plus_km">
                                    <x-form.input type="number" name="pricing_data[{{ $i }}][start_km]" label="Başlangıç Km" placeholder="5" :value="old('pricing_data.' . $i . '.start_km')" />
                                </div>
                                <div class="pricing-field" data-types="fixed_km_plus_km">
                                    <x-form.input type="number" name="pricing_data[{{ $i }}][start_price]" label="Başlangıç Ücreti (TL)" placeholder="25.00" :value="old('pricing_data.' . $i . '.start_price')" />
                                </div>
                                <div class="pricing-field" data-types="km_range">
                                    <x-form.input type="number" name="pricing_data[{{ $i }}][min_km]" label="Min Km" placeholder="0" :value="old('pricing_data.' . $i . '.min_km')" />
                                </div>
                                <div class="pricing-field" data-types="km_range">
                                    <x-form.input type="number" name="pricing_data[{{ $i }}][max_km]" label="Max Km" placeholder="10" :value="old('pricing_data.' . $i . '.max_km')" />
                                </div>
                                <div class="pricing-field" data-types="km_range">
                                    <x-form.input type="number" name="pricing_data[{{ $i }}][range_price]" label="Aralık Ücreti (TL)" placeholder="40.00" :value="old('pricing_data.' . $i . '.range_price')" />
                                </div>
                                <div class="pricing-field" data-types="commission">
                                    <x-form.input type="number" name="pricing_data[{{ $i }}][commission_rate]" label="Komisyon Oranı (%)" placeholder="15" :value="old('pricing_data.' . $i . '.commission_rate')" />
                                </div>
                                <div class="pricing-field" data-types="tiered_package">
                                    <x-form.input type="number" name="pricing_data[{{ $i }}][tier2_count]" label="2. Kademe Paket Sayısı" placeholder="5" :value="old('pricing_data.' . $i . '.tier2_count')" />
                                </div>
                                <div class="pricing-field" data-types="tiered_package">
                                    <x-form.input type="number" name="pricing_data[{{ $i }}][tier2_price]" label="2. Kademe Ücreti (TL)" placeholder="50.00" :value="old('pricing_data.' . $i . '.tier2_price')" />
                                </div>
                            </x-layout.grid>
                        </div>
                        @endfor
                    </div>
                </div>
            </x-layout.section>

            {{-- Yetkiler ve Ayarlar --}}
            <x-layout.section title="Yetkiler ve Ayarlar">
                <x-layout.grid cols="1" mdCols="2" lgCols="3">
                    <x-form.radio-group name="can_reject_package" label="Paket Reddedebilir" :selected="old('can_reject_package', '1')" :options="['1' => 'Evet', '0' => 'Hayır']" inline />
                    <x-form.radio-group name="payment_editing_enabled" label="Ödeme Düzenleme" :selected="old('payment_editing_enabled', '1')" :options="['1' => 'Açık', '0' => 'Kapalı']" inline />
                    <x-form.radio-group name="status_change_enabled" label="Durum Değiştirme" :selected="old('status_change_enabled', '1')" :options="['1' => 'Açık', '0' => 'Kapalı']" inline />
                </x-layout.grid>
            </x-layout.section>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                <x-ui.button variant="secondary" :href="route('bayi.kuryelerim')">İptal</x-ui.button>
                <x-ui.button type="submit">Kurye Oluştur</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>

@push('scripts')
<script>
    function updatePricingFields() {
        const workingType = document.getElementById('workingType').value;
        document.querySelectorAll('.pricing-field').forEach(field => {
            const types = field.dataset.types.split(',');
            field.style.display = types.includes(workingType) ? 'block' : 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        updatePricingFields();
        document.getElementById('workingType').addEventListener('change', updatePricingFields);
    });
</script>
@endpush
@endsection
