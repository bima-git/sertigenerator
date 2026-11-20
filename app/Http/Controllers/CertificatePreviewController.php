<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CertificateMode;
use App\Pdf\CertificateRenderer;
use App\Services\SampleCertificateData;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Halaman pemeriksa hasil render PDF di browser. Hanya memakai data contoh,
 * tidak mengakses basis data penerbitan.
 */
final class CertificatePreviewController extends Controller
{
    public function index(
        Request $request,
        SampleCertificateData $samples,
    ): View {
        $semua = $samples->samples($this->tanggalTerbit());
        [$jenis, $sampel, $mode, $guides] = $this->pilihan($request, $semua);

        return view('pratinjau', [
            'jenis' => $jenis,
            'jenisTersedia' => array_values(array_unique(array_column($semua, 'jenis'))),
            'sampel' => $sampel,
            'semuaSampel' => array_values(array_filter(
                $semua,
                fn (array $item): bool => $item['jenis'] === $jenis,
            )),
            'mode' => $mode,
            'modeTersedia' => CertificateMode::cases(),
            'guides' => $guides,
            'pdfUrl' => route('pratinjau.pdf', [
                'jenis' => $jenis,
                'sampel' => $sampel['berkas'],
                'mode' => $mode->value,
                'guides' => $guides ? 1 : 0,
            ]),
        ]);
    }

    public function pdf(
        Request $request,
        CertificateRenderer $renderer,
        SampleCertificateData $samples,
    ): Response {
        $semua = $samples->samples($this->tanggalTerbit());
        [$jenis, $sampel, $mode, $guides] = $this->pilihan($request, $semua);

        $isi = $renderer->render(
            $jenis,
            $sampel['data'],
            $mode,
            $this->denganLatar($request, $jenis),
            null,
            $guides,
        );

        return response($isi, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$sampel['berkas'].'"',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function tanggalTerbit(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-24 10:00:00');
    }

    /**
     * Menentukan jenis, sampel, mode, dan garis bantu dari query string.
     * Nilai asing untuk jenis dianggap 404 supaya tidak menutupi salah ketik.
     *
     * @param  list<array{jenis: string, berkas: string, data: array<string, string>}>  $semua
     * @return array{0: string, 1: array{jenis: string, berkas: string, data: array<string, string>}, 2: CertificateMode, 3: bool}
     */
    private function pilihan(Request $request, array $semua): array
    {
        $jenis = (string) ($request->query('jenis') ?: 'seminar');
        $jenisTersedia = array_values(array_unique(array_column($semua, 'jenis')));

        abort_unless(in_array($jenis, $jenisTersedia, true), 404, "Jenis '{$jenis}' tidak dikenal.");

        $kelompok = array_values(array_filter(
            $semua,
            fn (array $item): bool => $item['jenis'] === $jenis,
        ));

        $berkas = (string) $request->query('sampel', '');
        $sampel = $kelompok[0];

        foreach ($kelompok as $item) {
            if ($item['berkas'] === $berkas) {
                $sampel = $item;
            }
        }

        $mode = CertificateMode::tryFrom((string) $request->query('mode', '')) ?? CertificateMode::Terbit;

        return [$jenis, $sampel, $mode, $request->boolean('guides')];
    }

    private function denganLatar(Request $request, string $jenis): bool
    {
        if ($request->has('latar')) {
            return $request->boolean('latar');
        }

        return is_file(base_path((string) config("layout.{$jenis}.background")));
    }
}
