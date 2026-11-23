<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature Test untuk Backend Pengelolaan Pengguna (User Management).
 * Menguji PRD PG-01, PG-02, PG-03, AU-01, AU-04, AU-07, dan AD-01.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function createAdmin(array $attr = []): User
    {
        return User::factory()->create([
            'peran' => 'admin',
            'aktif' => true,
        ] + $attr);
    }

    private function createOperator(array $attr = []): User
    {
        return User::factory()->create([
            'peran' => 'operator',
            'aktif' => true,
        ] + $attr);
    }

    public function test_guest_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/admin/pengguna')->assertRedirect('/masuk');
        $this->get('/admin/pengguna/baru')->assertRedirect('/masuk');
        $this->post('/admin/pengguna/baru', [])->assertRedirect('/masuk');
        $this->get('/admin/pengguna/1/ubah')->assertRedirect('/masuk');
        $this->post('/admin/pengguna/1/ubah', [])->assertRedirect('/masuk');
        $this->post('/admin/pengguna/1/reset-sandi')->assertRedirect('/masuk');
    }

    public function test_operator_menerima_403_pada_semua_rute_admin_pengguna(): void
    {
        $operator = $this->createOperator();
        $this->actingAs($operator);

        $targetUser = $this->createOperator();

        $this->get('/admin/pengguna')->assertForbidden();
        $this->get('/admin/pengguna/baru')->assertForbidden();
        $this->post('/admin/pengguna/baru', [
            'nama' => 'User Baru',
            'username' => 'userbaru',
            'peran' => 'operator',
        ])->assertForbidden();
        $this->get("/admin/pengguna/{$targetUser->id}/ubah")->assertForbidden();
        $this->post("/admin/pengguna/{$targetUser->id}/ubah", [
            'nama' => 'User Ubah',
            'username' => $targetUser->username,
        ])->assertForbidden();
        $this->post("/admin/pengguna/{$targetUser->id}/reset-sandi")->assertForbidden();
    }

    public function test_admin_dapat_melihat_daftar_dan_form_pengguna(): void
    {
        $admin = $this->createAdmin(['nama' => 'Admin Utama', 'username' => 'adminutama']);
        $operator = $this->createOperator(['nama' => 'Budi Santoso', 'username' => 'budisantoso']);
        $this->actingAs($admin);

        $responseIndex = $this->get('/admin/pengguna');
        $responseIndex->assertOk();
        $responseIndex->assertSee('Admin Utama');
        $responseIndex->assertSee('Budi Santoso');
        $responseIndex->assertSee('budisantoso');

        $responseCreate = $this->get('/admin/pengguna/baru');
        $responseCreate->assertOk();
        $responseCreate->assertSee('Tambah pengguna');

        $responseEdit = $this->get("/admin/pengguna/{$operator->id}/ubah");
        $responseEdit->assertOk();
        $responseEdit->assertSee('Ubah Budi Santoso');
    }

    public function test_admin_berhasil_membuat_pengguna_baru_dengan_password_sementara_dan_audit_log(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $payload = [
            'nama' => 'Rina Wijaya',
            'username' => 'rinawijaya',
            'peran' => 'operator',
            'aktif' => '1',
        ];

        $response = $this->post('/admin/pengguna/baru', $payload);

        $response->assertRedirect('/admin/pengguna');
        $response->assertSessionHas('success');
        $response->assertSessionHas('temp_password');

        $tempPassword = session('temp_password');
        $this->assertNotEmpty($tempPassword);
        $this->assertGreaterThanOrEqual(10, strlen($tempPassword));

        // Verifikasi pengguna tersimpan di basis data
        $newUser = User::where('username', 'rinawijaya')->firstOrFail();
        $this->assertEquals('Rina Wijaya', $newUser->nama);
        $this->assertEquals('operator', $newUser->peran);
        $this->assertTrue($newUser->aktif);

        // Verifikasi kata sandi di-hash menggunakan bcrypt/Argon2
        $this->assertTrue(Hash::check($tempPassword, $newUser->password));
        $this->assertNotEquals($tempPassword, $newUser->password);

        // Verifikasi pengguna baru dapat masuk menggunakan kata sandi sementara
        $this->post('/keluar');
        $loginAttempt = $this->post('/masuk', [
            'username' => 'rinawijaya',
            'password' => $tempPassword,
        ]);
        $loginAttempt->assertRedirect('/terbitkan');
        $this->assertAuthenticatedAs($newUser);

        // Verifikasi audit log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'aksi' => 'ubah_user',
            'target_id' => $newUser->id,
        ]);

        $log = AuditLog::where('target_id', $newUser->id)->latest('id')->firstOrFail();
        $this->assertEquals('tambah', $log->detail['tindakan']);
        $this->assertEquals('rinawijaya', $log->detail['username']);
        $this->assertEquals('Rina Wijaya', $log->detail['nama']);

        // Pastikan kata sandi TIDAK bocor ke log audit
        $this->assertArrayNotHasKey('password', $log->detail);
        $this->assertArrayNotHasKey('temp_password', $log->detail);
        $this->assertStringNotContainsString($tempPassword, json_encode($log->detail));
    }

    public function test_username_duplikat_ditolak(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        User::factory()->create(['username' => 'userkembar']);

        $response = $this->post('/admin/pengguna/baru', [
            'nama' => 'User Lain',
            'username' => 'userkembar',
            'peran' => 'operator',
            'aktif' => '1',
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_validasi_input_pengguna_menolak_data_kosong_dan_tidak_valid(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $response = $this->post('/admin/pengguna/baru', [
            'nama' => '',
            'username' => '',
            'peran' => 'peran_palsu',
        ]);

        $response->assertSessionHasErrors(['nama', 'username', 'peran']);

        // Karakter username tidak valid (spasi atau simbol terlarang)
        $responseInvalidUser = $this->post('/admin/pengguna/baru', [
            'nama' => 'Nama Benar',
            'username' => 'user with space!',
            'peran' => 'operator',
        ]);

        $responseInvalidUser->assertSessionHasErrors('username');
    }

    public function test_admin_berhasil_mengedit_pengguna_lain(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $operator = $this->createOperator([
            'nama' => 'Nama Lama',
            'username' => 'namalama',
            'peran' => 'operator',
            'aktif' => true,
        ]);

        $updatePayload = [
            'nama' => 'Nama Diperbarui',
            'username' => 'namadiperbarui',
            'peran' => 'admin',
            'aktif' => '0',
        ];

        $response = $this->post("/admin/pengguna/{$operator->id}/ubah", $updatePayload);

        $response->assertRedirect('/admin/pengguna');
        $response->assertSessionHas('success');

        $operator->refresh();
        $this->assertEquals('Nama Diperbarui', $operator->nama);
        $this->assertEquals('namadiperbarui', $operator->username);
        $this->assertEquals('admin', $operator->peran);
        $this->assertFalse($operator->aktif);

        // Verifikasi audit log
        $log = AuditLog::where('target_id', $operator->id)->latest('id')->firstOrFail();
        $this->assertEquals('ubah', $log->detail['tindakan']);
        $this->assertEquals('namadiperbarui', $log->detail['username']);
        $this->assertArrayNotHasKey('password', $log->detail);
    }

    public function test_admin_dapat_mereset_password_pengguna(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $user = $this->createOperator([
            'password' => Hash::make('password-lama-rahasia'),
            'gagal_login' => 4,
            'terkunci_sampai' => now()->addMinutes(10),
        ]);

        $response = $this->post("/admin/pengguna/{$user->id}/reset-sandi");

        $response->assertRedirect('/admin/pengguna');
        $response->assertSessionHas('success');
        $response->assertSessionHas('temp_password');

        $tempPassword = session('temp_password');
        $this->assertNotEmpty($tempPassword);

        $user->refresh();
        $this->assertTrue(Hash::check($tempPassword, $user->password));
        $this->assertEquals(0, $user->gagal_login);
        $this->assertNull($user->terkunci_sampai);

        // Verifikasi audit log
        $log = AuditLog::where('target_id', $user->id)->latest('id')->firstOrFail();
        $this->assertEquals('reset_sandi', $log->detail['tindakan']);
        $this->assertEquals($user->username, $log->detail['username']);
        $this->assertStringNotContainsString($tempPassword, json_encode($log->detail));
    }

    public function test_self_deactivation_ditolak_pada_server(): void
    {
        $admin = $this->createAdmin(['nama' => 'Admin Sedang Login']);
        $this->actingAs($admin);

        // Browser tetap mengirim hidden aktif=0 walau checkbox dinonaktifkan.
        // Penonaktifan diri ditolak, tetapi edit profil yang diizinkan tetap tersimpan.
        $response = $this->post("/admin/pengguna/{$admin->id}/ubah", [
            'nama' => 'Admin Sedang Login',
            'username' => $admin->username,
            'peran' => 'admin',
            'aktif' => '0',
        ]);

        $response->assertRedirect('/admin/pengguna');
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();

        $admin->refresh();
        $this->assertTrue($admin->aktif);
    }

    public function test_self_demotion_ditolak_pada_server(): void
    {
        $admin = $this->createAdmin(['nama' => 'Admin Sedang Login']);
        $this->actingAs($admin);

        // Mencoba memanipulasi request dengan menurunkan peran ke operator untuk akun sendiri
        $response = $this->post("/admin/pengguna/{$admin->id}/ubah", [
            'nama' => 'Admin Sedang Login',
            'username' => $admin->username,
            'peran' => 'operator',
            'aktif' => '1',
        ]);

        $response->assertSessionHasErrors('peran');

        $admin->refresh();
        $this->assertEquals('admin', $admin->peran);
    }

    public function test_last_active_admin_dilindungi_dari_penonaktifan(): void
    {
        // Hanya ada 1 admin aktif di sistem
        $lastAdmin = $this->createAdmin(['username' => 'admin_terakhir']);
        User::factory()->create(['peran' => 'admin', 'aktif' => false]);
        $this->actingAs($lastAdmin);

        // Mencoba menonaktifkan admin aktif terakhir (targetnya akun sendiri,
        // karena hanya admin aktif yang bisa login dan mengirim request).
        $response = $this->post("/admin/pengguna/{$lastAdmin->id}/ubah", [
            'nama' => $lastAdmin->nama,
            'username' => $lastAdmin->username,
            'peran' => 'admin',
            'aktif' => '0',
        ]);

        $response->assertRedirect('/admin/pengguna');
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();

        $lastAdmin->refresh();
        $this->assertTrue($lastAdmin->aktif);
    }

    public function test_last_active_admin_dilindungi_dari_penurunan_peran(): void
    {
        $lastAdmin = $this->createAdmin(['username' => 'admin_terakhir']);
        User::factory()->create(['peran' => 'admin', 'aktif' => false]);
        $this->actingAs($lastAdmin);

        // Mencoba mendemosi admin aktif terakhir menjadi operator
        $response = $this->post("/admin/pengguna/{$lastAdmin->id}/ubah", [
            'nama' => $lastAdmin->nama,
            'username' => $lastAdmin->username,
            'peran' => 'operator',
            'aktif' => '1',
        ]);

        $response->assertSessionHasErrors('peran');

        $lastAdmin->refresh();
        $this->assertEquals('admin', $lastAdmin->peran);
    }

    public function test_edit_sendiri_tanpa_peran_dan_aktif_mempertahankan_status_admin_dan_aktif(): void
    {
        $admin = $this->createAdmin(['nama' => 'Admin Form Default']);
        $this->actingAs($admin);

        // Klien boleh tidak mengirim peran/aktif; controller tetap memaksa
        // akun sendiri berperan admin dan aktif.
        $response = $this->post("/admin/pengguna/{$admin->id}/ubah", [
            'nama' => 'Admin Nama Baru',
            'username' => $admin->username,
        ]);

        $response->assertRedirect('/admin/pengguna');
        $response->assertSessionHas('success');

        $admin->refresh();
        $this->assertEquals('Admin Nama Baru', $admin->nama);
        $this->assertEquals('admin', $admin->peran);
        $this->assertTrue($admin->aktif);
    }

    public function test_self_edit_mengirim_hidden_aktif_nol_tetap_menyimpan_profil(): void
    {
        $admin = $this->createAdmin([
            'nama' => 'Admin Form Default',
            'username' => 'admin_form',
        ]);
        $this->actingAs($admin);

        // Payload persis seperti browser: select peran disabled (tidak terkirim),
        // checkbox aktif disabled tetapi hidden aktif=0 tetap terkirim.
        $response = $this->post("/admin/pengguna/{$admin->id}/ubah", [
            'nama' => 'Admin Nama Baru',
            'username' => 'admin_form_baru',
            'aktif' => '0',
        ]);

        $response->assertRedirect('/admin/pengguna');
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();

        $admin->refresh();
        $this->assertEquals('Admin Nama Baru', $admin->nama);
        $this->assertEquals('admin_form_baru', $admin->username);
        $this->assertEquals('admin', $admin->peran);
        $this->assertTrue($admin->aktif);
    }

    public function test_admin_lain_tetap_dapat_dinonaktifkan_saat_ada_admin_aktif_lain(): void
    {
        $admin = $this->createAdmin(['username' => 'admin_utama']);
        $this->actingAs($admin);

        $other = $this->createAdmin([
            'nama' => 'Admin Kedua',
            'username' => 'admin_kedua',
        ]);

        $response = $this->post("/admin/pengguna/{$other->id}/ubah", [
            'nama' => $other->nama,
            'username' => $other->username,
            'peran' => 'admin',
            'aktif' => '0',
        ]);

        $response->assertRedirect('/admin/pengguna');
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();

        $other->refresh();
        $this->assertFalse($other->aktif);
        $this->assertTrue($admin->fresh()->aktif);
    }
}
