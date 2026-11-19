<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CertificateMode;
use App\Exceptions\RenderException;
use App\Services\DateId;
use App\Services\PdfRenderer;
use App\Services\SampleCertificateData;
use DateTimeImmutable;
use Illuminate\Console\Command;
use InvalidArgumentException;

final class MakeSampleCertificates extends Command
{
    protected $signature = 'certificates:make-samples
                            {--output= : Folder tujuan, bawaan storage/app/samples}
                            {--mode=terbit : Mode PDF: terbit, pratinjau, atau dibatalkan}
                            {--jenis= : Hanya cetak satu jenis layout: seminar atau bootcamp}
                            {--guides : Tambahkan garis bantu 10 mm dan penanda koordinat}';

    protected $description = 'Membuat PDF sampel untuk kalibrasi layout sertifikat';

    public function handle(PdfRenderer $renderer, DateId $dates, SampleCertificateData $samples): int
    {
        try {
            $mode = CertificateMode::fromInput((string) $this->option('mode'));
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $output = (string) ($this->option('output') ?: storage_path('app/samples'));
        $jenis = (string) ($this->option('jenis') ?: '');
        $tersedia = array_keys((array) config('layout', []));

        if ($jenis !== '' && ! in_array($jenis, $tersedia, true)) {
            $this->error("Jenis '{$jenis}' tidak dikenal. Pilihan: ".implode(', ', $tersedia).'.');

            return self::FAILURE;
        }

        $tanggal = $dates->formatWithCity('Yogyakarta', new DateTimeImmutable('2026-10-24 10:00:00'));

        $background = base_path((string) config('layout.seminar.background'));
        $withBackground = is_file($background);

        if (! $withBackground) {
            $this->warn('Gambar latar belum tersedia. PDF dibuat tanpa latar; koordinat teks tetap bisa diperiksa.');
        }

        $samples = $samples->samples($tanggal);

        if ($jenis !== '') {
            $samples = array_values(array_filter(
                $samples,
                fn (array $sampel): bool => $sampel['jenis'] === $jenis,
            ));
        }

        foreach ($samples as $sampel) {
            $tujuan = $output.DIRECTORY_SEPARATOR.$sampel['berkas'];

            try {
                $renderer->renderToFile(
                    $sampel['jenis'],
                    $sampel['data'],
                    $tujuan,
                    $mode,
                    $withBackground,
                    null,
                    (bool) $this->option('guides'),
                );
            } catch (RenderException $exception) {
                $this->error("{$sampel['berkas']}: {$exception->getMessage()}");

                return self::FAILURE;
            }

            $this->line("Dibuat: {$tujuan}");
        }

        $this->info(count($samples)." sampel dibuat di {$output} (mode {$mode->label()}).");

        return self::SUCCESS;
    }
}
