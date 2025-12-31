@extends('layouts.app')

@section('content')
<div class="p-6 animate-fadeIn">
    {{-- Page Header --}}
    <x-layout.page-header
        title="İşletme Bilgileri"
        subtitle="İşletmenizin temel bilgileri"
    >
        <x-slot name="icon">
            <x-ui.icon name="business" class="w-7 h-7 text-black dark:text-white" />
        </x-slot>
    </x-layout.page-header>

    {{-- Form --}}
    <x-ui.card>
        <form action="{{ route('isletmem.bilgiler.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <x-layout.grid cols="1" mdCols="2" gap="6">
                <x-form.input
                    name="name"
                    label="İşletme Adı"
                    :value="old('name', $business->name ?? '')"
                    required
                />

                <x-form.input
                    name="phone"
                    label="Telefon"
                    :value="old('phone', $business->phone ?? '')"
                />

                <x-form.input
                    type="email"
                    name="email"
                    label="E-posta"
                    :value="old('email', $business->email ?? '')"
                />

                <x-form.input
                    name="tax_number"
                    label="Vergi No"
                    :value="old('tax_number', $business->tax_number ?? '')"
                />
            </x-layout.grid>

            <x-form.textarea
                name="address"
                label="Adres"
                :value="old('address', $business->address ?? '')"
                :rows="3"
            />

            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-800">
                <x-ui.button type="submit">
                    Kaydet
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
