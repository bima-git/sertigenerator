<?php

namespace Tests\Unit\Services;

use App\Services\Roman;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RomanTest extends TestCase
{
    /**
     * Memastikan konversi bulan 1 sampai 12 menghasilkan angka Romawi yang tepat.
     */
    public function test_from_month_maps_all_twelve_months_correctly(): void
    {
        $expected = [
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

        foreach ($expected as $month => $roman) {
            $this->assertSame($roman, Roman::fromMonth($month), "Bulan {$month} harus menghasilkan {$roman}");
        }
    }

    /**
     * Memastikan bulan kurang dari 1 melempar InvalidArgumentException.
     */
    public function test_from_month_throws_exception_for_zero_or_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Roman::fromMonth(0);
    }

    /**
     * Memastikan bulan lebih dari 12 melempar InvalidArgumentException.
     */
    public function test_from_month_throws_exception_for_month_greater_than_twelve(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Roman::fromMonth(13);
    }

    /**
     * Memastikan konversi umum angka Romawi toRoman berfungsi dengan benar.
     */
    public function test_to_roman_converts_arbitrary_numbers(): void
    {
        $this->assertSame('I', Roman::toRoman(1));
        $this->assertSame('IV', Roman::toRoman(4));
        $this->assertSame('IX', Roman::toRoman(9));
        $this->assertSame('X', Roman::toRoman(10));
        $this->assertSame('XLII', Roman::toRoman(42));
        $this->assertSame('XCIX', Roman::toRoman(99));
        $this->assertSame('MMXXVI', Roman::toRoman(2026));
    }

    /**
     * Memastikan toRoman melempar InvalidArgumentException untuk angka non-positif.
     */
    public function test_to_roman_throws_exception_for_invalid_numbers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Roman::toRoman(0);
    }
}
