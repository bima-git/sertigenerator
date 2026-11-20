<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\DateId;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DateIdTest extends TestCase
{
    private DateId $dateId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dateId = new DateId;
    }

    public function test_month_name_covers_all_twelve_months(): void
    {
        $expected = [
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

        foreach ($expected as $month => $name) {
            $this->assertSame($name, $this->dateId->monthName($month));
        }
    }

    public function test_month_name_rejects_invalid_month(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->dateId->monthName(13);
    }

    public function test_format_uses_indonesian_month_name(): void
    {
        $tanggal = new DateTimeImmutable('2026-10-24 10:00:00', new DateTimeZone(DateId::ZONA));

        $this->assertSame('24 Oktober 2026', $this->dateId->format($tanggal));
    }

    public function test_format_does_not_pad_single_digit_day(): void
    {
        $tanggal = new DateTimeImmutable('2026-01-05 10:00:00', new DateTimeZone(DateId::ZONA));

        $this->assertSame('5 Januari 2026', $this->dateId->format($tanggal));
    }

    public function test_format_converts_time_to_jakarta(): void
    {
        $tanggal = new DateTimeImmutable('2026-10-24 18:30:00', new DateTimeZone('UTC'));

        $this->assertSame('25 Oktober 2026', $this->dateId->format($tanggal));
    }

    public function test_format_accepts_string_input(): void
    {
        $this->assertSame('17 Agustus 2026', $this->dateId->format('2026-08-17 09:00:00'));
    }

    public function test_format_with_city_prefixes_city(): void
    {
        $this->assertSame(
            'Yogyakarta, 24 Oktober 2026',
            $this->dateId->formatWithCity('Yogyakarta', '2026-10-24 10:00:00'),
        );
    }

    public function test_format_with_city_trims_and_omits_empty_city(): void
    {
        $this->assertSame(
            'Yogyakarta, 24 Oktober 2026',
            $this->dateId->formatWithCity('  Yogyakarta  ', '2026-10-24 10:00:00'),
        );

        $this->assertSame(
            '24 Oktober 2026',
            $this->dateId->formatWithCity('   ', '2026-10-24 10:00:00'),
        );
    }
}
