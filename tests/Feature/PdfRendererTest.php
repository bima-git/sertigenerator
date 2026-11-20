<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Event;
use App\Pdf\CertificateDataMapper;
use App\Pdf\CertificateRenderer;
use App\Services\LayoutConfig;
use App\Services\PdfRenderer;
use Tests\TestCase;

/**
 * Menguji kontrak yang dipanggil form penerbitan: teks tanda air bebas harus
 * sampai ke PDF tanpa pemanggil perlu menyentuh CertificateRenderer.
 */
final class PdfRendererTest extends TestCase
{
    public function test_forwards_custom_watermark_from_form(): void
    {
        $pdf = $this->service()->render($this->certificate(), 'pratinjau', 'SALINAN FORM');
        $teks = $this->textOf($pdf);

        $this->assertStringContainsString('SALINAN FORM', $teks);
        $this->assertStringNotContainsString('PRATINJAU', $teks);
    }

    public function test_uses_mode_default_when_watermark_is_null(): void
    {
        $teks = $this->textOf($this->service()->render($this->certificate(), 'pratinjau'));

        $this->assertStringContainsString('PRATINJAU', $teks);
    }

    private function service(): PdfRenderer
    {
        $this->app->instance(LayoutConfig::class, $this->layoutsWithCoreFonts());

        return new PdfRenderer(
            $this->app->make(CertificateRenderer::class),
            $this->app->make(CertificateDataMapper::class),
        );
    }

    private function layoutsWithCoreFonts(): LayoutConfig
    {
        $layouts = [];

        foreach (['seminar', 'bootcamp'] as $jenis) {
            /** @var array<string, mixed> $layout */
            $layout = require config_path("layout/{$jenis}.php");
            $layout['font'] = ['serif' => 'times', 'serif_bold' => 'times', 'fallback' => 'times'];
            $layouts[$jenis] = $layout;
        }

        return new LayoutConfig($layouts);
    }

    private function certificate(): Certificate
    {
        $certificate = new Certificate([
            'jenis' => 'seminar',
            'nama_peserta' => 'Arief Soekardi',
            'nomor_lengkap' => '35/001/003/Xcodetraining/X/2026',
            'tanggal_terbit' => '2026-10-24',
            'data_cetak' => ['judul_kegiatan' => 'Koding Laravel: 20x Lebih Cepat'],
            'versi_layout' => null,
        ]);

        $certificate->setRelation('event', new Event([
            'jenis' => 'seminar',
            'judul' => 'Koding Laravel: 20x Lebih Cepat',
            'bernomor' => true,
            'pakai_qr' => true,
        ]));

        return $certificate;
    }

    /**
     * Mengambil teks dari operator TJ/Tj pada stream PDF yang terkompresi.
     */
    private function textOf(string $pdf): string
    {
        preg_match_all('/stream\r?\n(.*?)endstream/s', $pdf, $streams);

        $teks = '';

        foreach ($streams[1] as $stream) {
            $isi = @gzuncompress($stream);

            if ($isi === false) {
                continue;
            }

            preg_match_all('/\[\((.*?)\)\] TJ/', $isi, $cocok);
            $teks .= implode(' ', $cocok[1]).' ';

            preg_match_all('/\((.*?)\) Tj/', $isi, $cocokTj);
            $teks .= implode(' ', $cocokTj[1]).' ';
        }

        return str_replace(['\\(', '\\)'], ['(', ')'], $teks);
    }
}
