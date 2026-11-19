<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\User;
use InvalidArgumentException;

class HistoryService
{
    public function __construct(
        private readonly PdfRenderer $pdfRenderer,
        private readonly AuditLogger $auditLogger
    ) {}

    /**
     * Mengunduh ulang PDF sertifikat dari salinan data_cetak yang tersimpan.
     * Jika sertifikat berstatus dibatalkan, PDF otomatis diberi tanda air DIBATALKAN.
     *
     * @param Certificate|int $certificate
     * @param User|int|null $user
     * @return array{certificate: Certificate, filename: string, pdf: string}
     */
    public function download(Certificate|int $certificate, User|int|null $user = null): array
    {
        if (! ($certificate instanceof Certificate)) {
            $certificate = Certificate::query()->with('event')->findOrFail($certificate);
        }

        $userId = $user instanceof User ? $user->id : $user;

        // Tentukan mode rendering berdasarkan status sertifikat
        $mode = $certificate->isDibatalkan() ? 'dibatalkan' : 'terbit';

        // Render PDF dari data cetak dan versi layout tersimpan
        $pdf = $this->pdfRenderer->render($certificate, $mode);

        // Catat jejak audit unduh ulang
        $this->auditLogger->log($userId, 'unduh_ulang', $certificate->id, [
            'nomor'  => $certificate->nomor_lengkap,
            'status' => $certificate->status,
        ]);

        $filename = $this->generateSafeFilename($certificate);

        return [
            'certificate' => $certificate,
            'filename'    => $filename,
            'pdf'         => $pdf,
        ];
    }

    /**
     * Membatalkan sertifikat resmi (Khusus peran Admin).
     * Sesuai aturan PRD RW-05, AB-11, AB-12, AB-13:
     * - Status menjadi 'dibatalkan'
     * - Alasan pembatalan wajib diisi
     * - Baris tidak dihapus dari database
     * - Nomor urut tidak boleh dipakai ulang
     *
     * @param Certificate|int $certificate
     * @param string $alasan
     * @param User|int $admin
     * @return Certificate
     *
     * @throws InvalidArgumentException
     */
    public function cancel(Certificate|int $certificate, string $alasan, User|int $admin): Certificate
    {
        if (! ($certificate instanceof Certificate)) {
            $certificate = Certificate::query()->findOrFail($certificate);
        }

        $adminUser = $admin instanceof User ? $admin : User::query()->findOrFail($admin);

        if (! $adminUser->isAdmin()) {
            throw new InvalidArgumentException('Hanya peran Admin yang berwenang membatalkan sertifikat.');
        }

        if ($certificate->isDibatalkan()) {
            throw new InvalidArgumentException('Sertifikat ini sudah dibatalkan sebelumnya.');
        }

        $alasan = trim($alasan);
        if ($alasan === '') {
            throw new InvalidArgumentException('Alasan pembatalan wajib diisi.');
        }

        $certificate->update([
            'status'          => 'dibatalkan',
            'alasan_batal'    => $alasan,
            'dibatalkan_oleh' => $adminUser->id,
            'dibatalkan_pada' => now(),
        ]);

        $this->auditLogger->log($adminUser->id, 'batal', $certificate->id, [
            'nomor'  => $certificate->nomor_lengkap,
            'alasan' => $alasan,
        ]);

        return $certificate;
    }

    /**
     * Membangun nama berkas aman sesuai PRD PD-10.
     */
    private function generateSafeFilename(Certificate $certificate): string
    {
        $clean = preg_replace("/[\'\"]/", '', $certificate->nama_peserta);
        $clean = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $clean);
        $clean = trim(preg_replace('/_+/', '_', $clean), '_');

        $jenis = ucfirst($certificate->jenis);

        return "Sertifikat_{$jenis}_{$clean}.pdf";
    }
}
