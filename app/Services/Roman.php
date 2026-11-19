<?php

namespace App\Services;

use InvalidArgumentException;

class Roman
{
    /**
     * Peta angka ke angka Romawi standar (1 s.d. 12 untuk bulan).
     */
    private const MONTH_MAP = [
        1 => 'I',
        2 => 'II',
        3 => 'III',
        4 => 'IV',
        5 => 'V',
        6 => 'VI',
        7 => 'VII',
        8 => 'VIII',
        9 => 'IX',
        10 => 'X',
        11 => 'XI',
        12 => 'XII',
    ];

    /**
     * Konversi angka bulan (1 - 12) ke angka Romawi.
     *
     * @param int $month
     * @return string
     * @throws InvalidArgumentException
     */
    public static function fromMonth(int $month): string
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException("Bulan tidak valid: {$month}. Bulan harus berada di antara 1 dan 12.");
        }

        return self::MONTH_MAP[$month];
    }

    /**
     * Konversi angka integer positif ke angka Romawi umum (1 s.d. 3999).
     *
     * @param int $number
     * @return string
     * @throws InvalidArgumentException
     */
    public static function toRoman(int $number): string
    {
        if ($number <= 0 || $number > 3999) {
            throw new InvalidArgumentException("Angka harus berada di antara 1 dan 3999, diberikan: {$number}.");
        }

        $lookup = [
            1000 => 'M',
            900 => 'CM',
            500 => 'D',
            400 => 'CD',
            100 => 'C',
            90 => 'XC',
            50 => 'L',
            40 => 'XL',
            10 => 'X',
            9 => 'IX',
            5 => 'V',
            4 => 'IV',
            1 => 'I',
        ];

        $roman = '';
        foreach ($lookup as $value => $glyph) {
            while ($number >= $value) {
                $roman .= $glyph;
                $number -= $value;
            }
        }

        return $roman;
    }
}
