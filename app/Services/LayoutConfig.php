<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * Memuat dan memeriksa konfigurasi layout sertifikat (Design System 9.8).
 *
 * Semua koordinat dan teks tetap wajib berada di config/layout, bukan di kode.
 */
final class LayoutConfig
{
    public const TIPE = ['text', 'fit', 'qr', 'image', 'line'];

    public const PERATAAN = ['L', 'C'];

    /** Slot font yang boleh dipakai satu elemen. */
    public const FONT_ELEMEN = ['serif', 'serif_bold', 'script', 'sans'];

    /**
     * @param  array<string, mixed>  $layouts
     * @param  array<string, mixed>  $arsip  Layout beku dengan kunci "jenis-versi".
     */
    public function __construct(
        private readonly array $layouts,
        private readonly array $arsip = [],
    ) {
        if ($layouts === []) {
            throw new InvalidArgumentException('Konfigurasi layout kosong. Periksa folder config/layout.');
        }

        foreach ($layouts as $jenis => $layout) {
            $this->periksaLayout((string) $jenis, $layout);
        }

        foreach ($arsip as $kunci => $layout) {
            $this->periksaLayout('arsip '.$kunci, $layout);
        }
    }

    public static function fromConfig(): self
    {
        $layouts = (array) config('layout', []);

        // config/layout/arsip berisi salinan beku per versi, bukan jenis aktif.
        // Berkas dibaca langsung karena nama seperti "seminar-1.2.php" pecah bila
        // dimuat sebagai kunci config (titik dianggap pemisah).
        unset($layouts['arsip']);

        $arsip = [];

        foreach (glob(config_path('layout/arsip/*.php')) ?: [] as $berkas) {
            /** @var mixed $layout */
            $layout = require $berkas;
            $arsip[basename($berkas, '.php')] = $layout;
        }

        return new self($layouts, $arsip);
    }

    /**
     * @return array{
     *     versi: string,
     *     background: string,
     *     font: array{serif: string, serif_bold: string, fallback: string},
     *     page: array{size: string, orientation: string},
     *     elements: array<string, array<string, mixed>>
     * }
     */
    /**
     * Layout untuk satu jenis sertifikat.
     *
     * Bila versi diminta, salinan beku di config/layout/arsip dipakai lebih dulu
     * agar sertifikat lama tercetak dengan koordinat saat diterbitkan. Versi yang
     * belum punya arsip (sertifikat sebelum arsip dibuat) memakai layout aktif.
     */
    public function get(string $jenis, ?string $versi = null): array
    {
        if ($versi !== null && $versi !== '') {
            $arsip = $this->arsip[$jenis.'-'.$versi] ?? null;

            if (is_array($arsip)) {
                return $arsip;
            }
        }

        return $this->aktif($jenis);
    }

    /**
     * @return array{
     *     versi: string,
     *     background: string,
     *     font: array{serif: string, serif_bold: string, fallback: string},
     *     page: array{size: string, orientation: string},
     *     elements: array<string, array<string, mixed>>
     * }
     */
    private function aktif(string $jenis): array
    {
        if (! isset($this->layouts[$jenis])) {
            $tersedia = implode(', ', array_keys($this->layouts));

            throw new InvalidArgumentException("Layout '{$jenis}' tidak dikenal. Tersedia: {$tersedia}.");
        }

        /** @var array{versi: string, background: string, font: array{serif: string, serif_bold: string, fallback: string}, page: array{size: string, orientation: string}, elements: array<string, array<string, mixed>>} */
        return $this->layouts[$jenis];
    }

    /**
     * @return list<string>
     */
    public function jenis(): array
    {
        return array_keys($this->layouts);
    }

