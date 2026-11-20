<?php

declare(strict_types=1);

namespace Tests\Unit\Pdf;

use App\Models\Certificate;
use App\Models\Event;
use App\Pdf\CertificateDataMapper;
use App\Services\DateId;
use Tests\TestCase;

final class CertificateDataMapperTest extends TestCase
{
    private CertificateDataMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new CertificateDataMapper(new DateId);
    }

    public function test_seminar_uses_snapshot_from_data_cetak(): void
    {
        $certificate = $this->certificate('seminar', [
            'judul_kegiatan' => 'Koding Laravel',
            'tanggal_kota' => 'Yogyakarta, 24 Oktober 2026',
            'pakai_qr' => true,
        ]);

        $data = $this->mapper->map($certificate);

        $this->assertSame('Arief Soekardi', $data['nama_peserta']);
        $this->assertSame('35/001/003/Xcodetraining/X/2026', $data['nomor_lengkap']);
        $this->assertSame('Koding Laravel', $data['judul_cetak']);
        $this->assertSame('Yogyakarta, 24 Oktober 2026', $data['tanggal_kota']);
        $this->assertTrue($data['pakai_qr']);
        $this->assertArrayNotHasKey('ujian', $data);
    }

    public function test_bootcamp_maps_program_to_ujian_when_ujian_absent(): void
    {
        $certificate = $this->certificate('bootcamp', [
            'bootcamp' => 'X-Code Bootcamp #14',
            'program' => 'Fullstack Web Development',
            'pakai_qr' => false,
        ]);

        $data = $this->mapper->map($certificate);

        $this->assertSame('X-Code Bootcamp #14', $data['bootcamp']);
        $this->assertSame('Fullstack Web Development', $data['ujian']);
        $this->assertFalse($data['pakai_qr']);
        $this->assertArrayNotHasKey('judul_cetak', $data);
    }

    public function test_legacy_snapshot_falls_back_to_event_and_formatted_date(): void
    {
        $certificate = $this->certificate('seminar', ['judul_kegiatan' => '']);
        $certificate->event->judul = 'Judul dari Event';
        $certificate->event->pakai_qr = false;
        $certificate->tanggal_terbit = '2026-10-24';

        $data = $this->mapper->map($certificate);

        $this->assertSame('Judul dari Event', $data['judul_cetak']);
        $this->assertSame('Yogyakarta, 24 Oktober 2026', $data['tanggal_kota']);
        $this->assertFalse($data['pakai_qr']);
    }

    public function test_missing_number_is_omitted(): void
    {
        $certificate = $this->certificate('bootcamp', []);
        $certificate->nomor_lengkap = null;

        $data = $this->mapper->map($certificate);

        $this->assertArrayNotHasKey('nomor_lengkap', $data);
    }

    /**
     * @param  array<string, mixed>  $dataCetak
     */
    private function certificate(string $jenis, array $dataCetak): Certificate
    {
        $certificate = new Certificate([
            'jenis' => $jenis,
            'nama_peserta' => 'Arief Soekardi',
            'nomor_lengkap' => '35/001/003/Xcodetraining/X/2026',
            'tanggal_terbit' => '2026-10-24',
        ]);
        $certificate->data_cetak = $dataCetak;

        $event = new Event([
            'judul' => 'Kegiatan Contoh',
            'jenis' => $jenis,
            'pakai_qr' => true,
            'program_default' => 'Program Contoh',
        ]);

        $certificate->setRelation('event', $event);

        return $certificate;
    }
}
