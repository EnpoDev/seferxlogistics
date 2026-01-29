<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:customers,phone', 'regex:/^[0-9\s\-\+\(\)]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Müşteri adı zorunludur.',
            'name.max' => 'Müşteri adı en fazla 255 karakter olabilir.',
            'phone.required' => 'Telefon numarası zorunludur.',
            'phone.unique' => 'Bu telefon numarası zaten kayıtlı.',
            'phone.regex' => 'Geçerli bir telefon numarası giriniz.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'address.max' => 'Adres en fazla 500 karakter olabilir.',
            'lat.between' => 'Geçersiz enlem değeri.',
            'lng.between' => 'Geçersiz boylam değeri.',
            'notes.max' => 'Notlar en fazla 1000 karakter olabilir.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/[^0-9+]/', '', $this->phone),
            ]);
        }
    }
}
