<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Event;
use Carbon\Carbon;
use DateTimeInterface;
use InvalidArgumentException;

class NumberAllocator
{
    /**
     * Menghitung nomor urut saran sistem berikutnya untuk kombinasi:
     * event_id, seri, dan tahun.
     *
     * Aturan PRD NM-03:
     * Nilai tertinggi ditambah 1. Sertifikat yang berstatus dibatalkan
     * tetap ikut dihitung agar nomor yang sudah pernah terbit tidak dipakai ulang.
     *
     * @param int $eventId ID kegiatan dari tabel events
     * @param int $seri Nomor seri (default 1)
     * @param int $tahun Tahun dari tanggal terbit sertifikat
     * @return int
     */
    public function next(int $eventId, int $seri, int $tahun): int
    {
        $maxUrut = Certificate::query()
            ->where('event_id', $eventId)
            ->where('seri', $seri)
            ->where('tahun', $tahun)
            ->max('urut');

        return ((int) $maxUrut) + 1;
    }

    /**
     * Memeriksa apakah kombinasi event, seri, urut, dan tahun tertentu masih tersedia.
     *
     * @param int $eventId
     * @param int $seri
     * @param int $urut
     * @param int $tahun
     * @return bool True jika belum terpakai, False jika sudah ada
     */
    public function isAvailable(int $eventId, int $seri, int $urut, int $tahun): bool
    {
        return ! Certificate::query()
            ->where('event_id', $eventId)
            ->where('seri', $seri)
            ->where('urut', $urut)
            ->where('tahun', $tahun)
            ->exists();
    }

    /**
     * Memeriksa apakah string nomor lengkap sudah tercatat pada basis data.
     *
     * @param string $nomorLengkap
     * @return bool True jika sudah terpakai
     */
    public function isNumberTaken(string $nomorLengkap): bool
    {
        return Certificate::query()
            ->where('nomor_lengkap', trim($nomorLengkap))
            ->exists();
    }

    /**
     * Menghasilkan saran penomoran dan preview nomor lengkap untuk kebutuhan form/API.
     *
     * @param Event|int $event Model Event atau ID event
     * @param int $seri Nomor seri (default: 1)
     * @param DateTimeInterface|string|null $tanggalTerbit Tanggal terbit sertifikat
     * @param int|null $manualUrut Nomor urut pilihan operator (opsional)
     * @return array{
     *     bernomor: bool,
     *     urut_saran: int|null,
     *     urut_aktif: int|null,
     *     nomor_lengkap: string|null,
     *     kode: string,
     *     penerbit: string,
     *     seri: int|null,
     *     tahun: int,
     *     is_available: bool
     * }
     *
     * @throws InvalidArgumentException
     */
    public function suggest(
        Event|int $event,
        int $seri = 1,
        DateTimeInterface|string|null $tanggalTerbit = null,
        ?int $manualUrut = null
    ): array {
        if (! ($event instanceof Event)) {
            $event = Event::query()->findOrFail($event);
        }

        $date = $this->parseDate($tanggalTerbit);
        $tahun = (int) $date->format('Y');

        if (! $event->bernomor) {
            return [
                'bernomor' => false,
                'urut_saran' => null,
                'urut_aktif' => null,
                'nomor_lengkap' => null,
                'kode' => $event->kode,
                'penerbit' => $event->penerbit,
                'seri' => null,
                'tahun' => $tahun,
                'is_available' => true,
            ];
        }

        $urutSaran = $this->next($event->id, $seri, $tahun);
        $urutAktif = ($manualUrut !== null && $manualUrut > 0) ? $manualUrut : $urutSaran;
        $isAvailable = $this->isAvailable($event->id, $seri, $urutAktif, $tahun);

        $nomorLengkap = NumberGenerator::build(
            $event->kode,
            $seri,
            $urutAktif,
            $event->penerbit,
            $date
        );

        return [
            'bernomor' => true,
            'urut_saran' => $urutSaran,
            'urut_aktif' => $urutAktif,
            'nomor_lengkap' => $nomorLengkap,
            'kode' => $event->kode,
            'penerbit' => $event->penerbit,
            'seri' => $seri,
            'tahun' => $tahun,
            'is_available' => $isAvailable,
        ];
    }

    /**
     * Mengurai input tanggal ke instance Carbon dengan zona waktu Asia/Jakarta.
     *
     * @param DateTimeInterface|string|null $tanggal
     * @return Carbon
     */
    private function parseDate(DateTimeInterface|string|null $tanggal): Carbon
    {
        if ($tanggal === null) {
            return Carbon::now('Asia/Jakarta');
        }

        if ($tanggal instanceof DateTimeInterface) {
            return Carbon::instance($tanggal)->timezone('Asia/Jakarta');
        }

        return Carbon::parse($tanggal, 'Asia/Jakarta');
    }
}
