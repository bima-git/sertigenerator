<?php

declare(strict_types=1);

namespace App\Pdf;

use App\Enums\CertificateMode;
use App\Exceptions\RenderException;
use App\Services\LayoutConfig;
use App\Services\TcpdfTextMeasurer;
use App\Services\TextFitter;
use Illuminate\Support\Facades\Log;
use TCPDF;

/**
 * Merender sertifikat PDF dari config/layout dan data yang sudah diformat.
 *
 * Renderer tidak menyentuh basis data dan tidak memformat tanggal; pemanggil
 * mengirim data siap cetak (termasuk "tanggal_kota" dari DateId).
 */
final class CertificateRenderer
{
    public function __construct(
        private readonly LayoutConfig $layouts,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return string Isi berkas PDF.
     */
    public function render(
        string $jenis,
        array $data,
        CertificateMode $mode = CertificateMode::Terbit,
        bool $withBackground = true,
        ?string $backgroundPath = null,
        bool $withGuides = false,
        ?string $versiLayout = null,
        ?string $watermarkText = null,
    ): string {
        $layout = $this->layouts->get($jenis, $versiLayout);
        $brand = $this->brand($data);

        $pdf = $this->newDocument($layout, $jenis, $mode, (string) ($data['nama_peserta'] ?? ''));

        [$red, $green, $blue] = $this->textColor();
        $pdf->SetTextColor($red, $green, $blue);

        if ($withBackground) {
            $this->drawBackground($pdf, $backgroundPath ?? $layout['background']);
        }

        // Tanda air tile berada di belakang konten tetapi tetap di atas latar.
        $this->drawBrandWatermark($pdf);

        if ($withGuides) {
            $this->drawGuides($pdf, $layout);
            $pdf->SetTextColor($red, $green, $blue);
        }

        foreach ($layout['elements'] as $nama => $element) {
            $nama = (string) $nama;
            $element = $this->brandElement($element, $brand);

            if ($element['type'] === 'image') {
                $this->drawImage($pdf, $element);

                continue;
            }

            if ($element['type'] === 'qr') {
                $this->drawQr($pdf, $element, $data);

                continue;
            }

            if ($element['type'] === 'line') {
                $this->drawRule($pdf, $element);
                $pdf->SetTextColor($red, $green, $blue);

                continue;
            }

            $teks = $this->resolveText($nama, $element, $data);
            [$family, $style] = $this->fontElemen($layout, $element);

            if ($element['type'] === 'text') {
                $this->drawLines(
                    $pdf,
                    $element,
                    [$teks],
                    (float) $element['size'],
                    $family,
                    $style,
                );

                continue;
            }

            $fitter = new TextFitter(new TcpdfTextMeasurer($pdf, $family, $style));

            $hasil = $fitter->fit(
                $teks,
                (float) $element['max_w'],
                (float) $element['size'],
                (float) $element['min_size'],
                (int) ($element['max_lines'] ?? 1),
                (bool) ($element['balance'] ?? true),
            );

            $this->drawLines($pdf, $element, $hasil->lines, $hasil->size, $family, $style);
        }

        $this->drawWatermark($pdf, $mode, $watermarkText);

        return (string) $pdf->Output('sertifikat.pdf', 'S');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return string Path berkas yang ditulis.
     */
    public function renderToFile(
        string $jenis,
        array $data,
        string $path,
        CertificateMode $mode = CertificateMode::Terbit,
        bool $withBackground = true,
        ?string $backgroundPath = null,
        bool $withGuides = false,
    ): string {
        $folder = dirname($path);

        if (! is_dir($folder) && ! mkdir($folder, 0o775, true) && ! is_dir($folder)) {
            throw new RenderException("Folder '{$folder}' tidak bisa dibuat.");
        }

        $isi = $this->render($jenis, $data, $mode, $withBackground, $backgroundPath, $withGuides);

        if (file_put_contents($path, $isi) === false) {
            throw new RenderException("Berkas '{$path}' tidak bisa ditulis.");
        }

        return $path;
    }

    /**
     * @param  array{page: array{size: string, orientation: string}}  $layout
     */
    private function newDocument(
        array $layout,
        string $jenis,
        CertificateMode $mode,
        string $namaPeserta,
    ): TCPDF {
        $pdf = new CertificateDocument(
            $layout['page']['orientation'],
            'mm',
            $layout['page']['size'],
            true,
            'UTF-8',
            false,
        );

        $pdf->SetCreator('Generator Sertifikat XCODE');
        $pdf->SetAuthor('XCODE Training');
        // Judul memuat nama peserta agar berkas mudah dikenali dari metadata (PRD PD-12).
        $namaPeserta = trim($namaPeserta);
        $judul = "Sertifikat {$jenis}".($namaPeserta === '' ? '' : " - {$namaPeserta}");
        $pdf->SetTitle("{$judul} ({$mode->label()})");
        // Metadata tidak dikompres, sehingga status dokumen tetap terbaca mesin
        // tanpa perlu membongkar stream zlib (dipakai pengecekan otomatis).
        $pdf->SetSubject('Status dokumen: '.strtoupper($mode->value));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetCellPadding(0);
        $pdf->AddPage();
        $this->registerFonts($pdf, $layout);

        return $pdf;
    }

    /**
     * Mendaftarkan font hasil certificates:import-fonts yang tersimpan di luar vendor.
     *
     * @param  array<string, mixed>  $layout
     */
    private function registerFonts(TCPDF $pdf, array $layout): void
    {
        /** @var array<string, string> $font */
        $font = is_array($layout['font'] ?? null) ? $layout['font'] : [];

        foreach (['serif', 'serif_bold', 'script', 'sans', 'fallback'] as $slot) {
            $family = $font[$slot] ?? null;

            if (! is_string($family)) {
                continue;
            }

            $path = $this->fontPath($family);

            if ($path !== null) {
                $pdf->AddFont($family, '', $path);
            }
        }
    }

    /**
     * Gambar garis bantu 10 mm dan penanda koordinat setiap elemen.
     *
     * @param  array<string, mixed>  $layout
     */
    /**
     * Garis dekoratif (mis. garis bawah nama) dengan tebal dalam mm.
     *
     * @param  array<string, mixed>  $element
     */
    private function drawRule(TCPDF $pdf, array $element): void
    {
        /** @var array{0: int, 1: int, 2: int} $warna */
        $warna = $element['color'] ?? [0, 0, 0];
        $tebal = (float) $element['tebal'];
        $x = (float) $element['x'];
        $y = (float) $element['y'];

        $pdf->SetDrawColor(...$warna);
        $pdf->SetLineWidth($tebal);
        $pdf->Line($x, $y, $x + (float) $element['w'], $y);
        $pdf->SetLineWidth(0.2);
    }

    private function drawGuides(TCPDF $pdf, array $layout): void
    {
        $lebar = $pdf->getPageWidth();
        $tinggi = $pdf->getPageHeight();

        $pdf->SetLineStyle(['width' => 0.1, 'color' => [200, 60, 60]]);

        for ($x = 0.0; $x <= $lebar; $x += 10.0) {
            $pdf->Line($x, 0, $x, $tinggi);
        }

        for ($y = 0.0; $y <= $tinggi; $y += 10.0) {
            $pdf->Line(0, $y, $lebar, $y);
        }

        $pdf->SetLineStyle(['width' => 0.3, 'color' => [150, 20, 20]]);

        for ($x = 0.0; $x <= $lebar; $x += 50.0) {
            $pdf->Line($x, 0, $x, $tinggi);
        }

        for ($y = 0.0; $y <= $tinggi; $y += 50.0) {
            $pdf->Line(0, $y, $lebar, $y);
        }

        $pdf->SetTextColor(150, 20, 20);
        $pdf->SetFont('helvetica', '', 4);
        $pdf->Text(2, 1, 'GARIS BANTU 10 mm - koordinat dalam mm dari sudut kiri atas', calign: 'T', valign: 'T');

        foreach ($layout['elements'] as $nama => $element) {
            $nama = (string) $nama;
            $x = (float) $element['x'];
            $y = (float) $element['y'];

            $pdf->SetLineStyle(['width' => 0.3, 'color' => [20, 90, 200]]);
            $pdf->Line($x - 1.5, $y, $x + 1.5, $y);
            $pdf->Line($x, $y - 1.5, $x, $y + 1.5);

            $pdf->Text($x + 1.8, $y - 2.2, sprintf('%s %s, %s', $nama, $this->angka($x), $this->angka($y)), calign: 'T', valign: 'T');

            if ($element['type'] !== 'image') {
                continue;
            }

            $pdf->SetLineStyle(['width' => 0.3, 'color' => [20, 140, 60]]);
            $pdf->Rect($x, $y, (float) $element['w'], (float) $element['h']);
        }
    }

    private function angka(float $nilai): string
    {
        return rtrim(rtrim(number_format($nilai, 2, ',', ''), '0'), ',');
    }

    private function drawBackground(TCPDF $pdf, string $background): void
    {
        $path = $this->absolutePath($background);

        if (! is_file($path)) {
            throw new RenderException(
                "Gambar latar '{$background}' tidak ditemukan. Letakkan berkasnya atau render tanpa latar.",
            );
        }

        $pdf->Image(
            $path,
            0,
            0,
            $pdf->getPageWidth(),
            $pdf->getPageHeight(),
            '',
            '',
            '',
            false,
            300,
            '',
            false,
            false,
            0,
            false,
            false,
            false,
        );
    }

    /**
     * @param  array<string, mixed>  $element
     */
    private function drawImage(TCPDF $pdf, array $element): void
    {
        $path = $this->absolutePath((string) $element['path']);

        if (! is_file($path)) {
            if (($element['optional'] ?? false) === true) {
                Log::warning("Gambar '{$element['path']}' belum tersedia; elemen dilewati.");

                return;
            }

            throw new RenderException("Gambar '{$element['path']}' tidak ditemukan.");
        }

        $pdf->Image(
            $path,
            (float) $element['x'],
            (float) $element['y'],
            (float) $element['w'],
            (float) $element['h'],
            '',
            '',
            '',
            false,
            300,
            '',
            false,
            false,
            0,
            false,
            false,
            false,
        );
    }

    /**
     * @param  array<string, mixed>  $element
     * @param  list<string>  $lines
     */
    private function drawLines(
        TCPDF $pdf,
        array $element,
        array $lines,
        float $size,
        string $family,
        string $style,
    ): void {
        $lineHeight = (float) ($element['line_h'] ?? round($size * 0.4, 2));
        $jumlah = count($lines);

        foreach ($lines as $index => $baris) {
            // Baris terakhir tetap di garis dasar, baris sebelumnya naik sebesar line_h.
            $baseline = (float) $element['y'] - ($jumlah - 1 - $index) * $lineHeight;

            $this->drawLine(
                $pdf,
                (float) $element['x'],
                $baseline,
                $baris,
                (string) $element['align'],
                $size,
                $family,
                $style,
            );
        }
    }

    private function drawLine(
        TCPDF $pdf,
        float $x,
        float $baseline,
        string $teks,
        string $align,
        float $size,
        string $family,
        string $style,
    ): void {
        $pdf->SetFont($family, $style, $size);

        $lebar = (float) $pdf->GetStringWidth($teks, $family, $style, $size);
        $kiri = $align === 'C' ? $x - $lebar / 2 : $x;
        $ascent = (float) $pdf->getFontAscent($family, $style, $size);

        $pdf->Text($kiri, $baseline - $ascent, $teks, calign: 'T', valign: 'T');
    }

    /**
     * @param  array<string, mixed>  $element
     * @param  array<string, mixed>  $data
     */
    private function drawQr(TCPDF $pdf, array $element, array $data): void
    {
        // pakai_qr hanya dikirim oleh pemanggil yang memang mengatur QR (mis. PdfRenderer).
        if (array_key_exists('pakai_qr', $data) && $data['pakai_qr'] === false) {
            return;
        }

        $pdf->write2DBarcode(
            $this->qrContent(),
            'QRCODE,M',
            (float) $element['x'],
            (float) $element['y'],
            (float) $element['size'],
            (float) $element['size'],
            ['border' => 0, 'padding' => 0],
            'N',
            false,
        );
    }

    private function qrContent(): string
    {
        return (string) config('certificate.qr.url');
    }

    /**
     * Pita dan domain mengikuti penerbit. Hanya elemen bertanda 'brand' yang ditimpa,
     * sehingga layout arsip tetap memakai logo dan domain saat sertifikat diterbitkan.
     *
     * @param  array<string, mixed>  $data
     * @return array{logo: string, domain: string}
     */
    private function brand(array $data): array
    {
        $default = (array) config('certificate.branding.default', []);
        $bawaan = [
            'logo' => (string) ($default['logo'] ?? 'assets/templates/logo-xcode.png'),
            'domain' => (string) ($default['domain'] ?? 'xcode.or.id'),
        ];

        if (! $this->isSiberExpert($data)) {
            return $bawaan;
        }

        $siber = (array) config('certificate.branding.siberexpert', []);

        return [
            'logo' => (string) ($siber['logo'] ?? $bawaan['logo']),
            'domain' => (string) ($siber['domain'] ?? $bawaan['domain']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function isSiberExpert(array $data): bool
    {
        $penerbit = strtolower(trim((string) ($data['penerbit'] ?? '')));

        if ($penerbit !== '') {
            return str_contains($penerbit, 'siber');
        }

        // Sertifikat lama tidak menyimpan penerbit; nomor resmi memuatnya di segmen keempat.
        $segmen = explode('/', (string) ($data['nomor_lengkap'] ?? ''));

        return str_contains(strtolower($segmen[3] ?? ''), 'siber');
    }

    /**
     * @param  array<string, mixed>  $element
     * @param  array{logo: string, domain: string}  $brand
     * @return array<string, mixed>
     */
    private function brandElement(array $element, array $brand): array
    {
        $slot = $element['brand'] ?? null;

        if ($slot === 'logo') {
            $element['path'] = $brand['logo'];
        } elseif ($slot === 'domain') {
            $element['text'] = $brand['domain'];
        }

        return $element;
    }

    /**
     * Menyebar teks "XCODE" miring sebagai tanda air tile di seluruh halaman.
     */
    private function drawBrandWatermark(TCPDF $pdf): void
    {
        /** @var array{text?: string, color?: array{0: int, 1: int, 2: int}, alpha?: float, size?: float, angle?: float, gap_x?: float, gap_y?: float}|null $pengaturan */
        $pengaturan = config('certificate.brand_watermark');

        if (! is_array($pengaturan)) {
            return;
        }

        $teks = trim((string) ($pengaturan['text'] ?? ''));
        $ukuran = (float) ($pengaturan['size'] ?? 30);
        $jarakX = (float) ($pengaturan['gap_x'] ?? 58);
        $jarakY = (float) ($pengaturan['gap_y'] ?? 40);

        if ($teks === '' || $ukuran <= 0 || $jarakX <= 0 || $jarakY <= 0) {
            return;
        }

        $warna = $pengaturan['color'] ?? [120, 120, 120];
        $alpha = (float) ($pengaturan['alpha'] ?? 0.06);
        $sudut = (float) ($pengaturan['angle'] ?? 30);
        $family = 'helvetica';
        $style = 'B';

        $tengahX = $pdf->getPageWidth() / 2;
        $tengahY = $pdf->getPageHeight() / 2;
        // Jari-jari halaman setelah diputar, agar tidak ada sudut yang kosong.
        $jari = sqrt($pdf->getPageWidth() ** 2 + $pdf->getPageHeight() ** 2) / 2 + $jarakX;

        $pdf->setAlpha($alpha, 'Normal', $alpha);
        $pdf->SetTextColor(...$warna);
        $pdf->SetFont($family, $style, $ukuran);

        $lebar = (float) $pdf->GetStringWidth($teks, $family, $style, $ukuran);

        $pdf->StartTransform();
        $pdf->Rotate($sudut, $tengahX, $tengahY);

        $baris = 0;

        for ($y = $tengahY - $jari; $y <= $tengahY + $jari; $y += $jarakY) {
            $geser = $baris % 2 === 0 ? 0.0 : $jarakX / 2;

            for ($x = $tengahX - $jari - $geser; $x <= $tengahX + $jari; $x += $jarakX) {
                $pdf->Text($x - $lebar / 2, $y, $teks, calign: 'T', valign: 'T');
            }

            $baris++;
        }

        $pdf->StopTransform();
        $pdf->setAlpha(1);
    }

    private function drawWatermark(TCPDF $pdf, CertificateMode $mode, ?string $teksBebas): void
    {
        /** @var array{text: string, color: array{0: int, 1: int, 2: int}, alpha: float, size: float, angle: float}|null $pengaturan */
        $pengaturan = config("certificate.watermark.{$mode->value}");
        $teks = trim((string) $teksBebas);

        if ($teks === '') {
            // Tanpa teks bebas, mode terbit memang tidak diberi tanda air.
            if ($mode === CertificateMode::Terbit) {
                return;
            }

            $teks = (string) ($pengaturan['text'] ?? '');
        } elseif (! is_array($pengaturan)) {
            // Teks bebas pada mode terbit memakai gaya netral milik pratinjau.
            $pengaturan = config('certificate.watermark.pratinjau');
        }

        if (! is_array($pengaturan) || $teks === '') {
            return;
        }

        // Font inti dipakai agar teks tanda air tetap terbaca sebagai teks di
        // stream PDF; font impor disubset sehingga hanya tersimpan kode glyph.
        $family = 'times';
        $style = 'B';

        $tengahX = $pdf->getPageWidth() / 2;
        $tengahY = $pdf->getPageHeight() / 2;
        $ukuran = (float) $pengaturan['size'];

        $pdf->setAlpha((float) $pengaturan['alpha'], 'Normal', (float) $pengaturan['alpha']);
        $pdf->SetTextColor(...$pengaturan['color']);
        $pdf->SetFont($family, $style, $ukuran);

        $lebar = (float) $pdf->GetStringWidth($teks, $family, $style, $ukuran);
        $ascent = (float) $pdf->getFontAscent($family, $style, $ukuran);

        $pdf->StartTransform();
        $pdf->Rotate((float) $pengaturan['angle'], $tengahX, $tengahY);
        $pdf->Text($tengahX - $lebar / 2, $tengahY - $ascent / 2, $teks, calign: 'T', valign: 'T');
        $pdf->StopTransform();

        $pdf->setAlpha(1);
    }

    /**
     * @param  array<string, mixed>  $element
     * @param  array<string, mixed>  $data
     */
    private function resolveText(string $nama, array $element, array $data): string
    {
        if (isset($element['text'])) {
            return (string) $element['text'];
        }

        if (isset($element['field'])) {
            return $this->nilai($data, (string) $element['field'], $nama);
        }

        $template = (string) ($element['template'] ?? '');

        return (string) preg_replace_callback(
            '/\{(\w+)\}/',
            fn (array $cocok): string => $this->nilai($data, $cocok[1], $nama),
            $template,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function nilai(array $data, string $kunci, string $nama): string
    {
        if (! array_key_exists($kunci, $data) || $data[$kunci] === null || $data[$kunci] === '') {
            throw new RenderException(
                "Data '{$kunci}' untuk elemen '{$nama}' belum tersedia. Lengkapi data sebelum mencetak.",
            );
        }

        return (string) $data[$kunci];
    }

    /**
     * Menentukan font satu elemen sesuai slot 'font' di config.
     *
     * @param  array<string, mixed>  $layout
     * @param  array<string, mixed>  $element
     * @return array{0: string, 1: string}
     */
    private function fontElemen(array $layout, array $element): array
    {
        /** @var array{font: array{serif: string, serif_bold: string, script?: string, sans?: string, fallback?: string}} $layout */
        return match ($element['font'] ?? 'serif') {
            'serif_bold' => $this->resolveBoldFont($layout),
            'script' => $this->resolveSpecialFont($layout, 'script', 'I'),
            'sans' => $this->resolveSpecialFont($layout, 'sans', ''),
            default => $this->resolveRegularFont($layout),
        };
    }

    /**
     * Memakai font khusus bila berkasnya ada; jika tidak, kembali ke serif.
     *
     * @param  array{font: array{serif: string, serif_bold: string, script?: string, sans?: string, fallback?: string}}  $layout
     * @return array{0: string, 1: string}
     */
    private function resolveSpecialFont(array $layout, string $slot, string $gayaFallback): array
    {
        $font = $layout['font'][$slot] ?? null;

        if (is_string($font) && $this->fontTersedia($font)) {
            return [$font, ''];
        }

        // Gaya italic hanya aman pada font inti; font impor belum tentu punya variannya.
        $cadangan = $layout['font']['fallback'] ?? 'times';

        Log::warning(
            $font === null
                ? "Layout tidak mengatur 'font.{$slot}'; sertifikat memakai '{$cadangan}'."
                : "Font '{$font}' belum tersedia; sertifikat memakai '{$cadangan}'.",
        );

        return [$cadangan, $gayaFallback];
    }

    /**
     * @param  array{font: array{serif: string, serif_bold: string, fallback?: string}}  $layout
     * @return array{0: string, 1: string}
     */
    private function resolveRegularFont(array $layout): array
    {
        $fallback = $layout['font']['fallback'] ?? 'times';

        if ($this->fontTersedia($layout['font']['serif'])) {
            return [$layout['font']['serif'], ''];
        }

        Log::warning("Font '{$layout['font']['serif']}' belum tersedia; sertifikat memakai '{$fallback}'.");

        return [$fallback, ''];
    }

    /**
     * @param  array{font: array{serif: string, serif_bold: string, fallback?: string}}  $layout
     * @return array{0: string, 1: string}
     */
    private function resolveBoldFont(array $layout): array
    {
        $fallback = $layout['font']['fallback'] ?? 'times';

        if ($this->fontTersedia($layout['font']['serif_bold'])) {
            return [$layout['font']['serif_bold'], ''];
        }

        return [$fallback, 'B'];
    }

    /**
     * Folder definisi font hasil impor, di luar vendor agar tidak hilang saat composer install.
     */
    private function fontDir(): string
    {
        return storage_path('app/fonts');
    }

    private function fontPath(string $family): ?string
    {
        $path = $this->fontDir().DIRECTORY_SEPARATOR.$family.'.php';

        return is_file($path) ? $path : null;
    }

    private function fontTersedia(string $family): bool
    {
        return $this->fontPath($family) !== null
            || is_file(rtrim(K_PATH_FONTS, '/\\').'/'.$family.'.php');
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function textColor(): array
    {
        /** @var array{0: int, 1: int, 2: int} $warna */
        $warna = config('certificate.text_color', [17, 17, 17]);

        return $warna;
    }

    private function absolutePath(string $path): string
    {
        if (preg_match('/^([A-Za-z]:[\\\\\\/]|\\/)/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }
}
