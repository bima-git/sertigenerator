<?php

namespace App\Services;

use App\Models\Certificate;

class PdfRenderer
{
    /**
     * Merender sertifikat menjadi format biner PDF.
     *
     * @param Certificate $certificate
     * @param string $mode 'terbit', 'pratinjau', atau 'dibatalkan'
     * @return string Konten biner PDF
     */
    public function render(Certificate $certificate, string $mode = 'terbit'): string
    {
        $watermark = '';
        if ($mode === 'pratinjau') {
            $watermark = ' [WATERMARK: PRATINJAU]';
        } elseif ($mode === 'dibatalkan') {
            $watermark = ' [WATERMARK: DIBATALKAN]';
        }

        // Struktur dokumen PDF standar biner valid (%PDF-1.4)
        $nama = $certificate->nama_peserta ?? 'Peserta';
        $nomor = $certificate->nomor_lengkap ?? 'Tanpa Nomor';

        $stream = "BT /F1 12 Tf 50 500 Td (Sertifikat XCODE: {$nama} - {$nomor}{$watermark}) Tj ET";
        $length = strlen($stream);

        return "%PDF-1.4\n"
            . "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n"
            . "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n"
            . "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Contents 4 0 R >> endobj\n"
            . "4 0 obj << /Length {$length} >> stream\n"
            . "{$stream}\n"
            . "endstream\n"
            . "endobj\n"
            . "xref\n"
            . "0 5\n"
            . "0000000000 65535 f \n"
            . "0000000010 00000 n \n"
            . "0000000060 00000 n \n"
            . "0000000117 00000 n \n"
            . "0000000215 00000 n \n"
            . "trailer << /Size 5 /Root 1 0 R >>\n"
            . "startxref\n"
            . "350\n"
            . "%%EOF\n";
    }
}
