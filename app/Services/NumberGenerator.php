<?php

namespace App\Services;

use Carbon\Carbon;
use DateTimeInterface;
use InvalidArgumentException;

class NumberGenerator
{
    /**
     * Membangun nomor sertifikat resmi XCODE sesuai formula:
     * {kode}/{seri}/{urut}/{penerbit}/{bulan_romawi}/{tahun}
     *
     * Contoh: 35/001/003/Xcodetraining/X/2026
     *
     * @param string $kode Kode kegiatan (misal: '35', '255', '15')
     * @param int|string $seri Nomor seri (default: 1 -> '001')
     * @param int|string $urut Nomor urut peserta (misal: 3 -> '003')
     * @param string $penerbit Nama penerbit (misal: 'Xcodetraining', 'Bootcamp', 'SiberExpert')
     * @param DateTimeInterface|string $tanggalTerbit Tanggal penerbitan sertifikat
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public static function build(
        string $kode,
        int|string $seri,
        int|string $urut,
        string $penerbit,
        DateTimeInterface|string $tanggalTerbit
    ): string {
        $kode = trim($kode);
        if ($kode === '') {
            throw new InvalidArgumentException('Kode kegiatan tidak boleh kosong.');
        }

        if (!is_numeric($seri) || (int)$seri <= 0) {
            throw new InvalidArgumentException("Seri harus berupa angka bulat positif, diberikan: {$seri}.");
        }

        if (!is_numeric($urut) || (int)$urut <= 0) {
            throw new InvalidArgumentException("Nomor urut harus berupa angka bulat positif, diberikan: {$urut}.");
        }

        $penerbit = trim($penerbit);
        if ($penerbit === '') {
            throw new InvalidArgumentException('Penerbit tidak boleh kosong.');
        }

        $date = self::parseDate($tanggalTerbit);
        $bulanRomawi = Roman::fromMonth((int)$date->format('n'));
        $tahun = $date->format('Y');

        $seriFormatted = sprintf('%03d', (int)$seri);
        $urutFormatted = sprintf('%03d', (int)$urut);

        return "{$kode}/{$seriFormatted}/{$urutFormatted}/{$penerbit}/{$bulanRomawi}/{$tahun}";
    }

    /**
     * Mem-parsing string nomor sertifikat ke dalam komponen array segmen.
     *
     * @param string $nomor
     * @return array{kode: string, seri: int, urut: int, penerbit: string, bulan_romawi: string, tahun: int}
     *
     * @throws InvalidArgumentException
     */
    public static function parse(string $nomor): array
    {
        $pattern = '#^(?<kode>[^/]+)/(?<seri>\d{3})/(?<urut>\d{3})/(?<penerbit>[^/]+)/(?<bulan>[IVXLCDM]+)/(?<tahun>\d{4})$#';

        if (!preg_match($pattern, trim($nomor), $matches)) {
            throw new InvalidArgumentException("Format nomor sertifikat tidak valid: {$nomor}. Format yang benar: {kode}/{seri}/{urut}/{penerbit}/{bulan}/{tahun}");
        }

        return [
            'kode' => $matches['kode'],
            'seri' => (int)$matches['seri'],
            'urut' => (int)$matches['urut'],
            'penerbit' => $matches['penerbit'],
            'bulan_romawi' => $matches['bulan'],
            'tahun' => (int)$matches['tahun'],
        ];
    }

    /**
     * Memeriksa apakah format nomor sertifikat valid.
     *
     * @param string $nomor
     * @return bool
     */
    public static function isValid(string $nomor): bool
    {
        try {
            self::parse($nomor);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Mengurai input tanggal ke instance Carbon dengan zona waktu Asia/Jakarta.
     *
     * @param DateTimeInterface|string $tanggal
     * @return Carbon
     */
    private static function parseDate(DateTimeInterface|string $tanggal): Carbon
    {
        if ($tanggal instanceof DateTimeInterface) {
            return Carbon::instance($tanggal)->timezone('Asia/Jakarta');
        }

        return Carbon::parse($tanggal, 'Asia/Jakarta');
    }
}
