<?php

declare(strict_types=1);

namespace App\Pdf;

use App\Models\Certificate;
use App\Services\DateId;

/**
 * Menyiapkan data siap cetak untuk CertificateRenderer dari model Certificate.
 *
 * Kunci yang dihasilkan adalah kunci yang dibaca elemen di config/layout:
 * nama_peserta, nomor_lengkap, tanggal_kota, dan judul_cetak (seminar) atau
 * bootcamp + ujian (bootcamp). Snapshot data_cetak dipakai lebih dahulu agar
 * unduh ulang mencetak teks yang sama dengan saat penerbitan.
 */
final class CertificateDataMapper
{
    public function __construct(
        private readonly DateId $dateId,
    ) {}

    /**
     * @return array<string, string|bool>
     */
    public function map(Certificate $certificate): array
    {
        $cetak = $this->dataCetak($certificate);
        $event = $certificate->relationLoaded('event')
            ? $certificate->getRelation('event')
            : $certificate->event;

        $data = [
            'nama_peserta' => (string) $certificate->nama_peserta,
            'tanggal_kota' => $this->tanggalKota($certificate, $cetak),
            'pakai_qr' => $this->pakaiQr($cetak, $event?->pakai_qr),
            // Dipakai renderer untuk memilih pita dan domain; sertifikat lama
            // tanpa event tetap dikenali dari segmen penerbit pada nomor.
            'kode' => (string) ($event?->kode ?? ''),
            'penerbit' => (string) ($event?->penerbit ?? ''),
        ];

        $nomor = $this->teks($certificate->nomor_lengkap);

        if ($nomor !== null) {
            $data['nomor_lengkap'] = $nomor;
        }

        if ($certificate->jenis === 'seminar') {
            $data['judul_cetak'] = $this->teks($cetak['judul_kegiatan'] ?? null)
                ?? $this->teks($event?->judul)
                ?? '';

            return $data;
        }

        $data['bootcamp'] = $this->teks($cetak['bootcamp'] ?? null)
            ?? $this->teks($event?->judul)
            ?? '';

        $data['ujian'] = $this->teks($cetak['ujian'] ?? null)
            ?? $this->teks($cetak['program'] ?? null)
            ?? $this->teks($event?->program_default)
            ?? '';

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function dataCetak(Certificate $certificate): array
    {
        $cetak = $certificate->data_cetak;

        return is_array($cetak) ? $cetak : [];
    }

    /**
     * @param  array<string, mixed>  $cetak
     */
    private function tanggalKota(Certificate $certificate, array $cetak): string
    {
        $tersimpan = $this->teks($cetak['tanggal_kota'] ?? null);

        if ($tersimpan !== null) {
            return $tersimpan;
        }

        // Sertifikat lama belum menyimpan tanggal_kota; susun ulang dari tanggal terbit.
        $kota = (string) config('app.kota_terbit', 'Yogyakarta');
        $tanggal = $certificate->tanggal_terbit ?? now();

        return $this->dateId->formatWithCity($kota, $tanggal);
    }

    /**
     * @param  array<string, mixed>  $cetak
     */
    private function pakaiQr(array $cetak, ?bool $dariEvent): bool
    {
        if (array_key_exists('pakai_qr', $cetak) && $cetak['pakai_qr'] !== null) {
            return (bool) $cetak['pakai_qr'];
        }

        return $dariEvent ?? true;
    }

    private function teks(mixed $nilai): ?string
    {
        if (! is_scalar($nilai)) {
            return null;
        }

        $teks = trim((string) $nilai);

        return $teks === '' ? null : $teks;
    }
}
