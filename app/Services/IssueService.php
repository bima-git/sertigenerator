<?php

namespace App\Services;

use App\Exceptions\DuplicateNumberException;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class IssueService
{
    public function __construct(
        private readonly NumberAllocator $allocator,
        private readonly PdfRenderer $pdfRenderer,
        private readonly AuditLogger $auditLogger
    ) {}

    /**
     * Menjalankan alur penerbitan sertifikat secara atomik di dalam database transaction.
     *
     * @param array<string, mixed> $data Data terverifikasi dari IssueRequest
     * @param User|int $user Pengguna yang melakukan penerbitan
     * @return array{certificate: Certificate, nomor: string|null, filename: string, pdf: string}
     *
     * @throws DuplicateNumberException
     * @throws InvalidArgumentException
     * @throws \Throwable
     */
    public function issue(array $data, User|int $user): array
    {
        $userId = $user instanceof User ? $user->id : (int) $user;

        return DB::transaction(function () use ($data, $userId) {
            // 1. Kunci baris kegiatan untuk mencegah race condition konkurensi nomor
            /** @var Event $event */
            $event = Event::query()
                ->whereKey($data['event_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $event->aktif) {
                throw new InvalidArgumentException("Kegiatan [{$event->judul}] sudah tidak aktif.");
            }

            if ($event->jenis !== $data['jenis']) {
                throw new InvalidArgumentException("Jenis kegiatan tidak sesuai dengan data formulir.");
            }

            $date = Carbon::parse($data['tanggal_terbit'], 'Asia/Jakarta');
            $tahun = (int) $date->format('Y');

            $number = null;
            $seri = null;
            $urut = null;

            // 2. Alokasikan nomor jika kegiatan bernomor
            if ($event->bernomor) {
                $seri = isset($data['seri']) && (int) $data['seri'] > 0 ? (int) $data['seri'] : 1;
                $urut = isset($data['urut']) && (int) $data['urut'] > 0
                    ? (int) $data['urut']
                    : $this->allocator->next($event->id, $seri, $tahun);

                // Pastikan nomor urut masih bebas (khususnya jika diinput manual oleh operator)
                if (! $this->allocator->isAvailable($event->id, $seri, $urut, $tahun)) {
                    throw new DuplicateNumberException(
                        "Nomor urut {$urut} untuk kegiatan [{$event->judul}] seri {$seri} tahun {$tahun} sudah terpakai."
                    );
                }

                $number = NumberGenerator::build(
                    $event->kode,
                    $seri,
                    $urut,
                    $event->penerbit,
                    $date
                );
            }

            // 3. Susun salinan data cetak (JSON) untuk keterulangan unduh ulang di masa depan
            $dataCetak = $this->buildDataCetak($event, $data);

            // 4. Simpan baris sertifikat
            $versiLayout = (string) config("layout.{$data['jenis']}.versi", '1.0');
            $certificate = Certificate::create([
                'event_id'       => $event->id,
                'jenis'          => $data['jenis'],
                'seri'           => $seri,
                'urut'           => $urut,
                'tahun'          => $tahun,
                'nomor_lengkap'  => $number,
                'nama_peserta'   => $data['nama_peserta'],
                'tanggal_terbit' => $data['tanggal_terbit'],
                'data_cetak'     => $dataCetak,
                'versi_layout'   => $versiLayout,
                'status'         => 'terbit',
                'dibuat_oleh'    => $userId,
            ]);

            // 5. Render PDF di memori SEBELUM commit!
            // Bila render gagal (exception), DB::transaction otomatis rollback seluruh data!
            $pdf = $this->pdfRenderer->render($certificate, 'terbit');

            // 6. Catat jejak audit
            $this->auditLogger->log($userId, 'terbit', $certificate->id, [
                'nomor'  => $number,
                'nama'   => $certificate->nama_peserta,
                'event'  => $event->judul,
            ]);

            $filename = $this->generateSafeFilename($certificate);

            return [
                'certificate' => $certificate,
                'nomor'       => $number,
                'filename'    => $filename,
                'pdf'         => $pdf,
            ];
        });
    }

    /**
     * Merender pratinjau sertifikat dengan tanda air PRATINJAU.
     * Tidak menyimpan data ke database dan tidak menghabiskan kuota nomor urut.
     *
     * @param array<string, mixed> $data
     * @return array{nomor: string|null, filename: string, pdf: string}
     */
    public function preview(array $data): array
    {
        /** @var Event $event */
        $event = Event::query()->whereKey($data['event_id'])->firstOrFail();

        $date = Carbon::parse($data['tanggal_terbit'], 'Asia/Jakarta');
        $tahun = (int) $date->format('Y');

        $number = null;
        $seri = null;
        $urut = null;

        if ($event->bernomor) {
            $seri = isset($data['seri']) && (int) $data['seri'] > 0 ? (int) $data['seri'] : 1;
            $urut = isset($data['urut']) && (int) $data['urut'] > 0
                ? (int) $data['urut']
                : $this->allocator->next($event->id, $seri, $tahun);

            $number = NumberGenerator::build(
                $event->kode,
                $seri,
                $urut,
                $event->penerbit,
                $date
            );
        }

        $dataCetak = $this->buildDataCetak($event, $data);

        // Instance Certificate sementara in-memory
        $versiLayout = (string) config("layout.{$data['jenis']}.versi", '1.0');
        $certificate = new Certificate([
            'event_id'       => $event->id,
            'jenis'          => $data['jenis'],
            'seri'           => $seri,
            'urut'           => $urut,
            'tahun'          => $tahun,
            'nomor_lengkap'  => $number,
            'nama_peserta'   => $data['nama_peserta'],
            'tanggal_terbit' => $data['tanggal_terbit'],
            'data_cetak'     => $dataCetak,
            'versi_layout'   => $versiLayout,
            'status'         => 'terbit',
        ]);
        $certificate->setRelation('event', $event);

        $pdf = $this->pdfRenderer->render($certificate, 'pratinjau');

        return [
            'nomor'    => $number,
            'filename' => 'Pratinjau_Sertifikat.pdf',
            'pdf'      => $pdf,
        ];
    }

    /**
     * Menyusun array data cetak sesuai PRD Bagian 7.2 dan Design System 9.8.
     *
     * @param Event $event
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function buildDataCetak(Event $event, array $data): array
    {
        $kota = config('app.kota_terbit', 'Yogyakarta');
        $date = Carbon::parse($data['tanggal_terbit'] ?? now(), 'Asia/Jakarta');

        $bulanIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $tanggalFormatted = $date->day . ' ' . ($bulanIndo[(int) $date->format('n')] ?? $date->format('F')) . ' ' . $date->year;
        $tanggalKota = "{$kota}, {$tanggalFormatted}";
        $pakaiQr = (bool) ($event->pakai_qr ?? true);

        if ($data['jenis'] === 'seminar') {
            return [
                'judul_besar'    => 'SERTIFIKAT SEMINAR',
                'judul_kegiatan' => ! empty($data['judul_cetak']) ? $data['judul_cetak'] : $event->judul,
                'kode'           => $event->kode,
                'penerbit'       => $event->penerbit,
                'kota'           => $kota,
                'tanggal_kota'   => $tanggalKota,
                'pakai_qr'       => $pakaiQr,
            ];
        }

        $program = $data['program'] ?? $event->program_default ?? '';

        return [
            'judul_besar'     => 'SERTIFIKAT MAGANG',
            'bootcamp'        => $event->judul,
            'program'         => $program,
            'ujian'           => $program,
            'perusahaan'      => $data['perusahaan'] ?? '',
            'tanggal_mulai'   => $data['tanggal_mulai'] ?? '',
            'tanggal_selesai' => $data['tanggal_selesai'] ?? '',
            'kode'            => $event->kode,
            'penerbit'        => $event->penerbit,
            'kota'            => $kota,
            'tanggal_kota'    => $tanggalKota,
            'pakai_qr'        => $pakaiQr,
        ];
    }

    /**
     * Membangun nama berkas yang aman sesuai PRD PD-10:
     * Sertifikat_{Jenis}_{Nama}.pdf (Mu'ammal Hamidy -> Muammal_Hamidy).
     */
    private function generateSafeFilename(Certificate $certificate): string
    {
        $clean = preg_replace("/[\'\"]/", '', $certificate->nama_peserta);
        $clean = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $clean);
        $clean = trim(preg_replace('/_+/', '_', $clean), '_');

        $jenis = ucfirst($certificate->jenis);

        return "Sertifikat_{$jenis}_{$clean}.pdf";
    }
}
