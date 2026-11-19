<?php

namespace Tests\Feature\Services;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use App\Services\NumberAllocator;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberAllocatorTest extends TestCase
{
    use RefreshDatabase;

    private NumberAllocator $allocator;
    private User $user;
    private Event $seminarEvent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->allocator = new NumberAllocator();

        $this->user = User::factory()->create();

        $this->seminarEvent = Event::create([
            'kode' => '35',
            'judul' => 'Koding Laravel: 20x Lebih Cepat dengan Hermes AI',
            'jenis' => 'seminar',
            'penerbit' => 'Xcodetraining',
            'bernomor' => true,
            'pakai_qr' => true,
            'aktif' => true,
        ]);
    }

    /**
     * Memastikan alokasi pertama dimulai dari 1.
     */
    public function test_next_returns_one_when_no_certificates_exist(): void
    {
        $next = $this->allocator->next($this->seminarEvent->id, 1, 2026);
        $this->assertSame(1, $next);
    }

    /**
     * Memastikan alokasi bertambah secara sekuensial (1, 2, 3).
     */
    public function test_next_increments_sequentially(): void
    {
        $this->createCertificate($this->seminarEvent->id, 1, 1, 2026);
        $this->assertSame(2, $this->allocator->next($this->seminarEvent->id, 1, 2026));

        $this->createCertificate($this->seminarEvent->id, 1, 2, 2026);
        $this->assertSame(3, $this->allocator->next($this->seminarEvent->id, 1, 2026));
    }

    /**
     * Memastikan jika ada loncatan nomor (misal operator input nomor 9),
     * saran berikutnya adalah nilai maksimum + 1 (10).
     */
    public function test_next_follows_max_value_when_non_sequential(): void
    {
        $this->createCertificate($this->seminarEvent->id, 1, 3, 2026);
        $this->createCertificate($this->seminarEvent->id, 1, 9, 2026);

        $this->assertSame(10, $this->allocator->next($this->seminarEvent->id, 1, 2026));
    }

    /**
     * Memastikan alokasi nomor diisolasi per seri yang berbeda.
     */
    public function test_allocation_is_isolated_per_series(): void
    {
        $this->createCertificate($this->seminarEvent->id, 1, 5, 2026);

        // Seri 1 sekarang ada di 5, maka berikutnya 6
        $this->assertSame(6, $this->allocator->next($this->seminarEvent->id, 1, 2026));

        // Seri 2 belum ada, maka harus mulai dari 1
        $this->assertSame(1, $this->allocator->next($this->seminarEvent->id, 2, 2026));
    }

    /**
     * Memastikan alokasi nomor diisolasi per tahun terbit.
     */
    public function test_allocation_is_isolated_per_year(): void
    {
        $this->createCertificate($this->seminarEvent->id, 1, 10, 2025);

        // Tahun 2025 di urut 10
        $this->assertSame(11, $this->allocator->next($this->seminarEvent->id, 1, 2025));

        // Tahun 2026 baru mulai
        $this->assertSame(1, $this->allocator->next($this->seminarEvent->id, 1, 2026));
    }

    /**
     * Memastikan alokasi nomor diisolasi per kegiatan.
     */
    public function test_allocation_is_isolated_per_event(): void
    {
        $otherEvent = Event::create([
            'kode' => '255',
            'judul' => 'Ethical Web Hacking',
            'jenis' => 'seminar',
            'penerbit' => 'Xcodetraining',
            'bernomor' => true,
            'pakai_qr' => true,
            'aktif' => true,
        ]);

        $this->createCertificate($this->seminarEvent->id, 1, 5, 2026);

        $this->assertSame(6, $this->allocator->next($this->seminarEvent->id, 1, 2026));
        $this->assertSame(1, $this->allocator->next($otherEvent->id, 1, 2026));
    }

    /**
     * Aturan PRD NM-03:
     * Sertifikat yang berstatus DIBATALKAN tetap dihitung,
     * sehingga nomor yang dibatalkan TIDAK PERNAH dipakai ulang.
     */
    public function test_cancelled_certificates_are_included_in_next_calculation(): void
    {
        $this->createCertificate($this->seminarEvent->id, 1, 1, 2026, 'terbit');
        $this->createCertificate($this->seminarEvent->id, 1, 2, 2026, 'dibatalkan');

        // Walau nomor 2 dibatalkan, saran berikutnya harus 3, bukan kembali ke 2!
        $this->assertSame(3, $this->allocator->next($this->seminarEvent->id, 1, 2026));
    }

    /**
     * Memastikan isAvailable mendeteksi nomor yang sudah terpakai dan belum terpakai.
     */
    public function test_is_available_checks_availability(): void
    {
        $this->createCertificate($this->seminarEvent->id, 1, 3, 2026, 'terbit');
        $this->createCertificate($this->seminarEvent->id, 1, 4, 2026, 'dibatalkan');

        $this->assertTrue($this->allocator->isAvailable($this->seminarEvent->id, 1, 1, 2026));
        $this->assertTrue($this->allocator->isAvailable($this->seminarEvent->id, 1, 2, 2026));
        $this->assertFalse($this->allocator->isAvailable($this->seminarEvent->id, 1, 3, 2026));
        $this->assertFalse($this->allocator->isAvailable($this->seminarEvent->id, 1, 4, 2026)); // Yang dibatalkan juga tidak available
    }

    /**
     * Memastikan suggest menghasilkan payload lengkap untuk form penerbitan.
     */
    public function test_suggest_returns_complete_payload_for_numbered_event(): void
    {
        $this->createCertificate($this->seminarEvent->id, 1, 1, 2026);

        $suggestion = $this->allocator->suggest(
            $this->seminarEvent,
            seri: 1,
            tanggalTerbit: '2026-10-24'
        );

        $this->assertTrue($suggestion['bernomor']);
        $this->assertSame(2, $suggestion['urut_saran']);
        $this->assertSame(2, $suggestion['urut_aktif']);
        $this->assertSame('35/001/002/Xcodetraining/X/2026', $suggestion['nomor_lengkap']);
        $this->assertTrue($suggestion['is_available']);
    }

    /**
     * Memastikan suggest untuk kegiatan tanpa nomor (misal Bootcamp).
     */
    public function test_suggest_returns_null_fields_for_unnumbered_event(): void
    {
        $bootcampEvent = Event::create([
            'kode' => '15',
            'judul' => 'X-Code Bootcamp #14',
            'jenis' => 'bootcamp',
            'penerbit' => 'Bootcamp',
            'bernomor' => false,
            'pakai_qr' => false,
            'aktif' => true,
        ]);

        $suggestion = $this->allocator->suggest(
            $bootcampEvent,
            seri: 1,
            tanggalTerbit: '2026-10-24'
        );

        $this->assertFalse($suggestion['bernomor']);
        $this->assertNull($suggestion['urut_saran']);
        $this->assertNull($suggestion['nomor_lengkap']);
        $this->assertTrue($suggestion['is_available']);
    }

    /**
     * Memastikan database menolak duplikasi kombinasi (event_id, seri, urut, tahun)
     * melalui unique index constraint.
     */
    public function test_database_enforces_unique_constraint(): void
    {
        $this->createCertificate($this->seminarEvent->id, 1, 1, 2026);

        $this->expectException(QueryException::class);
        $this->createCertificate($this->seminarEvent->id, 1, 1, 2026);
    }

    /**
     * Helper untuk membuat data sertifikat uji.
     */
    private function createCertificate(
        int $eventId,
        int $seri,
        int $urut,
        int $tahun,
        string $status = 'terbit'
    ): Certificate {
        return Certificate::create([
            'event_id' => $eventId,
            'jenis' => 'seminar',
            'seri' => $seri,
            'urut' => $urut,
            'tahun' => $tahun,
            'nomor_lengkap' => "35/" . sprintf('%03d', $seri) . "/" . sprintf('%03d', $urut) . "/Xcodetraining/X/{$tahun}",
            'nama_peserta' => 'Peserta Uji ' . $urut,
            'tanggal_terbit' => "{$tahun}-10-24",
            'data_cetak' => [
                'judul_besar' => 'SERTIFIKAT SEMINAR',
                'judul_kegiatan' => 'Koding Laravel',
            ],
            'versi_layout' => '1.0',
            'status' => $status,
            'dibuat_oleh' => $this->user->id,
        ]);
    }
}
