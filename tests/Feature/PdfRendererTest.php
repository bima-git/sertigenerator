<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CertificateMode;
use App\Exceptions\RenderException;
use App\Services\LayoutConfig;
use App\Services\PdfRenderer;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class PdfRendererTest extends TestCase
{
    private PdfRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        // Font inti "times" membuat isi PDF tetap terbaca sebagai teks ASCII di tes,
        // tanpa bergantung pada berkas Tinos yang belum tersedia.
        $layouts = [];

        foreach (['seminar', 'bootcamp'] as $jenis) {
            /** @var array<string, mixed> $layout */
            $layout = require config_path("layout/{$jenis}.php");
            $layout['font'] = ['serif' => 'times', 'serif_bold' => 'times', 'fallback' => 'times'];
            $layouts[$jenis] = $layout;
        }

        $this->app->instance(LayoutConfig::class, new LayoutConfig($layouts));
        $this->renderer = $this->app->make(PdfRenderer::class);
    }

    public function test_renders_seminar_text_at_configured_fields(): void
    {
        $pdf = $this->renderer->render('seminar', $this->seminarData(), withBackground: false);

        $this->assertStringStartsWith('%PDF-', $pdf);

        $teks = $this->textOf($pdf);

        $this->assertStringContainsString('SERTIFIKAT SEMINAR', $teks);
        $this->assertStringContainsString('Arief Soekardi', $teks);
        $this->assertStringContainsString('35/001/003/Xcodetraining/X/2026', $teks);
        $this->assertStringContainsString('xcode.co.id/cekkodesertifikat', $teks);
        $this->assertStringContainsString('Yogyakarta, 24 Oktober 2026', $teks);
    }

    public function test_renders_seminar_fixed_elements(): void
    {
        $teks = $this->textOf($this->renderer->render('seminar', $this->seminarData(), withBackground: false));

        $this->assertStringContainsString('Sertifikat', $teks);
        $this->assertStringContainsString('diberikan kepada', $teks);
        $this->assertStringContainsString('xcode.or.id', $teks);
        $this->assertStringContainsString('Cynthia', $teks);
        $this->assertStringContainsString('Direktur', $teks);
    }

    public function test_draws_qr_by_default(): void
    {
        $pdf = $this->renderer->render('seminar', $this->seminarData(), withBackground: false);

        $this->assertGreaterThan(200, substr_count($this->uncompressed($pdf), ' re'));
    }

    public function test_guides_add_grid_and_element_markers(): void
    {
        $pdf = $this->renderer->render(
            'seminar',
            $this->seminarData(),
            withBackground: false,
            withGuides: true,
        );

        $teks = $this->textOf($pdf);

        $this->assertStringContainsString('GARIS BANTU', $teks);
        $this->assertSame(1, preg_match_all('/\/Type \/Page\b/', $pdf));
    }

    public function test_renders_single_page(): void
    {
        $pdf = $this->renderer->render('seminar', $this->seminarData(), withBackground: false);

        $this->assertSame(1, preg_match_all('/\/Type \/Page\b/', $pdf));
    }

    public function test_writes_pdf_to_file(): void
    {
        $folder = storage_path('framework/testing/renderer');
        File::deleteDirectory($folder);

        $path = $this->renderer->renderToFile(
            'seminar',
            $this->seminarData(),
            $folder.'/sertifikat.pdf',
            withBackground: false,
        );

        $this->assertFileExists($path);
        $this->assertStringStartsWith('%PDF-', (string) file_get_contents($path));

        File::deleteDirectory($folder);
    }

    public function test_requires_configured_background(): void
    {
        $this->expectException(RenderException::class);
        $this->expectExceptionMessage('tidak ditemukan');

        $this->renderer->render(
            'seminar',
            $this->seminarData(),
            backgroundPath: 'assets/templates/latar-tidak-ada.png',
        );
    }

    public function test_throws_when_data_field_is_missing(): void
    {
        $data = $this->seminarData();
        unset($data['nama_peserta']);

        $this->expectException(RenderException::class);
        $this->expectExceptionMessage("Data 'nama_peserta'");

        $this->renderer->render('seminar', $data, withBackground: false);
    }

    public function test_terbit_mode_has_no_watermark(): void
    {
        $teks = $this->textOf($this->renderer->render(
            'seminar',
            $this->seminarData(),
            CertificateMode::Terbit,
            withBackground: false,
        ));

        $this->assertStringNotContainsString('PRATINJAU', $teks);
        $this->assertStringNotContainsString('DIBATALKAN', $teks);
    }

    public function test_pratinjau_mode_adds_watermark(): void
    {
        $teks = $this->textOf($this->renderer->render(
            'seminar',
            $this->seminarData(),
            CertificateMode::Pratinjau,
            withBackground: false,
        ));

        $this->assertStringContainsString('PRATINJAU', $teks);
    }

    public function test_dibatalkan_mode_adds_watermark(): void
    {
        $teks = $this->textOf($this->renderer->render(
            'seminar',
            $this->seminarData(),
            CertificateMode::Dibatalkan,
            withBackground: false,
        ));

        $this->assertStringContainsString('DIBATALKAN', $teks);
    }

    public function test_renders_bootcamp_paragraph_from_template(): void
    {
        $pdf = $this->renderer->render('bootcamp', [
            'nama_peserta' => 'Akrom Masani Iksanul Amri',
            'ujian' => 'Fullstack Web Development',
            'bootcamp' => 'X-Code Bootcamp #14',
            'tanggal_kota' => 'Yogyakarta, 24 Oktober 2026',
        ], withBackground: false);

        $teks = $this->textOf($pdf);

        $this->assertStringContainsString('SERTIFIKAT PENCAPAIAN', $teks);
        $this->assertStringContainsString('Akrom Masani Iksanul Amri', $teks);
        $this->assertStringContainsString('Ujian Fullstack Web Development', $teks);
        $this->assertStringContainsString('Peserta dinyatakan telah lulus', $teks);
        $this->assertStringContainsString('Cantika', $teks);
        $this->assertStringContainsString('Direktur Operasional', $teks);
    }

    /**
     * @return array<string, string>
     */
    private function seminarData(): array
    {
        return [
            'nama_peserta' => 'Arief Soekardi',
            'judul_cetak' => 'Koding Laravel: 20x Lebih Cepat dengan Hermes AI',
            'nomor_lengkap' => '35/001/003/Xcodetraining/X/2026',
            'tanggal_kota' => 'Yogyakarta, 24 Oktober 2026',
        ];
    }

    private function uncompressed(string $pdf): string
    {
        preg_match_all('/stream\r?\n(.*?)endstream/s', $pdf, $streams);

        $isi = '';

        foreach ($streams[1] as $stream) {
            $data = @gzuncompress($stream);

            if ($data !== false) {
                $isi .= $data;
            }
        }

        return $isi;
    }

    /**
     * Mengambil teks dari operator TJ pada stream yang tidak terkompresi.
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
        }

        return str_replace(['\\(', '\\)'], ['(', ')'], $teks);
    }
}
