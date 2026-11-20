<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TextMeasurer;
use TCPDF;

/**
 * Pengukur lebar teks memakai metrik font yang sedang dipakai TCPDF.
 */
final class TcpdfTextMeasurer implements TextMeasurer
{
    public function __construct(
        private readonly TCPDF $pdf,
        private readonly string $family,
        private readonly string $style = '',
    ) {}

    public function width(string $text, float $size): float
    {
        return (float) $this->pdf->GetStringWidth($text, $this->family, $this->style, $size);
    }
}
