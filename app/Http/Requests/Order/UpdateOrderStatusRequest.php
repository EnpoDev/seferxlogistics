<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,preparing,ready,on_delivery,delivered,cancelled,returned,approved'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Sipariş durumu zorunludur.',
            'status.in' => 'Geçersiz sipariş durumu.',
        ];
    }
}
