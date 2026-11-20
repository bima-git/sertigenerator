<?php

declare(strict_types=1);

namespace App\Pdf;

use TCPDF;

/**
 * Dokumen TCPDF untuk sertifikat.
 *
 * Menonaktifkan tautan tersembunyi "Powered by TCPDF" yang biasa ditambahkan
 * TCPDF saat Close(), karena teks itu ikut terbaca saat isi PDF disalin.
 */
final class CertificateDocument extends TCPDF
{
    public function __construct(
        string $orientation,
        string $unit,
        string $format,
        bool $unicode = true,
        string $encoding = 'UTF-8',
        bool $diskcache = false,
    ) {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, false);
        $this->tcpdflink = false;
    }
}
