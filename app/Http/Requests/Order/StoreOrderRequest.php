<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9\s\-\+\(\)]+$/'],
            'customer_address' => ['required', 'string', 'max:500'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'restaurant_id' => ['nullable', 'exists:restaurants,id'],
            'courier_id' => ['nullable', 'exists:couriers,id'],
            'delivery_fee' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'payment_method' => ['nullable', 'in:cash,card,online'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'auto_assign_courier' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Müşteri adı zorunludur.',
            'customer_name.max' => 'Müşteri adı en fazla 255 karakter olabilir.',
            'customer_phone.required' => 'Müşteri telefonu zorunludur.',
            'customer_phone.regex' => 'Geçerli bir telefon numarası giriniz.',
            'customer_address.required' => 'Müşteri adresi zorunludur.',
            'customer_address.max' => 'Adres en fazla 500 karakter olabilir.',
            'lat.between' => 'Geçersiz enlem değeri.',
            'lng.between' => 'Geçersiz boylam değeri.',
            'branch_id.exists' => 'Seçilen şube bulunamadı.',
            'restaurant_id.exists' => 'Seçilen restoran bulunamadı.',
            'courier_id.exists' => 'Seçilen kurye bulunamadı.',
            'delivery_fee.required' => 'Teslimat ücreti zorunludur.',
            'delivery_fee.min' => 'Teslimat ücreti negatif olamaz.',
            'delivery_fee.max' => 'Teslimat ücreti çok yüksek.',
            'payment_method.in' => 'Geçersiz ödeme yöntemi.',
            'notes.max' => 'Notlar en fazla 1000 karakter olabilir.',
            'items.required' => 'En az bir ürün eklemelisiniz.',
            'items.min' => 'En az bir ürün eklemelisiniz.',
            'items.max' => 'Bir siparişte en fazla 50 ürün olabilir.',
            'items.*.product_id.required' => 'Ürün seçimi zorunludur.',
            'items.*.product_id.exists' => 'Seçilen ürün bulunamadı.',
            'items.*.quantity.required' => 'Ürün adedi zorunludur.',
            'items.*.quantity.min' => 'Ürün adedi en az 1 olmalıdır.',
            'items.*.quantity.max' => 'Ürün adedi en fazla 99 olabilir.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('customer_phone')) {
            $this->merge([
                'customer_phone' => preg_replace('/[^0-9+]/', '', $this->customer_phone),
            ]);
        }
    }
}
