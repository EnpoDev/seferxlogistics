<?php

namespace App\Http\Requests\Courier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourierStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:available,busy,offline,on_break'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Durum seçimi zorunludur.',
            'status.in' => 'Geçersiz durum. Geçerli değerler: available, busy, offline, on_break',
        ];
    }
}
