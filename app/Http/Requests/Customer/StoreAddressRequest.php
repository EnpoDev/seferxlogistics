<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'building_no' => ['nullable', 'string', 'max:20'],
            'floor' => ['nullable', 'string', 'max:10'],
            'apartment_no' => ['nullable', 'string', 'max:20'],
            'directions' => ['nullable', 'string', 'max:500'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Adres başlığı zorunludur.',
            'title.max' => 'Adres başlığı en fazla 50 karakter olabilir.',
            'address.required' => 'Adres zorunludur.',
            'address.max' => 'Adres en fazla 500 karakter olabilir.',
            'lat.between' => 'Geçersiz enlem değeri.',
            'lng.between' => 'Geçersiz boylam değeri.',
            'building_no.max' => 'Bina no en fazla 20 karakter olabilir.',
            'floor.max' => 'Kat en fazla 10 karakter olabilir.',
            'apartment_no.max' => 'Daire no en fazla 20 karakter olabilir.',
            'directions.max' => 'Tarif en fazla 500 karakter olabilir.',
        ];
    }
}
