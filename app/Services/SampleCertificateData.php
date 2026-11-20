<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Data contoh untuk kalibrasi dan pratinjau layout (Design System 9.9 dan
 * contoh nomor pada PRD). Dipakai perintah certificates:make-samples dan
 * halaman pratinjau agar keduanya menampilkan isi yang sama.
 */
final class SampleCertificateData
{
    /**
     * @return list<array{jenis: string, berkas: string, data: array<string, string>}>
     */
    public function samples(string $tanggal): array
    {
        $judul = 'Koding Laravel: 20x Lebih Cepat dengan Hermes AI';
        $nomor = fn (int $urut): string => sprintf('35/001/%03d/Xcodetraining/X/2026', $urut);

        return [
            [
                'jenis' => 'seminar',
                'berkas' => 'Sertifikat_Seminar_Arief_Soekardi.pdf',
                'data' => [
                    'nama_peserta' => 'Arief Soekardi',
                    'judul_cetak' => $judul,
                    'nomor_lengkap' => $nomor(3),
                    'tanggal_kota' => $tanggal,
                ],
            ],
            [
                'jenis' => 'seminar',
                'berkas' => 'Sertifikat_Seminar_Mourice.pdf',
                'data' => [
                    'nama_peserta' => 'Mourice',
                    'judul_cetak' => $judul,
                    'nomor_lengkap' => $nomor(9),
                    'tanggal_kota' => $tanggal,
                ],
            ],
            [
                'jenis' => 'seminar',
                'berkas' => 'Sertifikat_Seminar_Muammal_Hamidy.pdf',
                'data' => [
                    'nama_peserta' => "Mu'ammal Hamidy",
                    'judul_cetak' => $judul,
                    'nomor_lengkap' => $nomor(15),
                    'tanggal_kota' => $tanggal,
                ],
            ],
            [
                'jenis' => 'bootcamp',
                'berkas' => 'Sertifikat_Bootcamp_Akrom_Masani_Iksanul_Amri.pdf',
                'data' => [
                    'nama_peserta' => 'Akrom Masani Iksanul Amri',
                    'ujian' => 'Fullstack Web Development',
                    'bootcamp' => 'X-Code Bootcamp #14',
                    'tanggal_kota' => $tanggal,
                ],
            ],
        ];
    }
}
