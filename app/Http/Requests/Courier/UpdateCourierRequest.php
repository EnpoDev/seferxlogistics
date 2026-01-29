<?php

namespace App\Http\Requests\Courier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9\s\-\+\(\)]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', 'in:available,busy,offline,on_break,active'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'photo' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,jpg,webp'],
            'tc_no' => ['nullable', 'string', 'size:11', 'regex:/^[0-9]+$/'],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            'shift_start' => ['nullable', 'date_format:H:i'],
            'shift_end' => ['nullable', 'date_format:H:i'],
            'max_delivery_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'notification_enabled' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:4', 'max:50'],
            'platform' => ['nullable', 'in:android,ios'],
            'work_type' => ['nullable', 'in:full_time,part_time,freelance'],
            'tier' => ['nullable', 'in:bronze,silver,gold,platinum'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'withholding_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'tax_office' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:11', 'regex:/^[0-9]+$/'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'iban' => ['nullable', 'string', 'max:50', 'regex:/^TR[0-9]{24}$/i'],
            'kobi_key' => ['nullable', 'string', 'max:100'],
            'can_reject_package' => ['nullable', 'boolean'],
            'max_package_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'payment_editing_enabled' => ['nullable', 'boolean'],
            'status_change_enabled' => ['nullable', 'boolean'],
            'working_type' => ['nullable', 'in:per_package,per_km,km_range,package_plus_km,fixed_km_plus_km,commission,tiered_package'],
            'pricing_data' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Kurye adı zorunludur.',
            'phone.required' => 'Telefon numarası zorunludur.',
            'phone.regex' => 'Geçerli bir telefon numarası giriniz.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'status.required' => 'Durum seçimi zorunludur.',
            'status.in' => 'Geçersiz durum.',
            'lat.between' => 'Geçersiz enlem değeri.',
            'lng.between' => 'Geçersiz boylam değeri.',
            'photo.image' => 'Dosya bir resim olmalıdır.',
            'photo.max' => 'Fotoğraf en fazla 2MB olabilir.',
            'tc_no.size' => 'TC kimlik numarası 11 haneli olmalıdır.',
            'iban.regex' => 'Geçerli bir IBAN giriniz.',
        ];
    }
}
