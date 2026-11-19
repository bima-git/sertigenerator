<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PRD AU-01 sampai AU-07 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attr = []): User
    {
        return User::factory()->create(['password' => 'rahasia-panjang-1'] + $attr);
    }

    public function test_masuk_berhasil_lalu_keluar(): void
    {
        $u = $this->user();

        $this->post('/masuk', ['username' => $u->username, 'password' => 'rahasia-panjang-1'])->assertRedirect('/terbitkan');
        $this->assertAuthenticatedAs($u);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $u->id, 'aksi' => 'masuk']);

        $this->post('/keluar')->assertRedirect('/masuk');
        $this->assertGuest();
        $this->get('/riwayat')->assertRedirect('/masuk');
    }

    public function test_lima_kali_gagal_mengunci_akun_sepuluh_menit(): void
    {
        $u = $this->user();

        foreach (range(1, 5) as $_) {
            $this->post('/masuk', ['username' => $u->username, 'password' => 'salah'])->assertSessionHasErrors('username');
        }
        $this->assertTrue($u->fresh()->terkunci_sampai->between(now()->addMinutes(9), now()->addMinutes(10)));

        // Kata sandi benar pun ditolak selama terkunci.
        $this->post('/masuk', ['username' => $u->username, 'password' => 'rahasia-panjang-1'])
            ->assertSessionHasErrors(['username' => 'Terlalu banyak percobaan. Coba lagi dalam 10 menit.']);
        $this->assertGuest();
    }

    public function test_akun_nonaktif_tidak_bisa_masuk_dan_sesinya_ditolak(): void
    {
        $u = $this->user(['aktif' => false]);
        $this->post('/masuk', ['username' => $u->username, 'password' => 'rahasia-panjang-1'])->assertSessionHasErrors('username');
        $this->assertGuest();

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
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('sandi-baru-12', $u->fresh()->password));
    }
}
