<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Proteksi halaman dan akun (PRD AU-02, AU-05, AU-06, AU-07).
 * Logika masuk/keluar sendiri diuji di AuthTest.
 */
class AccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attr = []): User
    {
        return User::factory()->create(['password' => 'rahasia-panjang-1'] + $attr);
    }

    public function test_tanpa_sesi_semua_halaman_mengarah_ke_masuk(): void
    {
        foreach (['/terbitkan', '/riwayat', '/akun', '/admin/pengguna', '/api/nomor'] as $url) {
            $this->get($url)->assertRedirect('/masuk');
        }
    }

    public function test_masuk_lewat_form_lalu_keluar(): void
    {
        $u = $this->user();

        $this->post('/masuk', ['username' => $u->username, 'password' => 'rahasia-panjang-1'])->assertRedirect('/terbitkan');
        $this->assertAuthenticatedAs($u);

        $this->post('/keluar')->assertRedirect('/masuk');
        $this->assertGuest();
        $this->get('/riwayat')->assertRedirect('/masuk');
    }

    public function test_sesi_akun_yang_dinonaktifkan_ditolak_pada_permintaan_berikutnya(): void
    {
        $u = $this->user(['aktif' => false]);

        $this->actingAs($u)->get('/terbitkan')->assertRedirect('/masuk');
        $this->assertGuest();
    }

    public function test_operator_ditolak_di_halaman_admin(): void
    {
        $this->actingAs($this->user(['peran' => 'operator']))->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($this->user(['peran' => 'admin']))->get('/admin/pengguna')->assertOk();
    }

    public function test_ganti_kata_sandi_minimal_sepuluh_karakter(): void
    {
        $u = $this->user();
        $this->actingAs($u);

        $this->post('/akun/sandi', ['password_lama' => 'rahasia-panjang-1', 'password' => 'pendek', 'password_confirmation' => 'pendek'])
            ->assertSessionHasErrors('password');
        $this->post('/akun/sandi', ['password_lama' => 'rahasia-panjang-1', 'password' => 'sandi-baru-12', 'password_confirmation' => 'sandi-baru-12'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('sandi-baru-12', $u->fresh()->password));
    }
}
