<?php

namespace App\Exceptions;

/**
 * Teks (nama atau judul) tidak muat di sertifikat walau ukuran huruf sudah minimum
 * dan sudah dipecah ke jumlah baris maksimum (PRD PD-05, PD-06).
 * Ini kesalahan isian, bukan galat sistem: ditampilkan ke pengguna sebagai 422.
 */
class TextOverflowException extends RenderException
{
    public function __construct(public readonly string $text, string $message)
    {
        parent::__construct($message);
    }

    /** Field form asal teks yang tidak muat. Judul bisa berasal dari judul kegiatan bila judul_cetak kosong. */
    public function fieldIn(\Illuminate\Http\Request $request): string
    {
        foreach (['nama_peserta', 'program', 'perusahaan'] as $field) {
            if (trim((string) $request->input($field)) === $this->text) {
                return $field;
            }
        }

        return 'judul_cetak';
    }
}
