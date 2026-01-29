<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['in:bayi,isletme,admin'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'İsim zorunludur.',
            'name.max' => 'İsim en fazla 255 karakter olabilir.',
            'email.required' => 'E-posta adresi zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
            'password.required' => 'Şifre zorunludur.',
            'password.confirmed' => 'Şifre onayı eşleşmiyor.',
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'roles.required' => 'En az bir rol seçmelisiniz.',
            'roles.min' => 'En az bir rol seçmelisiniz.',
            'roles.*.in' => 'Geçersiz rol seçimi.',
        ];
    }
}
