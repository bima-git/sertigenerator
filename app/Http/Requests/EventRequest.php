<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diotorisasi untuk membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Siapkan input sebelum validasi berjalan.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode' => is_string($this->kode) ? trim($this->kode) : $this->kode,
            'judul' => is_string($this->judul) ? trim($this->judul) : $this->judul,
            'penerbit' => is_string($this->penerbit) ? trim($this->penerbit) : $this->penerbit,
            'program_default' => is_string($this->program_default) ? trim($this->program_default) : $this->program_default,
            'bernomor' => $this->boolean('bernomor'),
            'pakai_qr' => $this->boolean('pakai_qr'),
            'aktif' => $this->boolean('aktif'),
        ]);
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk permintaan ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jenis' => ['required', 'string', 'in:seminar,bootcamp'],
            // PRD KG-04: Kode kegiatan boleh sama antar kegiatan, tidak ada unique constraint global.
            'kode' => ['required', 'string', 'max:10'],
            'judul' => ['required', 'string', 'max:500'],
            'penerbit' => ['required', 'string', 'max:50'],
            'program_default' => ['nullable', 'string', 'max:200'],
            // Aturan bisnis PRD/PD-07: seminar/webinar wajib bernomor. Bootcamp
            // tetap boleh tanpa nomor sesuai PRD bagian 5.2 butir 6.
            // prepareForValidation() menormalkan field yang tidak dikirim menjadi
            // false, sehingga kasus "field tidak dikirim" ikut tertolak di sini.
            'bernomor' => [
                'required',
                'boolean',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->input('jenis') === 'seminar' && ! $value) {
                        $fail('Kegiatan seminar/webinar wajib memiliki penomoran aktif.');
                    }
                },
            ],
            'pakai_qr' => ['required', 'boolean'],
            'aktif' => ['required', 'boolean'],
        ];
    }

    /**
     * Pesan kesalahan khusus dalam bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jenis.required' => 'Jenis kegiatan wajib dipilih.',
            'jenis.in' => 'Jenis kegiatan harus berupa seminar atau bootcamp.',
            'kode.required' => 'Kode kegiatan wajib diisi.',
            'kode.max' => 'Kode kegiatan tidak boleh melebihi 10 karakter.',
            'judul.required' => 'Judul kegiatan wajib diisi.',
            'judul.max' => 'Judul kegiatan tidak boleh melebihi 500 karakter.',
            'penerbit.required' => 'Penerbit kegiatan wajib diisi.',
            'penerbit.max' => 'Penerbit tidak boleh melebihi 50 karakter.',
            'program_default.max' => 'Program bawaan tidak boleh melebihi 200 karakter.',
        ];
    }
}
