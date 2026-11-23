<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diotorisasi untuk membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Siapkan data sebelum divalidasi.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('nama') && is_string($this->nama)) {
            $merge['nama'] = trim($this->nama);
        }

        if ($this->has('username') && is_string($this->username)) {
            $merge['username'] = trim($this->username);
        }

        if ($this->has('aktif')) {
            // Form self-edit men-disable checkbox aktif tetapi hidden aktif=0
            // tetap terkirim. Akun sendiri selalu dipaksa aktif oleh
            // UserController::update, jadi nilai itu dinormalkan agar edit
            // profil yang diizinkan tidak ikut gagal validasi (BUG-1).
            $merge['aktif'] = $this->isEditingSelf() ? true : $this->boolean('aktif');
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    /**
     * Apakah request ini mengubah akun milik pengguna yang sedang login?
     */
    private function isEditingSelf(): bool
    {
        $userId = $this->route('id');
        $currentUser = $this->user();

        return $userId !== null
            && $currentUser !== null
            && (int) $userId === $currentUser->id;
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk permintaan ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'nama' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9._-]+$/',
                $userId
                    ? Rule::unique('users', 'username')->ignore($userId)
                    : 'unique:users,username',
            ],
            'peran' => [$userId ? 'nullable' : 'required', 'string', 'in:operator,admin'],
            'aktif' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Konfigurasi validasi lanjutan untuk proteksi keamanan akun.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $userId = $this->route('id');
            if (! $userId) {
                return;
            }

            /** @var User|null $targetUser */
            $targetUser = User::find($userId);
            if (! $targetUser) {
                return;
            }

            $currentAdmin = $this->user();

            // 1. Proteksi self-deactivation & self-demotion (PRD PG-02)
            if ($currentAdmin && $currentAdmin->id === $targetUser->id) {
                if ($this->has('aktif') && ! $this->boolean('aktif')) {
                    $validator->errors()->add('aktif', 'Anda tidak dapat menonaktifkan akun sendiri.');
                }

                if ($this->filled('peran') && $this->input('peran') !== 'admin') {
                    $validator->errors()->add('peran', 'Anda tidak dapat menurunkan peran akun sendiri.');
                }
            }

            // 2. Proteksi last active admin (PRD PG-03)
            if ($targetUser->isAdmin() && $targetUser->aktif) {
                $activeAdminCount = User::query()
                    ->where('peran', 'admin')
                    ->where('aktif', true)
                    ->count();

                if ($activeAdminCount <= 1) {
                    if ($this->has('aktif') && ! $this->boolean('aktif')) {
                        $validator->errors()->add('aktif', 'Admin aktif terakhir tidak dapat dinonaktifkan.');
                    }

                    if ($this->filled('peran') && $this->input('peran') !== 'admin') {
                        $validator->errors()->add('peran', 'Peran admin aktif terakhir tidak dapat diubah.');
                    }
                }
            }
        });
    }

    /**
     * Pesan kesalahan khusus dalam bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'nama.max' => 'Nama lengkap tidak boleh melebihi 100 karakter.',
            'username.required' => 'Username wajib diisi.',
            'username.max' => 'Username tidak boleh melebihi 50 karakter.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik, tanda hubung, dan garis bawah.',
            'username.unique' => 'Username sudah digunakan oleh akun lain.',
            'peran.required' => 'Peran pengguna wajib dipilih.',
            'peran.in' => 'Peran pengguna harus berupa operator atau admin.',
        ];
    }
}
