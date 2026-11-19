<?php

namespace Tests\Unit\Services;

use App\Services\NumberGenerator;
use Carbon\Carbon;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class NumberGeneratorTest extends TestCase
{
    /**
     * Kasus Uji Wajib PRD 5.3:
     * kode '35', seri 1, urut 3, 'Xcodetraining', 2026-10-24 -> 35/001/003/Xcodetraining/X/2026
     */
    public function test_case_1_kode_35_urut_3(): void
    {
        $result = NumberGenerator::build('35', 1, 3, 'Xcodetraining', '2026-10-24');
        $this->assertSame('35/001/003/Xcodetraining/X/2026', $result);
    }

    /**
     * Kasus Uji Wajib PRD 5.3:
     * kode '35', seri 1, urut 9, 'Xcodetraining', 2026-10-24 -> 35/001/009/Xcodetraining/X/2026
     */
    public function test_case_2_kode_35_urut_9(): void
    {
        $result = NumberGenerator::build('35', 1, 9, 'Xcodetraining', '2026-10-24');
        $this->assertSame('35/001/009/Xcodetraining/X/2026', $result);
    }

    /**
     * Kasus Uji Wajib PRD 5.3:
     * kode '35', seri 1, urut 15, 'Xcodetraining', 2026-10-24 -> 35/001/015/Xcodetraining/X/2026
     */
    public function test_case_3_kode_35_urut_15(): void
    {
        $result = NumberGenerator::build('35', 1, 15, 'Xcodetraining', '2026-10-24');
        $this->assertSame('35/001/015/Xcodetraining/X/2026', $result);
    }

    /**
     * Kasus Uji Wajib PRD 5.3:
     * kode '15', seri 1, urut 1, 'Bootcamp', 2025-02-10 -> 15/001/001/Bootcamp/II/2025
     */
    public function test_case_4_kode_15_bootcamp(): void
    {
        $result = NumberGenerator::build('15', 1, 1, 'Bootcamp', '2025-02-10');
        $this->assertSame('15/001/001/Bootcamp/II/2025', $result);
    }

    /**
     * Kasus Uji Wajib PRD 5.3:
     * kode '23', seri 1, urut 1, 'SiberExpert', 2024-05-03 -> 23/001/001/SiberExpert/V/2024
     */
    public function test_case_5_kode_23_siberexpert(): void
    {
        $result = NumberGenerator::build('23', 1, 1, 'SiberExpert', '2024-05-03');
        $this->assertSame('23/001/001/SiberExpert/V/2024', $result);
    }

    /**
     * Kasus Uji Wajib PRD 5.3:
     * kode '255', seri 1, urut 1, 'Xcodetraining', 2026-12-01 -> 255/001/001/Xcodetraining/XII/2026
     */
    public function test_case_6_kode_255_desember(): void
    {
        $result = NumberGenerator::build('255', 1, 1, 'Xcodetraining', '2026-12-01');
        $this->assertSame('255/001/001/Xcodetraining/XII/2026', $result);
    }

    /**
     * Kasus Uji Wajib PRD 5.3:
     * Batas awal dan akhir tahun (Januari dan Desember)
     */
    public function test_case_7_boundary_dates_january_and_december(): void
    {
        $january = NumberGenerator::build('35', 1, 1, 'Xcodetraining', '2026-01-31');
        $this->assertStringEndsWith('I/2026', $january);

        $december = NumberGenerator::build('35', 1, 1, 'Xcodetraining', '2026-12-31');
        $this->assertStringEndsWith('XII/2026', $december);
    }

    /**
     * Memastikan penerimaan tipe data tanggal berupa DateTime dan Carbon.
     */
    public function test_accepts_datetime_and_carbon_instances(): void
    {
        $dateTime = new DateTime('2026-10-24 10:00:00');
        $result1 = NumberGenerator::build('35', 1, 3, 'Xcodetraining', $dateTime);
        $this->assertSame('35/001/003/Xcodetraining/X/2026', $result1);

        $carbon = Carbon::create(2026, 10, 24, 15, 30, 0, 'Asia/Jakarta');
        $result2 = NumberGenerator::build('35', 1, 3, 'Xcodetraining', $carbon);
        $this->assertSame('35/001/003/Xcodetraining/X/2026', $result2);
    }

    /**
     * Memastikan parsing nomor sertifikat mengembalikan komponen array yang tepat.
     */
    public function test_parse_valid_number_returns_segments(): void
    {
        $parsed = NumberGenerator::parse('35/001/003/Xcodetraining/X/2026');

        $this->assertSame('35', $parsed['kode']);
        $this->assertSame(1, $parsed['seri']);
        $this->assertSame(3, $parsed['urut']);
        $this->assertSame('Xcodetraining', $parsed['penerbit']);
        $this->assertSame('X', $parsed['bulan_romawi']);
        $this->assertSame(2026, $parsed['tahun']);
    }

    /**
     * Memastikan isValid mengembalikan true untuk format valid dan false untuk format tidak valid.
     */
    public function test_is_valid_validates_pattern(): void
    {
        $this->assertTrue(NumberGenerator::isValid('35/001/003/Xcodetraining/X/2026'));
        $this->assertTrue(NumberGenerator::isValid('255/001/001/Xcodetraining/XII/2026'));
        $this->assertFalse(NumberGenerator::isValid('invalid-number'));
        $this->assertFalse(NumberGenerator::isValid('35/1/3/Xcodetraining/X/2026')); // seri dan urut harus 3 digit
    }

    /**
     * Memastikan kode kosong melempar InvalidArgumentException.
     */
    public function test_throws_exception_for_empty_kode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        NumberGenerator::build('', 1, 1, 'Xcodetraining', '2026-10-24');
    }

    /**
     * Memastikan seri non-positif melempar InvalidArgumentException.
     */
    public function test_throws_exception_for_invalid_seri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        NumberGenerator::build('35', 0, 1, 'Xcodetraining', '2026-10-24');
    }

    /**
     * Memastikan nomor urut non-positif melempar InvalidArgumentException.
     */
    public function test_throws_exception_for_invalid_urut(): void
    {
        $this->expectException(InvalidArgumentException::class);
        NumberGenerator::build('35', 1, -5, 'Xcodetraining', '2026-10-24');
    }

    /**
     * Memastikan penerbit kosong melempar InvalidArgumentException.
     */
    public function test_throws_exception_for_empty_penerbit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        NumberGenerator::build('35', 1, 1, '   ', '2026-10-24');
    }
}
