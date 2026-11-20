<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\DateId;
use App\Services\NumberGenerator;
use App\Services\SampleCertificateData;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SampleCertificateDataTest extends TestCase
{
    public function test_seminar_numbers_follow_documented_examples(): void
    {
        $samples = $this->samples(new DateTimeImmutable('2026-10-24 10:00:00'));

        $this->assertSame([
            '35/001/003/Xcodetraining/X/2026',
            '35/001/009/Xcodetraining/X/2026',
            '35/001/015/Xcodetraining/X/2026',
        ], array_column(array_column($samples, 'data'), 'nomor_lengkap'));

        $this->assertSame('Yogyakarta, 24 Oktober 2026', $samples[0]['data']['tanggal_kota']);
    }

    public function test_month_and_year_follow_issue_date_like_real_samples(): void
    {
        $samples = $this->samples(new DateTimeImmutable('2026-11-13 10:00:00'));

        $this->assertSame('35/001/003/Xcodetraining/XI/2026', $samples[0]['data']['nomor_lengkap']);
        $this->assertSame(
            NumberGenerator::build('35', 1, 3, 'Xcodetraining', '2026-11-13'),
            $samples[0]['data']['nomor_lengkap'],
        );
        $this->assertSame('Yogyakarta, 13 November 2026', $samples[0]['data']['tanggal_kota']);
    }

    /**
     * @return list<array{jenis: string, berkas: string, data: array<string, string>}>
     */
    private function samples(DateTimeImmutable $tanggal): array
    {
        return (new SampleCertificateData(new DateId))->samples($tanggal);
    }
}
