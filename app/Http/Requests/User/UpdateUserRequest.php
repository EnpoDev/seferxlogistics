<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $userId],
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['in:bayi,isletme,admin'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'İsim zorunludur.',
            'email.required' => 'E-posta adresi zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi giriniz.',
            'email.unique' => 'Bu e-posta adresi başka bir kullanıcıya ait.',
            'password.confirmed' => 'Şifre onayı eşleşmiyor.',
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'roles.required' => 'En az bir rol seçmelisiniz.',
        ];
    }
}
