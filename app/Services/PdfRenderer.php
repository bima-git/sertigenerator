<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CertificateMode;
use App\Models\Certificate;
use App\Pdf\CertificateDataMapper;
use App\Pdf\CertificateRenderer;

/**
 * Titik masuk rendering PDF sertifikat untuk lapisan layanan.
 *
 * Kelas ini hanya menjembatani kontrak IssueService/HistoryService
 * (render(Certificate, string)) ke engine desain App\Pdf\CertificateRenderer.
 */
class PdfRenderer
{
    public function __construct(
        private readonly CertificateRenderer $renderer,
        private readonly CertificateDataMapper $mapper,
    ) {}

    /**
     * Merender sertifikat menjadi format biner PDF.
     *
     * @param  string  $mode  'terbit', 'pratinjau', atau 'dibatalkan'
     * @return string Konten biner PDF
     */
    public function render(Certificate $certificate, string $mode = 'terbit'): string
    {
        return $this->renderer->render(
            (string) $certificate->jenis,
            $this->mapper->map($certificate),
            CertificateMode::fromInput($mode),
            versiLayout: $certificate->versi_layout,
        );
    }
}
