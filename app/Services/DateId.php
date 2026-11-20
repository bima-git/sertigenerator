<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Format tanggal sertifikat dalam bahasa Indonesia (PRD PD-09).
 *
 * Nama bulan diambil dari tabel di kode, bukan dari locale server,
 * dan tanggal selalu dibaca dalam zona waktu Asia/Jakarta.
 */
final class DateId
{
    public const ZONA = 'Asia/Jakarta';

    private const BULAN = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function monthName(int $month): string
    {
        if (! isset(self::BULAN[$month])) {
            throw new InvalidArgumentException("Bulan tidak valid: {$month}.");
        }

        return self::BULAN[$month];
    }

    /**
     * Contoh: 24 Oktober 2026.
     */
    public function format(DateTimeInterface|string $tanggal): string
    {
        $waktu = $this->toJakarta($tanggal);

        return sprintf(
            '%d %s %d',
            (int) $waktu->format('j'),
            $this->monthName((int) $waktu->format('n')),
            (int) $waktu->format('Y'),
        );
    }

    /**
     * Contoh: Yogyakarta, 24 Oktober 2026.
     */
    public function formatWithCity(string $city, DateTimeInterface|string $tanggal): string
    {
        $city = trim($city);
        $format = $this->format($tanggal);

        return $city === '' ? $format : $city.', '.$format;
    }

    private function toJakarta(DateTimeInterface|string $tanggal): DateTimeImmutable
    {
        $zona = new DateTimeZone(self::ZONA);

        $waktu = $tanggal instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($tanggal)
            : new DateTimeImmutable($tanggal, $zona);

        return $waktu->setTimezone($zona);
    }
}
