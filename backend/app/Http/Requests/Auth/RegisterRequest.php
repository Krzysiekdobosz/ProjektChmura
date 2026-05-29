<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['nullable', 'in:user,owner'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Imię i nazwisko jest wymagane.',
            'email.required' => 'Adres email jest wymagany.',
            'email.email' => 'Podaj prawidłowy adres email.',
            'email.unique' => 'Ten adres email jest już zajęty.',
            'password.required' => 'Hasło jest wymagane.',
            'password.min' => 'Hasło musi mieć co najmniej 8 znaków.',
            'password.confirmed' => 'Hasła nie są zgodne.',
        ];
    }
}
