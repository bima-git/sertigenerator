<?php

namespace Tests\Feature;

use App\Exceptions\DuplicateNumberException;
use App\Exceptions\RenderException;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use App\Services\PdfRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueCertificateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Event $seminarEvent;
    private Event $bootcampEvent;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->bootcampEvent = Event::create([
            'kode' => '15',
            'judul' => 'X-Code Bootcamp #14',
            'jenis' => 'bootcamp',
            'penerbit' => 'Bootcamp',
            'bernomor' => false,
            'pakai_qr' => false,
            'program_default' => 'Cyber Security Engineer',
            'aktif' => true,
        ]);
    }

    /**
     * Uji alur penerbitan seminar berhasil: nomor dibuat, sertifikat tersimpan,
     * PDF terkirim sebagai unduhan, dan audit log tercatat.
     */
    public function test_issue_seminar_certificate_success(): void
    {
        $payload = [
            'jenis'          => 'seminar',
            'event_id'       => $this->seminarEvent->id,
            'nama_peserta'   => "Mu'ammal Hamidy",
            'tanggal_terbit' => '2026-10-24',
            'seri'           => 1,
        ];

        $response = $this->actingAs($this->user)->post(route('issue.store'), $payload);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="Sertifikat_Seminar_Muammal_Hamidy.pdf"');
        $response->assertHeader('X-Certificate-Number', '35/001/001/Xcodetraining/X/2026');

        // Pastikan baris sertifikat tersimpan di database
        $this->assertDatabaseHas('certificates', [
            'event_id'      => $this->seminarEvent->id,
            'jenis'         => 'seminar',
            'seri'          => 1,
            'urut'          => 1,
            'tahun'         => 2026,
            'nomor_lengkap' => '35/001/001/Xcodetraining/X/2026',
            'nama_peserta'  => "Mu'ammal Hamidy",
            'status'        => 'terbit',
        ]);

        // Pastikan jejak audit tercatat
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'aksi'    => 'terbit',
        ]);
    }

    /**
     * Uji alur penerbitan bootcamp berhasil: tanpa nomor dan data cetak memuat
     * informasi perusahaan serta rentang tanggal magang.
     */
    public function test_issue_bootcamp_certificate_success(): void
    {
        $payload = [
            'jenis'           => 'bootcamp',
            'event_id'        => $this->bootcampEvent->id,
            'nama_peserta'    => 'Akrom Masani Iksanul Amri',
            'program'         => 'Penetration Testing & Cyber Security',
            'perusahaan'      => 'PT Teknologi Keamanan Digital',
            'tanggal_mulai'   => '2026-08-01',
            'tanggal_selesai' => '2026-11-01',
            'tanggal_terbit'  => '2026-11-05',
        ];

        $response = $this->actingAs($this->user)->post(route('issue.store'), $payload);

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename="Sertifikat_Bootcamp_Akrom_Masani_Iksanul_Amri.pdf"');

        $cert = Certificate::first();
        $this->assertNotNull($cert);
        $this->assertNull($cert->nomor_lengkap);
        $this->assertNull($cert->urut);
        $this->assertSame('PT Teknologi Keamanan Digital', $cert->data_cetak['perusahaan']);
        $this->assertSame('Akrom Masani Iksanul Amri', $cert->nama_peserta);
    }

    /**
     * Uji alur pratinjau: PDF inline bertanda air PRATINJAU dan TIDAK menyimpan ke database.
     */
    public function test_preview_certificate_does_not_save_to_database(): void
    {
        $payload = [
            'jenis'          => 'seminar',
            'event_id'       => $this->seminarEvent->id,
            'nama_peserta'   => 'Calon Peserta',
            'tanggal_terbit' => '2026-10-24',
        ];

        $response = $this->actingAs($this->user)->post(route('issue.preview'), $payload);

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'inline; filename="Pratinjau_Sertifikat.pdf"');

        // Pastikan konten PDF memuat watermark PRATINJAU
        $this->assertStringContainsString('PRATINJAU', $response->getContent());

        // Pastikan tidak ada data yang tersimpan dan audit log kosong
        $this->assertDatabaseCount('certificates', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    /**
     * ATURAN KRITIS (PRD Section 4.5 & Business Flow 3.10):
     * Jika render PDF gagal, transaksi database harus di-ROLLBACK otomatis!
     * Tidak ada baris sertifikat tersimpan dan nomor urut tetap bebas.
     */
    public function test_atomic_rollback_when_pdf_rendering_fails(): void
    {
        // Mock PdfRenderer agar melempar exception galat render
        $mockPdf = $this->mock(PdfRenderer::class);
        $mockPdf->shouldReceive('render')
            ->once()
            ->andThrow(new RenderException('Simulasi kegagalan TCPDF memori'));

        $payload = [
            'jenis'          => 'seminar',
            'event_id'       => $this->seminarEvent->id,
            'nama_peserta'   => 'Peserta Uji Gagal',
            'tanggal_terbit' => '2026-10-24',
        ];

        $response = $this->actingAs($this->user)->post(route('issue.store'), $payload);
        $response->assertStatus(500);

        // Bukti rollback atomik:
        // 1. Tidak ada data sertifikat yang tersimpan
        $this->assertDatabaseCount('certificates', 0);

        // 2. Tidak ada audit log penerbitan
        $this->assertDatabaseCount('audit_logs', 0);
    }

    /**
     * Memastikan operator ditolak bila mencoba menggunakan nomor urut yang sudah ada.
     */
    public function test_duplicate_manual_urut_is_rejected(): void
    {
        // Terbitkan nomor 1
        $this->actingAs($this->user)->post(route('issue.store'), [
            'jenis'          => 'seminar',
            'event_id'       => $this->seminarEvent->id,
            'nama_peserta'   => 'Peserta Pertama',
            'tanggal_terbit' => '2026-10-24',
            'seri'           => 1,
            'urut'           => 1,
        ]);

        // Coba terbitkan lagi dengan nomor urut 1 yang sama
        $this->expectException(DuplicateNumberException::class);

        $this->actingAs($this->user)->withoutExceptionHandling()->post(route('issue.store'), [
            'jenis'          => 'seminar',
            'event_id'       => $this->seminarEvent->id,
            'nama_peserta'   => 'Peserta Kedua',
            'tanggal_terbit' => '2026-10-24',
            'seri'           => 1,
            'urut'           => 1,
        ]);
    }

    /**
     * Validasi gagal jika tanggal selesai bootcamp lebih awal dari tanggal mulai.
     */
    public function test_validation_rejects_bootcamp_invalid_dates(): void
    {
        $payload = [
            'jenis'           => 'bootcamp',
            'event_id'        => $this->bootcampEvent->id,
            'nama_peserta'    => 'Peserta Uji',
            'program'         => 'Security Engineer',
            'perusahaan'      => 'PT Keamanan',
            'tanggal_mulai'   => '2026-11-10',
            'tanggal_selesai' => '2026-11-01', // Invalid: sebelum mulai
            'tanggal_terbit'  => '2026-11-11',
        ];

        $response = $this->actingAs($this->user)->postJson(route('issue.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tanggal_selesai']);
    }
}
