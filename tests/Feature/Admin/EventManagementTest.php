<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Test untuk Backend Pengelolaan Kegiatan (Event Management).
 * Menguji PRD KG-01 s.d. KG-07, AD-01, AD-02, dan AU-07.
 */
class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Cegah ViteException saat merender view atau halaman error (403, 405) di lingkungan test
        $this->withoutVite();
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'peran' => 'admin',
            'aktif' => true,
        ]);
    }

    private function createOperator(): User
    {
        return User::factory()->create([
            'peran' => 'operator',
            'aktif' => true,
        ]);
    }

    public function test_guest_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/admin/kegiatan')->assertRedirect('/masuk');
        $this->get('/admin/kegiatan/baru')->assertRedirect('/masuk');
        $this->post('/admin/kegiatan/baru', [])->assertRedirect('/masuk');
        $this->get('/admin/kegiatan/1/ubah')->assertRedirect('/masuk');
        $this->post('/admin/kegiatan/1/ubah', [])->assertRedirect('/masuk');
        $this->post('/admin/kegiatan/1/aktif')->assertRedirect('/masuk');
    }

    public function test_operator_menerima_403_pada_semua_rute_admin_kegiatan(): void
    {
        $operator = $this->createOperator();
        $this->actingAs($operator);

        $event = Event::factory()->create();

        $this->get('/admin/kegiatan')->assertForbidden();
        $this->get('/admin/kegiatan/baru')->assertForbidden();
        $this->post('/admin/kegiatan/baru', [
            'jenis' => 'seminar',
            'kode' => '99',
            'judul' => 'Uji Coba Operator',
            'penerbit' => 'Xcodetraining',
        ])->assertForbidden();
        $this->get("/admin/kegiatan/{$event->id}/ubah")->assertForbidden();
        $this->post("/admin/kegiatan/{$event->id}/ubah", [
            'jenis' => 'seminar',
            'kode' => '99',
            'judul' => 'Uji Coba Ubah',
            'penerbit' => 'Xcodetraining',
        ])->assertForbidden();
        $this->post("/admin/kegiatan/{$event->id}/aktif")->assertForbidden();
    }

    public function test_admin_dapat_melihat_halaman_daftar_dan_formulir_kegiatan(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $event = Event::factory()->create([
            'kode' => '35',
            'judul' => 'Koding Laravel: 20x Lebih Cepat dengan Hermes AI',
            'jenis' => 'seminar',
        ]);

        $responseIndex = $this->get('/admin/kegiatan');
        $responseIndex->assertOk();
        $responseIndex->assertSee('35');
        $responseIndex->assertSee('Koding Laravel: 20x Lebih Cepat dengan Hermes AI');

        $responseCreate = $this->get('/admin/kegiatan/baru');
        $responseCreate->assertOk();
        $responseCreate->assertSee('Tambah kegiatan');

        $responseEdit = $this->get("/admin/kegiatan/{$event->id}/ubah");
        $responseEdit->assertOk();
        $responseEdit->assertSee('Ubah kegiatan 35');
    }

    public function test_admin_berhasil_menambah_kegiatan_baru_dan_mencatat_audit_log(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $payload = [
            'jenis' => 'seminar',
            'kode' => '35',
            'judul' => 'Koding Laravel: 20x Lebih Cepat dengan Hermes AI',
            'penerbit' => 'Xcodetraining',
            'program_default' => null,
            'bernomor' => '1',
            'pakai_qr' => '1',
            'aktif' => '1',
        ];

        $response = $this->post('/admin/kegiatan/baru', $payload);

        $response->assertRedirect('/admin/kegiatan');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('events', [
            'kode' => '35',
            'judul' => 'Koding Laravel: 20x Lebih Cepat dengan Hermes AI',
            'jenis' => 'seminar',
            'penerbit' => 'Xcodetraining',
            'bernomor' => true,
            'pakai_qr' => true,
            'aktif' => true,
        ]);

        $createdEvent = Event::where('kode', '35')->firstOrFail();

        // Verifikasi audit log (PRD AD-01)
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'aksi' => 'ubah_event',
            'target_id' => $createdEvent->id,
        ]);

        $log = AuditLog::where('target_id', $createdEvent->id)->latest('created_at')->firstOrFail();
        $this->assertEquals('tambah', $log->detail['tindakan']);
        $this->assertEquals('35', $log->detail['kode']);
        $this->assertEquals('Koding Laravel: 20x Lebih Cepat dengan Hermes AI', $log->detail['judul']);
    }

    public function test_admin_berhasil_mengubah_kegiatan_dan_mencatat_audit_log(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $event = Event::factory()->create([
            'kode' => '15',
            'judul' => 'Bootcamp Cyber Security Judul Lama',
            'jenis' => 'bootcamp',
            'penerbit' => 'Bootcamp',
            'bernomor' => false,
            'pakai_qr' => false,
            'aktif' => true,
        ]);

        $updatePayload = [
            'jenis' => 'bootcamp',
            'kode' => '15',
            'judul' => 'Bootcamp Penetration Testing Judul Baru',
            'penerbit' => 'Bootcamp',
            'program_default' => 'Penetration Testing & Cyber Security Engineer',
            'bernomor' => '0',
            'pakai_qr' => '0',
            'aktif' => '1',
        ];

        $response = $this->post("/admin/kegiatan/{$event->id}/ubah", $updatePayload);

        $response->assertRedirect('/admin/kegiatan');
        $response->assertSessionHas('success');

        $event->refresh();
        $this->assertEquals('Bootcamp Penetration Testing Judul Baru', $event->judul);
        $this->assertEquals('Penetration Testing & Cyber Security Engineer', $event->program_default);

        // Verifikasi audit log (PRD AD-01)
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'aksi' => 'ubah_event',
            'target_id' => $event->id,
        ]);

        $log = AuditLog::where('target_id', $event->id)->latest('created_at')->firstOrFail();
        $this->assertEquals('ubah', $log->detail['tindakan']);
        $this->assertEquals('15', $log->detail['kode']);
        $this->assertEquals('Bootcamp Penetration Testing Judul Baru', $log->detail['judul']);
    }

    public function test_admin_berhasil_toggle_aktif_nonaktif_kegiatan(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $event = Event::factory()->create([
            'kode' => '50',
            'aktif' => true,
        ]);

        // Toggle dari aktif menjadi nonaktif
        $responseDeactivate = $this->post("/admin/kegiatan/{$event->id}/aktif");
        $responseDeactivate->assertRedirect('/admin/kegiatan');
        $responseDeactivate->assertSessionHas('success');

        $event->refresh();
        $this->assertFalse($event->aktif);

        $logDeactivate = AuditLog::where('target_id', $event->id)->oldest('id')->firstOrFail();
        $this->assertEquals('nonaktifkan', $logDeactivate->detail['tindakan']);
        $this->assertFalse($logDeactivate->detail['aktif']);

        // Toggle kembali dari nonaktif menjadi aktif
        $responseActivate = $this->post("/admin/kegiatan/{$event->id}/aktif");
        $responseActivate->assertRedirect('/admin/kegiatan');
        $responseActivate->assertSessionHas('success');

        $event->refresh();
        $this->assertTrue($event->aktif);

        $logActivate = AuditLog::where('target_id', $event->id)->latest('id')->firstOrFail();
        $this->assertEquals('aktifkan', $logActivate->detail['tindakan']);
        $this->assertTrue($logActivate->detail['aktif']);
    }

    public function test_validasi_input_menolak_field_wajib_kosong_dan_format_salah(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $response = $this->post('/admin/kegiatan/baru', [
            'jenis' => 'format-tidak-valid',
            'kode' => '',
            'judul' => '',
            'penerbit' => '',
        ]);

        $response->assertSessionHasErrors(['jenis', 'kode', 'judul', 'penerbit']);

        // Batas maksimum panjang field
        $responseMax = $this->post('/admin/kegiatan/baru', [
            'jenis' => 'seminar',
            'kode' => str_repeat('A', 11), // maks 10
            'judul' => str_repeat('B', 501), // maks 500
            'penerbit' => str_repeat('C', 51), // maks 50
            'program_default' => str_repeat('D', 201), // maks 200
        ]);

        $responseMax->assertSessionHasErrors(['kode', 'judul', 'penerbit', 'program_default']);
    }

    public function test_kode_kegiatan_ganda_diperbolehkan_sesuai_prd_kg_04(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        // Buat kegiatan pertama dengan kode 39
        Event::factory()->create([
            'kode' => '39',
            'judul' => 'Kegiatan Seri Pertama dengan Kode 39',
        ]);

        // Simpan kegiatan kedua dengan kode yang sama (39)
        $payloadSecond = [
            'jenis' => 'seminar',
            'kode' => '39',
            'judul' => 'Kegiatan Seri Kedua dengan Kode 39 yang Sama',
            'penerbit' => 'Xcodetraining',
            'bernomor' => '1',
            'pakai_qr' => '1',
            'aktif' => '1',
        ];

        $response = $this->post('/admin/kegiatan/baru', $payloadSecond);
        $response->assertRedirect('/admin/kegiatan');
        $response->assertSessionHas('success');

        // Pastikan kedua record berhasil tersimpan di basis data tanpa error unik
        $this->assertEquals(2, Event::where('kode', '39')->count());
    }

    public function test_kegiatan_nonaktif_tidak_muncul_pada_api_penerbitan(): void
    {
        $operator = $this->createOperator();
        $this->actingAs($operator);

        $aktifEvent = Event::factory()->create([
            'kode' => 'AKTIF1',
            'judul' => 'Kegiatan Aktif',
            'aktif' => true,
        ]);

        $nonaktifEvent = Event::factory()->create([
            'kode' => 'NONAKTIF1',
            'judul' => 'Kegiatan Tidak Aktif',
            'aktif' => false,
        ]);

        $response = $this->getJson('/api/kegiatan');
        $response->assertOk();

        $ids = collect($response->json())->pluck('id')->all();
        $this->assertContains($aktifEvent->id, $ids);
        $this->assertNotContains($nonaktifEvent->id, $ids);
    }

    public function test_pencarian_dan_penyaringan_kegiatan(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        Event::factory()->create(['kode' => 'SEMINAR99', 'judul' => 'Python Web Security', 'jenis' => 'seminar']);
        Event::factory()->create(['kode' => 'BOOTCAMP88', 'judul' => 'Fullstack Dev Bootcamp', 'jenis' => 'bootcamp']);

        // Saring menurut jenis
        $responseSeminar = $this->get('/admin/kegiatan?jenis=seminar');
        $responseSeminar->assertOk();
        $responseSeminar->assertSee('Python Web Security');
        $responseSeminar->assertDontSee('Fullstack Dev Bootcamp');

        // Saring menurut pencarian query kode/judul
        $responseSearch = $this->get('/admin/kegiatan?q=BOOTCAMP88');
        $responseSearch->assertOk();
        $responseSearch->assertSee('Fullstack Dev Bootcamp');
        $responseSearch->assertDontSee('Python Web Security');
    }

    public function test_perubahan_kegiatan_tidak_mengubah_data_cetak_sertifikat_lama(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $event = Event::factory()->create([
            'kode' => '35',
            'judul' => 'Judul Kegiatan Asli Saat Diterbitkan',
            'penerbit' => 'Xcodetraining',
        ]);

        // Simpan sertifikat dengan salinan snapshot data_cetak
        $certificate = \App\Models\Certificate::create([
            'event_id' => $event->id,
            'jenis' => 'seminar',
            'seri' => 1,
            'urut' => 1,
            'tahun' => 2026,
            'nomor_lengkap' => '35/001/001/Xcodetraining/X/2026',
            'nama_peserta' => 'Peserta Uji',
            'tanggal_terbit' => '2026-10-24',
            'data_cetak' => [
                'judul_besar' => 'SERTIFIKAT SEMINAR',
                'judul_kegiatan' => 'Judul Kegiatan Asli Saat Diterbitkan',
                'kode' => '35',
                'penerbit' => 'Xcodetraining',
                'kota' => 'Yogyakarta',
            ],
            'versi_layout' => 'v1',
            'status' => 'terbit',
            'dibuat_oleh' => $admin->id,
        ]);

        // Admin mengubah judul dan kode kegiatan
        $this->post("/admin/kegiatan/{$event->id}/ubah", [
            'jenis' => 'seminar',
            'kode' => '35-BARU',
            'judul' => 'Judul Kegiatan Baru Setelah Diubah Admin',
            'penerbit' => 'Xcodetraining',
            'bernomor' => '1',
            'pakai_qr' => '1',
            'aktif' => '1',
        ])->assertRedirect('/admin/kegiatan');

        // Pastikan sertifikat lama data_cetak-nya TIDAK berubah (PRD KG-05)
        $certificate->refresh();
        $this->assertEquals('Judul Kegiatan Asli Saat Diterbitkan', $certificate->data_cetak['judul_kegiatan']);
        $this->assertEquals('35', $certificate->data_cetak['kode']);
    }

    public function test_kegiatan_nonaktif_menolak_penerbitan_pada_issue_service(): void
    {
        $event = Event::factory()->create([
            'kode' => 'NONAKTIF-TEST',
            'judul' => 'Kegiatan Nonaktif Penolakan',
            'jenis' => 'seminar',
            'aktif' => false,
        ]);

        $issueService = app(\App\Services\IssueService::class);
        $user = $this->createOperator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Kegiatan [{$event->judul}] sudah tidak aktif.");

        $issueService->issue([
            'jenis' => 'seminar',
            'event_id' => $event->id,
            'nama_peserta' => 'Peserta Uji',
            'tanggal_terbit' => '2026-10-24',
        ], $user);
    }

    public function test_tidak_ada_rute_penghapusan_permanen_kegiatan(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $event = Event::factory()->create();

        // Memastikan method DELETE tidak terdaftar (PRD KG-03)
        $this->delete("/admin/kegiatan/{$event->id}")->assertNotFound();
        $this->delete("/admin/kegiatan/{$event->id}/ubah")->assertStatus(405);
        $this->delete('/admin/kegiatan')->assertStatus(405);
    }
}
