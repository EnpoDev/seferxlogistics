<?php

namespace App\Http\Requests\Courier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourierLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => 'Enlem değeri zorunludur.',
            'lat.numeric' => 'Enlem sayısal bir değer olmalıdır.',
            'lat.between' => 'Enlem -90 ile 90 arasında olmalıdır.',
            'lng.required' => 'Boylam değeri zorunludur.',
            'lng.numeric' => 'Boylam sayısal bir değer olmalıdır.',
            'lng.between' => 'Boylam -180 ile 180 arasında olmalıdır.',
        ];
    }
}
