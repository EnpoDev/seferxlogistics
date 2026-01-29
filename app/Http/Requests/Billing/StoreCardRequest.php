<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

class StoreCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'card_holder_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZğüşıöçĞÜŞİÖÇ\s]+$/u'],
            'card_number' => ['required', 'string', 'min:16', 'max:19', 'regex:/^[0-9\s]+$/'],
            'expiry_month' => ['required', 'integer', 'min:1', 'max:12'],
            'expiry_year' => ['required', 'integer', 'min:' . date('Y'), 'max:' . (date('Y') + 20)],
            'cvv' => ['required', 'string', 'min:3', 'max:4', 'regex:/^[0-9]+$/'],
            'is_default' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'card_holder_name.required' => 'Kart sahibi adı zorunludur.',
            'card_holder_name.regex' => 'Kart sahibi adı sadece harf içermelidir.',
            'card_number.required' => 'Kart numarası zorunludur.',
            'card_number.min' => 'Kart numarası en az 16 karakter olmalıdır.',
            'card_number.max' => 'Kart numarası en fazla 19 karakter olabilir.',
            'card_number.regex' => 'Kart numarası sadece rakam içermelidir.',
            'expiry_month.required' => 'Son kullanma ayı zorunludur.',
            'expiry_month.min' => 'Geçersiz ay.',
            'expiry_month.max' => 'Geçersiz ay.',
            'expiry_year.required' => 'Son kullanma yılı zorunludur.',
            'expiry_year.min' => 'Kartın son kullanma tarihi geçmiş.',
            'cvv.required' => 'CVV zorunludur.',
            'cvv.min' => 'CVV en az 3 karakter olmalıdır.',
            'cvv.max' => 'CVV en fazla 4 karakter olabilir.',
            'cvv.regex' => 'CVV sadece rakam içermelidir.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('card_number')) {
            $this->merge([
                'card_number' => preg_replace('/\s+/', '', $this->card_number),
            ]);
        }
    }
}
