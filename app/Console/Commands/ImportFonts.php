<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use TCPDF_FONTS;

/**
 * Mengubah berkas .ttf menjadi definisi font TCPDF di storage/app/fonts.
 *
 * Nama keluarga font mengikuti aturan TCPDF_FONTS::addTTFfont (huruf kecil,
 * hanya a-z0-9_, "bold" -> "b", "italic"/"oblique" -> "i"), sehingga
 * Tinos-Regular.ttf menjadi "tinos" dan Tinos-Bold.ttf menjadi "tinosb".
 * Nama itulah yang dipakai di config/layout.
 */
final class ImportFonts extends Command
{
    /**
     * Font OFL yang dipakai layout, diambil dari repositori Google Fonts.
     *
     * @var array<string, string>
     */
    private const DOWNLOADS = [
        'Tinos-Regular.ttf' => 'https://github.com/google/fonts/raw/main/ofl/tinos/Tinos-Regular.ttf',
        'Tinos-Bold.ttf' => 'https://github.com/google/fonts/raw/main/ofl/tinos/Tinos-Bold.ttf',
        'Tinos-Italic.ttf' => 'https://github.com/google/fonts/raw/main/ofl/tinos/Tinos-Italic.ttf',
        'Tinos-BoldItalic.ttf' => 'https://github.com/google/fonts/raw/main/ofl/tinos/Tinos-BoldItalic.ttf',
        'PinyonScript-Regular.ttf' => 'https://github.com/google/fonts/raw/main/ofl/pinyonscript/PinyonScript-Regular.ttf',
        'Poppins-Regular.ttf' => 'https://github.com/google/fonts/raw/main/ofl/poppins/Poppins-Regular.ttf',
    ];

    protected $signature = 'certificates:import-fonts
                            {--source= : Folder berisi berkas .ttf (bawaan assets/fonts)}
                            {--output= : Folder definisi font TCPDF (bawaan storage/app/fonts)}
                            {--download : Unduh font OFL yang dipakai layout sebelum impor}
                            {--force : Bangun ulang font yang sudah ada}';

    protected $description = 'Mengimpor font TTF sertifikat menjadi definisi font TCPDF';

    public function handle(): int
    {
        $source = (string) ($this->option('source') ?: base_path('assets/fonts'));
        $output = (string) ($this->option('output') ?: storage_path('app/fonts'));

        if ($this->option('download') && ! $this->download($source)) {
            return self::FAILURE;
        }

        if (! is_dir($source)) {
            $this->error("Folder font '{$source}' tidak ditemukan. Letakkan berkas .ttf di sana atau pakai --download.");

            return self::FAILURE;
        }

        $berkas = glob($source.DIRECTORY_SEPARATOR.'*.ttf') ?: [];

        if ($berkas === []) {
            $this->error("Tidak ada berkas .ttf di '{$source}'.");

            return self::FAILURE;
        }

        if (! $this->pastikanFolder($output)) {
            return self::FAILURE;
        }

        $gagal = 0;
        $dilewati = 0;

        foreach ($berkas as $ttf) {
            $this->line('Memproses '.basename($ttf).' ...');

            if (! $this->option('force') && $this->sudahAda($output, $ttf)) {
                $this->line('  dilewati, sudah ada (pakai --force untuk membangun ulang).');
                $dilewati++;

                continue;
            }

            $hasil = TCPDF_FONTS::addTTFfont(
                $ttf,
                'TrueTypeUnicode',
                '',
                32,
                rtrim($output, '/\\').DIRECTORY_SEPARATOR,
            );

            if ($hasil === false) {
                $this->error('  gagal diimpor.');
                $gagal++;

                continue;
            }

            $this->line("  keluarga font: {$hasil}");
        }

        $jumlah = count($berkas) - $gagal - $dilewati;

        if ($gagal > 0) {
            $this->error("{$gagal} berkas gagal diimpor.");

            return self::FAILURE;
        }

        $this->info("Selesai. {$jumlah} font baru, {$dilewati} dilewati, tersimpan di {$output}.");

        return self::SUCCESS;
    }

    private function sudahAda(string $output, string $ttf): bool
    {
        $nama = strtolower(pathinfo($ttf, PATHINFO_FILENAME));

        return is_file($output.DIRECTORY_SEPARATOR.$nama.'.php');
    }

    private function pastikanFolder(string $folder): bool
    {
        if (is_dir($folder)) {
            return true;
        }

        if (mkdir($folder, 0o775, true) || is_dir($folder)) {
            return true;
        }

        $this->error("Folder '{$folder}' tidak bisa dibuat.");

        return false;
    }

    private function download(string $folder): bool
    {
        if (! $this->pastikanFolder($folder)) {
            return false;
        }

        foreach (self::DOWNLOADS as $nama => $url) {
            $tujuan = $folder.DIRECTORY_SEPARATOR.$nama;

            if (is_file($tujuan)) {
                continue;
            }

            $this->line("Mengunduh {$nama} ...");

            $respons = Http::withOptions(['sink' => $tujuan])->timeout(60)->get($url);

            if (! $respons->successful() || ! is_file($tujuan)) {
                $this->error("  gagal mengunduh {$url}");

                return false;
            }
        }

        return true;
    }
}
