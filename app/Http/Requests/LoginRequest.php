<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dapatkan aturan validasi untuk permintaan login.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Berisi username atau email (masa transisi login email).
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Pesan kustom validasi bahasa Indonesia sesuai Design System Bagian 10.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Email atau username wajib diisi.',
            'username.max'      => 'Email atau username tidak boleh melebihi 255 karakter.',
            'password.required' => 'Kata sandi wajib diisi.',
        ];
    }
}