    private function periksaLayout(string $jenis, mixed $layout): void
    {
        if (! is_array($layout)) {
            throw new InvalidArgumentException("Layout '{$jenis}' harus berupa array.");
        }

        foreach (['versi', 'background'] as $kunci) {
            if (! isset($layout[$kunci]) || ! is_string($layout[$kunci]) || trim($layout[$kunci]) === '') {
                throw new InvalidArgumentException("Layout '{$jenis}' kehilangan kunci '{$kunci}'.");
            }
        }

        $font = $layout['font'] ?? null;

        if (! is_array($font)) {
            throw new InvalidArgumentException("Layout '{$jenis}' kehilangan blok 'font'.");
        }

        foreach (['serif', 'serif_bold'] as $kunci) {
            if (! isset($font[$kunci]) || ! is_string($font[$kunci]) || trim($font[$kunci]) === '') {
                throw new InvalidArgumentException("Layout '{$jenis}' kehilangan 'font.{$kunci}'.");
            }
        }

        if (isset($font['fallback']) && (! is_string($font['fallback']) || trim($font['fallback']) === '')) {
            throw new InvalidArgumentException("Layout '{$jenis}' punya 'font.fallback' yang tidak valid.");
        }

        foreach (['script', 'sans'] as $kunci) {
            if (isset($font[$kunci]) && (! is_string($font[$kunci]) || trim($font[$kunci]) === '')) {
                throw new InvalidArgumentException("Layout '{$jenis}' punya 'font.{$kunci}' yang tidak valid.");
            }
        }

        $page = $layout['page'] ?? null;

        if (! is_array($page)) {
            throw new InvalidArgumentException("Layout '{$jenis}' kehilangan blok 'page'.");
        }

        foreach (['size', 'orientation'] as $kunci) {
            if (! isset($page[$kunci]) || ! is_string($page[$kunci]) || trim($page[$kunci]) === '') {
                throw new InvalidArgumentException("Layout '{$jenis}' kehilangan 'page.{$kunci}'.");
            }
        }

        $elements = $layout['elements'] ?? null;

        if (! is_array($elements) || $elements === []) {
            throw new InvalidArgumentException("Layout '{$jenis}' harus punya minimal satu elemen.");
        }

        foreach ($elements as $nama => $element) {
            $this->periksaElemen($jenis, (string) $nama, $element);
        }
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function periksaGambar(string $awalan, array $element): void
    {
        if (! isset($element['path']) || ! is_string($element['path']) || trim($element['path']) === '') {
            throw new InvalidArgumentException("{$awalan} butuh 'path' berkas gambar.");
        }

        foreach (['w', 'h'] as $kunci) {
            if (! isset($element[$kunci]) || ! is_numeric($element[$kunci]) || (float) $element[$kunci] <= 0.0) {
                throw new InvalidArgumentException("{$awalan} butuh '{$kunci}' lebih besar dari 0.");
            }
        }

        if (isset($element['optional']) && ! is_bool($element['optional'])) {
            throw new InvalidArgumentException("{$awalan} punya 'optional' yang harus boolean.");
        }
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function periksaGaris(string $awalan, array $element): void
    {
        foreach (['w', 'tebal'] as $kunci) {
            if (! isset($element[$kunci]) || ! is_numeric($element[$kunci]) || (float) $element[$kunci] <= 0.0) {
                throw new InvalidArgumentException("{$awalan} butuh '{$kunci}' lebih besar dari 0.");
            }
        }

        if (! isset($element['color'])) {
            return;
        }

        $warna = $element['color'];

        if (! is_array($warna) || count($warna) !== 3 || array_filter($warna, 'is_int') !== $warna) {
            throw new InvalidArgumentException("{$awalan} punya 'color' yang harus tiga angka 0-255.");
        }

        foreach ($warna as $nilai) {
            if ($nilai < 0 || $nilai > 255) {
                throw new InvalidArgumentException("{$awalan} punya 'color' di luar rentang 0-255.");
            }
        }
    }

    private function periksaElemen(string $jenis, string $nama, mixed $element): void
    {
        $awalan = "Layout '{$jenis}' elemen '{$nama}'";

        if (! is_array($element)) {
            throw new InvalidArgumentException("{$awalan} harus berupa array.");
        }

        $tipe = $element['type'] ?? null;

        if (! is_string($tipe) || ! in_array($tipe, self::TIPE, true)) {
            throw new InvalidArgumentException(
                "{$awalan} punya type tidak dikenal. Pilihan: ".implode(', ', self::TIPE).'.',
            );
        }

        foreach (['x', 'y'] as $kunci) {
            if (! isset($element[$kunci]) || ! is_numeric($element[$kunci])) {
                throw new InvalidArgumentException("{$awalan} kehilangan koordinat '{$kunci}'.");
            }
        }

        if ($tipe === 'image') {
            $this->periksaGambar($awalan, $element);

            return;
        }

        if ($tipe === 'qr') {
            if (! isset($element['size']) || ! is_numeric($element['size']) || (float) $element['size'] <= 0.0) {
                throw new InvalidArgumentException("{$awalan} butuh 'size' lebih besar dari 0.");
            }

            return;
        }

        if ($tipe === 'line') {
            $this->periksaGaris($awalan, $element);

            return;
        }

        if (isset($element['font']) && (! is_string($element['font']) || ! in_array($element['font'], self::FONT_ELEMEN, true))) {
            throw new InvalidArgumentException(
                "{$awalan} punya font tidak dikenal. Pilihan: ".implode(', ', self::FONT_ELEMEN).'.',
            );
        }

        if (! isset($element['align']) || ! in_array($element['align'], self::PERATAAN, true)) {
            throw new InvalidArgumentException(
                "{$awalan} butuh 'align' berisi L atau C.",
            );
        }

        if (! isset($element['size']) || ! is_numeric($element['size']) || (float) $element['size'] <= 0.0) {
            throw new InvalidArgumentException("{$awalan} butuh 'size' lebih besar dari 0.");
        }

        $sumber = array_values(array_intersect(['text', 'field', 'template'], array_keys($element)));

        if (count($sumber) !== 1) {
            throw new InvalidArgumentException(
                "{$awalan} harus punya tepat satu sumber teks: 'text', 'field', atau 'template'.",
            );
        }

        $sumberNilai = $element[$sumber[0]] ?? null;

        if (! is_string($sumberNilai) || trim($sumberNilai) === '') {
            throw new InvalidArgumentException("{$awalan} punya '{$sumber[0]}' kosong.");
        }

        if ($tipe !== 'fit') {
            return;
        }

        foreach (['min_size', 'max_w'] as $kunci) {
            if (! isset($element[$kunci]) || ! is_numeric($element[$kunci]) || (float) $element[$kunci] <= 0.0) {
                throw new InvalidArgumentException("{$awalan} butuh '{$kunci}' lebih besar dari 0.");
            }
        }

        if ((float) $element['min_size'] > (float) $element['size']) {
            throw new InvalidArgumentException("{$awalan} punya 'min_size' lebih besar dari 'size'.");
        }

        if (isset($element['max_lines']) && (! is_int($element['max_lines']) || $element['max_lines'] < 1)) {
            throw new InvalidArgumentException("{$awalan} punya 'max_lines' yang harus bilangan bulat minimal 1.");
        }

        if (isset($element['line_h']) && (! is_numeric($element['line_h']) || (float) $element['line_h'] <= 0.0)) {
            throw new InvalidArgumentException("{$awalan} punya 'line_h' yang harus lebih besar dari 0.");
        }

        if (isset($element['balance']) && ! is_bool($element['balance'])) {
            throw new InvalidArgumentException("{$awalan} punya 'balance' yang harus boolean.");
        }
    }
}
