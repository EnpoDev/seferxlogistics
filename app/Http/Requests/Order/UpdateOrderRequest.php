<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
            'status' => ['required', 'in:pending,preparing,ready,on_delivery,delivered,cancelled,returned,approved'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Müşteri adı zorunludur.',
            'customer_phone.required' => 'Müşteri telefonu zorunludur.',
            'customer_phone.regex' => 'Geçerli bir telefon numarası giriniz.',
            'customer_address.required' => 'Müşteri adresi zorunludur.',
            'delivery_fee.required' => 'Teslimat ücreti zorunludur.',
            'status.required' => 'Sipariş durumu zorunludur.',
            'status.in' => 'Geçersiz sipariş durumu.',
            'items.required' => 'En az bir ürün eklemelisiniz.',
            'items.min' => 'En az bir ürün eklemelisiniz.',
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
