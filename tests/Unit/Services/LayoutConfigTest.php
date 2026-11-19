<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\LayoutConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LayoutConfigTest extends TestCase
{
    public function test_accepts_valid_layout(): void
    {
        $config = new LayoutConfig(['seminar' => $this->layout()]);

        $layout = $config->get('seminar');

        $this->assertSame('1.0', $layout['versi']);
        $this->assertSame(['seminar'], $config->jenis());
        $this->assertArrayHasKey('nama', $layout['elements']);
    }

    public function test_rejects_empty_configuration(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LayoutConfig([]);
    }

    public function test_rejects_unknown_jenis(): void
    {
        $config = new LayoutConfig(['seminar' => $this->layout()]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Layout 'webinar' tidak dikenal");

        $config->get('webinar');
    }

    public function test_rejects_layout_without_version(): void
    {
        $layout = $this->layout();
        unset($layout['versi']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("kehilangan kunci 'versi'");

        new LayoutConfig(['seminar' => $layout]);
    }

    public function test_rejects_layout_without_font(): void
    {
        $layout = $this->layout();
        unset($layout['font']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("kehilangan blok 'font'");

        new LayoutConfig(['seminar' => $layout]);
    }

    public function test_rejects_layout_without_elements(): void
    {
        $layout = $this->layout();
        $layout['elements'] = [];

        $this->expectException(InvalidArgumentException::class);

        new LayoutConfig(['seminar' => $layout]);
    }

    public function test_rejects_fit_element_without_max_width(): void
    {
        $layout = $this->layout();
        unset($layout['elements']['nama']['max_w']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("butuh 'max_w'");

        new LayoutConfig(['seminar' => $layout]);
    }

    public function test_rejects_fit_element_without_min_size(): void
    {
        $layout = $this->layout();
        unset($layout['elements']['nama']['min_size']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("butuh 'min_size'");

        new LayoutConfig(['seminar' => $layout]);
    }

    public function test_rejects_min_size_larger_than_size(): void
    {
        $layout = $this->layout();
        $layout['elements']['nama']['min_size'] = 30;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'min_size' lebih besar dari 'size'");

        new LayoutConfig(['seminar' => $layout]);
    }

    public function test_rejects_element_without_text_source(): void
    {
        $layout = $this->layout();
        unset($layout['elements']['nama']['field']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tepat satu sumber teks');

        new LayoutConfig(['seminar' => $layout]);
    }

    public function test_rejects_element_with_two_text_sources(): void
    {
        $layout = $this->layout();
        $layout['elements']['nama']['text'] = 'Nama';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tepat satu sumber teks');

        new LayoutConfig(['seminar' => $layout]);
    }

    public function test_rejects_unknown_element_type(): void
    {
        $layout = $this->layout();
        $layout['elements']['nama']['type'] = 'gambar';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('type tidak dikenal');

        new LayoutConfig(['seminar' => $layout]);
    }

    public function test_rejects_invalid_alignment(): void
    {
        $layout = $this->layout();
        $layout['elements']['nama']['align'] = 'X';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("butuh 'align'");

        new LayoutConfig(['seminar' => $layout]);
    }

    public function test_rejects_zero_max_lines(): void
    {
        $layout = $this->layout();
        $layout['elements']['nama']['max_lines'] = 0;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'max_lines'");

        new LayoutConfig(['seminar' => $layout]);
    }

    public function test_rejects_qr_element_without_size(): void
    {
        $layout = $this->layout();
        $layout['elements']['qr'] = ['type' => 'qr', 'x' => 240.3, 'y' => 87.0];

        $this->expectException(InvalidArgumentException::class);

        new LayoutConfig(['seminar' => $layout]);
    }

    public function test_rejects_image_element_without_path(): void
    {
        $layout = $this->layout();
        $layout['elements']['tanda_tangan'] = ['type' => 'image', 'x' => 234.12, 'y' => 152.99, 'w' => 50.55, 'h' => 38.38];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("butuh 'path'");

        new LayoutConfig(['seminar' => $layout]);
    }

    public function test_rejects_image_element_without_size(): void
    {
        $layout = $this->layout();
        $layout['elements']['logo'] = ['type' => 'image', 'path' => 'assets/templates/logo.png', 'x' => 240.71, 'y' => 3.56, 'w' => 37.32];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("butuh 'h'");

        new LayoutConfig(['seminar' => $layout]);
    }

    public function test_rejects_unknown_element_font(): void
    {
        $layout = $this->layout();
        $layout['elements']['nama']['font'] = 'comic';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('font tidak dikenal');

        new LayoutConfig(['seminar' => $layout]);
    }

    public function test_shipped_seminar_layout_is_valid(): void
    {
        $layout = $this->shipped('seminar');

        $config = new LayoutConfig(['seminar' => $layout]);

        $this->assertSame('1.2', $config->get('seminar')['versi']);
        $this->assertArrayHasKey('script_sertifikat', $config->get('seminar')['elements']);
        $this->assertArrayHasKey('penanda_tangan', $config->get('seminar')['elements']);
    }

    public function test_shipped_seminar_layout_has_adjustable_qr(): void
    {
        $qr = $this->shipped('seminar')['elements']['qr'] ?? null;

        $this->assertIsArray($qr);
        $this->assertSame('qr', $qr['type']);
        $this->assertSame(36.5, $qr['size']);
    }

    public function test_shipped_layouts_have_name_and_signature_rules(): void
    {
        foreach (['seminar', 'bootcamp'] as $jenis) {
            $elements = $this->shipped($jenis)['elements'];

            $this->assertSame('line', $elements['garis_nama']['type']);
            $this->assertSame('line', $elements['garis_tanda_tangan']['type']);
        }
    }

    public function test_rejects_line_without_thickness(): void
    {
        $layout = $this->layout();
        $layout['elements']['garis'] = ['type' => 'line', 'x' => 10, 'y' => 20, 'w' => 100];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("butuh 'tebal'");

        new LayoutConfig(['seminar' => $layout]);
    }

    public function test_shipped_bootcamp_layout_is_valid(): void
    {
        $layout = $this->shipped('bootcamp');

        $config = new LayoutConfig(['bootcamp' => $layout]);

        $this->assertSame('1.3', $config->get('bootcamp')['versi']);
    }

    public function test_bootcamp_has_no_number_or_qr_element(): void
    {
        $elements = $this->shipped('bootcamp')['elements'];

        $this->assertArrayNotHasKey('nomor', $elements);
        $this->assertArrayNotHasKey('qr', $elements);
    }

    /**
     * @return array<string, mixed>
     */
    private function layout(): array
    {
        return [
            'versi' => '1.0',
            'background' => 'assets/templates/background.png',
            'font' => ['serif' => 'tinos', 'serif_bold' => 'tinosb', 'fallback' => 'times'],
            'page' => ['size' => 'A4', 'orientation' => 'L'],
            'elements' => [
                'nama' => [
                    'type' => 'fit',
                    'field' => 'nama_peserta',
                    'x' => 120,
                    'y' => 151.7,
                    'align' => 'C',
                    'size' => 28,
                    'min_size' => 16,
                    'max_w' => 148,
                    'max_lines' => 2,
                    'line_h' => 8,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function shipped(string $jenis): array
    {
        $path = dirname(__DIR__, 3)."/config/layout/{$jenis}.php";

        $this->assertFileExists($path);

        /** @var array<string, mixed> $layout */
        $layout = require $path;

        return $layout;
    }
}
