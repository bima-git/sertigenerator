<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'username' => 'operator1',
            'nama'     => 'Budi Operator',
            'password' => Hash::make('rahasia123'),
            'peran'    => 'operator',
            'aktif'    => true,
            'gagal_login' => 0,
            'terkunci_sampai' => null,
        ]);
    }

    /**
     * Memastikan halaman login (GET /masuk) dapat diakses dengan sukses.
     */
    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Sistem Sertifikat');
        $response->assertSee('Masuk');
    }

    /**
     * Uji AU-01 & AU-03: Login API berhasil dengan kredensial valid,
     * meregenerasi sesi, dan mencatat jejak audit 'masuk'.
     */
    public function test_login_api_successful_with_valid_credentials(): void
    {
        $response = $this->postJson(route('api.login'), [
            'username' => 'operator1',
            'password' => 'rahasia123',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil masuk.',
                'user'    => [
                    'id'       => $this->user->id,
                    'username' => 'operator1',
                    'nama'     => 'Budi Operator',
                    'peran'    => 'operator',
                ],
            ]);

        $this->assertAuthenticatedAs($this->user);

        // Pastikan audit log mencatat aksi masuk
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'aksi'    => 'masuk',
        ]);
    }

    /**
     * Uji AU-01: Login dengan kata sandi salah mengembalikan status 422
     * dan menaikkan counter gagal_login.
     */
    public function test_login_fails_with_invalid_password(): void
    {
        $response = $this->postJson(route('api.login'), [
            'username' => 'operator1',
            'password' => 'passwordsalah',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);

        $this->assertGuest();

        $this->user->refresh();
        $this->assertSame(1, $this->user->gagal_login);

        // Pastikan audit log mencatat aksi gagal_masuk
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'aksi'    => 'gagal_masuk',
        ]);
    }

    /**
     * Uji AU-01: Login dengan username yang tidak terdaftar mengembalikan 422.
     */
    public function test_login_fails_with_non_existent_username(): void
    {
        $response = $this->postJson(route('api.login'), [
            'username' => 'usernametidakada',
            'password' => 'rahasia123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);

        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => null,
            'aksi'    => 'gagal_masuk',
        ]);
    }

    /**
     * Uji AU-04: Lima kegagalan berturut-turut mengunci akun selama 10 menit (HTTP 429).
     */
    public function test_five_consecutive_failures_locks_account_for_ten_minutes(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $res = $this->postJson(route('api.login'), [
                'username' => 'operator1',
                'password' => 'salah',
            ]);
            $res->assertStatus(422);
        }

        $this->user->refresh();
        $this->assertSame(4, $this->user->gagal_login);
        $this->assertNull($this->user->terkunci_sampai);

        // Percobaan ke-5
        $fifthResponse = $this->postJson(route('api.login'), [
            'username' => 'operator1',
            'password' => 'salah',
        ]);

        $fifthResponse->assertStatus(429);
        $this->assertStringContainsString('Terlalu banyak percobaan', $fifthResponse->json('message'));

        $this->user->refresh();
        $this->assertSame(5, $this->user->gagal_login);
        $this->assertNotNull($this->user->terkunci_sampai);
        $this->assertTrue(now()->diffInMinutes($this->user->terkunci_sampai) >= 9);

        // Percobaan ke-6 saat akun masih terkunci langsung ditolak 429
        $sixthResponse = $this->postJson(route('api.login'), [
            'username' => 'operator1',
            'password' => 'rahasia123', // walau password benar, tetap terkunci!
        ]);

        $sixthResponse->assertStatus(429);
        $this->assertGuest();
    }

    /**
     * Uji AU-05: Akun yang dinonaktifkan (aktif = false) ditolak masuk (HTTP 403).
     */
    public function test_inactive_user_cannot_login(): void
    {
        $this->user->update(['aktif' => false]);

        $response = $this->postJson(route('api.login'), [
            'username' => 'operator1',
            'password' => 'rahasia123',
        ]);

        $response->assertStatus(403);
        $this->assertStringContainsString('dinonaktifkan', $response->json('message'));
        $this->assertGuest();
    }

    /**
     * Uji AU-02: Logout menghapus sesi dan mencatat log audit 'keluar'.
     */
    public function test_logout_invalidates_session_and_logs_audit(): void
    {
        $this->actingAs($this->user);
        $this->assertAuthenticated();

        $response = $this->postJson(route('api.logout'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Berhasil keluar.',
            ]);

        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user->id,
            'aksi'    => 'keluar',
        ]);
    }

    /**
     * Uji API /api/me mengembalikan data profil pengguna yang sedang login.
     */
    public function test_api_me_returns_authenticated_user_profile(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson(route('api.me'));

        $response->assertOk()
            ->assertJson([
                'id'       => $this->user->id,
                'username' => 'operator1',
                'nama'     => 'Budi Operator',
                'peran'    => 'operator',
                'aktif'    => true,
            ]);
    }
}
