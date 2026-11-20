<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeInterface;

/**
 * Data contoh untuk kalibrasi dan pratinjau layout (Design System 9.9 dan
 * contoh nomor pada PRD). Dipakai perintah certificates:make-samples dan
 * halaman pratinjau agar keduanya menampilkan isi yang sama.
 */
final class SampleCertificateData
{
    public function __construct(
        private readonly DateId $dates,
    ) {}

    /**
     * Nomor contoh memakai kegiatan yang tercatat di panduan "KODE SERTIFIKAT XCODE":
     * kode 23 (Webinar Siber Expert Asia) dengan penerbit SiberExpert, seperti contoh
     * `23/001/001/SiberExpert/V/2024`. Bulan dan tahun ikut tanggal terbit, jadi tidak
     * bisa menyimpang saat tanggal sampel diganti.
     *
     * @return list<array{jenis: string, berkas: string, data: array<string, string>}>
     */
    public function samples(DateTimeInterface $tanggalTerbit, string $kota = 'Yogyakarta'): array
    {
        $tanggal = $this->dates->formatWithCity($kota, $tanggalTerbit);
        $judul = 'Webinar Siber Expert Asia';
        $nomor = fn (int $urut): string => NumberGenerator::build('23', 1, $urut, 'SiberExpert', $tanggalTerbit);

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
