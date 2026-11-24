<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IssueRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk permintaan ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jenis'           => ['required', 'string', 'in:seminar,bootcamp'],
            'event_id'        => ['required', 'integer', 'exists:events,id'],
            'nama_peserta'    => [
                'required',
                'string',
                'min:2',
                'max:100',
                "regex:/^[\p{L}\s\.\,\'\-]+$/u",
            ],
            'tanggal_terbit'  => ['required', 'date'],

            // Khusus Seminar / Webinar
            'judul_cetak'     => ['nullable', 'string', 'max:200'],
            'seri'            => ['nullable', 'integer', 'min:1', 'max:999'],
            'urut'            => ['nullable', 'integer', 'min:1', 'max:999'],

            // Khusus Bootcamp / Magang
            'program'         => ['required_if:jenis,bootcamp', 'nullable', 'string', 'max:150'],
            'perusahaan'      => ['required_if:jenis,bootcamp', 'nullable', 'string', 'max:150'],
            'tanggal_mulai'   => ['required_if:jenis,bootcamp', 'nullable', 'date'],
            'tanggal_selesai' => ['required_if:jenis,bootcamp', 'nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ];
    }

    /**
     * Pesan kesalahan khusus berbahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jenis.required'                    => 'Jenis sertifikat wajib dipilih.',
            'event_id.required'                 => 'Kegiatan wajib dipilih.',
            'event_id.exists'                   => 'Kegiatan yang dipilih tidak valid atau tidak ditemukan.',
            'nama_peserta.required'             => 'Nama peserta wajib diisi.',
            'nama_peserta.min'                  => 'Nama peserta minimal 2 karakter.',
            'nama_peserta.max'                  => 'Nama peserta maksimal 100 karakter.',
            'nama_peserta.regex'                => 'Nama hanya boleh berisi huruf, spasi, titik, koma, apostrof, dan tanda hubung.',
            'tanggal_terbit.required'           => 'Tanggal terbit wajib diisi.',
            'tanggal_terbit.date'               => 'Format tanggal terbit tidak valid.',
            'program.required_if'               => 'Nama program wajib diisi untuk jenis bootcamp/magang.',
            'perusahaan.required_if'            => 'Perusahaan penempatan wajib diisi untuk jenis bootcamp/magang.',
            'tanggal_mulai.required_if'         => 'Tanggal mulai wajib diisi untuk jenis bootcamp/magang.',
            'tanggal_selesai.required_if'       => 'Tanggal selesai wajib diisi untuk jenis bootcamp/magang.',
            'tanggal_selesai.after_or_equal'    => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }

    /**
     * Validasi lanjutan untuk memeriksa status aktif dan kesesuaian jenis kegiatan.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            if ($this->filled('event_id')) {
                $event = \App\Models\Event::query()->find($this->input('event_id'));
                if ($event) {
                    if (! $event->aktif) {
                        $validator->errors()->add('event_id', "Kegiatan [{$event->judul}] sudah tidak aktif.");
                    } elseif ($this->filled('jenis') && $event->jenis !== $this->input('jenis')) {
                        $validator->errors()->add('event_id', 'Jenis kegiatan tidak sesuai dengan jenis formulir.');
                    } elseif ($event->jenis === 'seminar' && ! $event->bernomor) {
                        $validator->errors()->add('event_id', "Kegiatan seminar [{$event->judul}] wajib memiliki penomoran aktif.");
                    }
                }
            }
        });
    }
}
