<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use App\Services\NumberAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operator;
    private Event $seminarEvent;
    private Certificate $certificate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create([
            'username' => 'admin_uji',
            'nama'     => 'Admin Penguji',
        ]);

        $this->operator = User::factory()->operator()->create([
            'username' => 'operator_uji',
            'nama'     => 'Operator Penguji',
        ]);

        $this->seminarEvent = Event::create([
            'kode'     => '35',
            'judul'    => 'Koding Laravel: 20x Lebih Cepat dengan Hermes AI',
            'jenis'    => 'seminar',
            'penerbit' => 'Xcodetraining',
            'bernomor' => true,
            'pakai_qr' => true,
            'aktif'    => true,
        ]);

        $this->certificate = Certificate::create([
            'event_id'       => $this->seminarEvent->id,
            'jenis'          => 'seminar',
            'seri'           => 1,
            'urut'           => 3,
            'tahun'          => 2026,
            'nomor_lengkap'  => '35/001/003/Xcodetraining/X/2026',
            'nama_peserta'   => "Mu'ammal Hamidy",
            'tanggal_terbit' => '2026-10-24',
            'data_cetak'     => [
                'judul_besar'    => 'SERTIFIKAT SEMINAR',
                'judul_kegiatan' => 'Koding Laravel: 20x Lebih Cepat dengan Hermes AI',
                'kode'           => '35',
                'penerbit'       => 'Xcodetraining',
                'kota'           => 'Yogyakarta',
            ],
            'versi_layout'   => '1.0',
            'status'         => 'terbit',
            'dibuat_oleh'    => $this->operator->id,
        ]);
    }

    /**
     * Memastikan unduh ulang sertifikat yang sah menghasilkan berkas PDF identik,
     * header sesuai, dan audit log unduh ulang tercatat.
     */
    public function test_download_active_certificate_success(): void
    {
        $response = $this->actingAs($this->operator)
            ->get(route('history.pdf', ['id' => $this->certificate->id]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="Sertifikat_Seminar_Muammal_Hamidy.pdf"');
        $response->assertHeader('X-Certificate-Number', '35/001/003/Xcodetraining/X/2026');

        // Pastikan tidak ada watermark DIBATALKAN
        $this->assertStringNotContainsString('DIBATALKAN', $response->getContent());

        // Pastikan jejak audit unduh_ulang tercatat
        $this->assertDatabaseHas('audit_logs', [
            'aksi'      => 'unduh_ulang',
            'target_id' => $this->certificate->id,
        ]);
    }

    /**
     * Memastikan Admin dapat membatalkan sertifikat resmi dengan mencantumkan alasan.
     */
    public function test_admin_can_cancel_certificate_with_reason(): void
    {
        $response = $this->actingAs($this->admin)->postJson(
            route('history.cancel', ['id' => $this->certificate->id]),
            ['alasan' => 'Terdapat kesalahan penulisan nama peserta oleh operator']
        );

        $response->assertOk()
            ->assertJson([
                'status'       => 'dibatalkan',
                'alasan_batal' => 'Terdapat kesalahan penulisan nama peserta oleh operator',
            ]);

        // Verifikasi database: status berubah dan nomor tidak dihapus
        $this->certificate->refresh();
        $this->assertSame('dibatalkan', $this->certificate->status);
        $this->assertSame('Terdapat kesalahan penulisan nama peserta oleh operator', $this->certificate->alasan_batal);
        $this->assertSame($this->admin->id, $this->certificate->dibatalkan_oleh);
        $this->assertNotNull($this->certificate->dibatalkan_pada);

        // Verifikasi audit log pembatalan
        $this->assertDatabaseHas('audit_logs', [
            'user_id'   => $this->admin->id,
            'aksi'      => 'batal',
            'target_id' => $this->certificate->id,
        ]);
    }

    /**
     * Memastikan unduh ulang sertifikat yang DIBATALKAN menyematkan tanda air DIBATALKAN pada PDF.
     */
    public function test_download_cancelled_certificate_contains_cancelled_watermark(): void
    {
        // Batalkan sertifikat terlebih dahulu
        $this->certificate->update([
            'status'          => 'dibatalkan',
            'alasan_batal'    => 'Koreksi sertifikat',
            'dibatalkan_oleh' => $this->admin->id,
            'dibatalkan_pada' => now(),
        ]);

        $response = $this->actingAs($this->operator)
            ->get(route('history.pdf', ['id' => $this->certificate->id]));

        $response->assertOk();

        // Pastikan konten PDF menyematkan watermark DIBATALKAN (mendukung plain text maupun compressed TCPDF stream)
        $this->assertPdfContainsText('DIBATALKAN', $response->getContent());
    }

    /**
     * Memeriksa apakah teks terdapat dalam PDF, baik pada stream teks biasa maupun compressed stream zlib TCPDF.
     */
    private function assertPdfContainsText(string $needle, string $pdfContent): void
    {
        if (str_contains($pdfContent, $needle)) {
            $this->assertTrue(true);
            return;
        }

        $found = false;
        if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $pdfContent, $matches)) {
            foreach ($matches[1] as $stream) {
                $decompressed = @gzuncompress(trim($stream));
                if ($decompressed !== false && str_contains($decompressed, $needle)) {
                    $found = true;
                    break;
                }
            }
        }

        $this->assertTrue($found, "PDF tidak memuat teks '{$needle}' (diuji baik raw maupun stream terdekompresi).");
    }

    /**
     * ATURAN KRITIS (PRD Section 4.4 & 4.6, Business Flow AB-13):
     * Memastikan nomor sertifikat yang dibatalkan TIDAK PERNAH dipakai ulang.
     */
    public function test_cancelled_certificate_number_is_never_reused(): void
    {
        // Sertifikat urut 3 dibatalkan
        $this->certificate->update([
            'status'          => 'dibatalkan',
            'alasan_batal'    => 'Koreksi nama',
            'dibatalkan_oleh' => $this->admin->id,
            'dibatalkan_pada' => now(),
        ]);

        $allocator = new NumberAllocator();

        // Alokasi urut berikutnya untuk seri 1 tahun 2026 harus 4 (bukan kembali ke 3!)
        $nextUrut = $allocator->next($this->seminarEvent->id, 1, 2026);
        $this->assertSame(4, $nextUrut);

        // Nomor urut 3 harus terdeteksi sudah tidak tersedia (isAvailable == false)
        $this->assertFalse($allocator->isAvailable($this->seminarEvent->id, 1, 3, 2026));
    }

    /**
     * Memastikan operator non-admin ditolak (403 Forbidden) saat mencoba membatalkan sertifikat.
     */
    public function test_non_admin_cannot_cancel_certificate(): void
    {
        $response = $this->actingAs($this->operator)->postJson(
            route('history.cancel', ['id' => $this->certificate->id]),
            ['alasan' => 'Mencoba batalkan tanpa hak admin']
        );

        $response->assertStatus(403);
    }

    /**
     * Memastikan pembatalan gagal jika alasan tidak diisi (HTTP 422).
     */
    public function test_cancel_requires_reason(): void
    {
        $response = $this->actingAs($this->admin)->postJson(
            route('history.cancel', ['id' => $this->certificate->id]),
            ['alasan' => '']
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['alasan']);
    }
}
