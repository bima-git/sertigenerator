<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetKataSandi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Login dengan email dan lupa/reset kata sandi lewat email (rencana tim 2026-11-27).
 */
class EmailAuthUiTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attr = []): User
    {
        return User::factory()->create($attr + ['email' => 'rina@contoh.test', 'password' => 'rahasia-panjang-1']);
    }

    public function test_halaman_masuk_menampilkan_email_atau_username_dan_tautan_lupa_sandi(): void
    {
        $this->get('/masuk')->assertOk()->assertSee('Email atau username')->assertSee('Lupa kata sandi?');
    }

    public function test_masuk_dengan_email_tanpa_peka_huruf_besar_atau_dengan_username(): void
    {
        $u = $this->user();

        $this->post('/masuk', ['username' => '  RINA@Contoh.test ', 'password' => 'rahasia-panjang-1'])->assertRedirect('/terbitkan');
        $this->assertAuthenticatedAs($u);
        $this->post('/keluar');

        $this->post('/masuk', ['username' => $u->username, 'password' => 'rahasia-panjang-1'])->assertRedirect('/terbitkan');
        $this->assertAuthenticatedAs($u);
    }

    public function test_akun_lama_tanpa_email_tetap_bisa_masuk_dengan_username(): void
    {
        $u = $this->user(['email' => null]);

        $this->post('/masuk', ['username' => $u->username, 'password' => 'rahasia-panjang-1'])->assertRedirect('/terbitkan');
        $this->assertAuthenticatedAs($u);
    }

    public function test_lupa_sandi_mengirim_email_ke_akun_aktif_dengan_pesan_yang_sama_untuk_semua(): void
    {
        Notification::fake();
        $aktif = $this->user();
        $nonaktif = $this->user(['email' => 'mati@contoh.test', 'aktif' => false]);

        foreach (['RINA@contoh.test', 'mati@contoh.test', 'tidak-ada@contoh.test'] as $email) {
            $this->from('/lupa-sandi')->post('/lupa-sandi', ['email' => $email])
                ->assertRedirect('/lupa-sandi')->assertSessionHas('status');
        }

        Notification::assertSentTo($aktif, ResetKataSandi::class, function ($n) use ($aktif) {
            $mail = $n->toMail($aktif);

            return str_contains($mail->actionUrl, '/reset-sandi/') && $mail->subject === 'Reset kata sandi Sistem Sertifikat XCODE';
        });
        Notification::assertNotSentTo($nonaktif, ResetKataSandi::class);
        $this->get('/lupa-sandi')->assertSee('Kirim Tautan Reset');
    }

    public function test_reset_sandi_dengan_token_sah_mengganti_kata_sandi_dan_membuka_kunci(): void
    {
        $u = $this->user(['gagal_login' => 5, 'terkunci_sampai' => now()->addMinutes(10)]);
        $token = Password::createToken($u);

        $this->get("/reset-sandi/{$token}?email=rina@contoh.test")->assertOk()->assertSee('Simpan Kata Sandi');

        $this->post('/reset-sandi', [
            'token' => $token, 'email' => 'rina@contoh.test',
            'password' => 'sandi-baru-12345', 'password_confirmation' => 'sandi-baru-12345',
        ])->assertRedirect('/masuk')->assertSessionHas('status');

        $u->refresh();
        $this->assertTrue(Hash::check('sandi-baru-12345', $u->password));
        $this->assertSame(0, $u->gagal_login);
        $this->assertNull($u->terkunci_sampai);
        $this->assertDatabaseHas('audit_logs', ['aksi' => 'reset_sandi', 'user_id' => $u->id]);
    }

    public function test_reset_sandi_menolak_token_salah_dan_sandi_pendek(): void
    {
        $u = $this->user();
        $token = Password::createToken($u);

        $this->from("/reset-sandi/{$token}")->post('/reset-sandi', [
            'token' => $token, 'email' => 'rina@contoh.test', 'password' => 'pendek', 'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');

        $this->from('/reset-sandi/salah')->post('/reset-sandi', [
            'token' => 'salah', 'email' => 'rina@contoh.test',
            'password' => 'sandi-baru-12345', 'password_confirmation' => 'sandi-baru-12345',
        ])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('rahasia-panjang-1', $u->fresh()->password));

        $this->withSession(['errors' => (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag(['email' => ['Tautan reset tidak berlaku lagi.']]))])
            ->get('/reset-sandi/salah?email=rina@contoh.test')->assertSee('Minta tautan baru');
    }

    public function test_halaman_lupa_sandi_hanya_untuk_tamu(): void
    {
        $this->actingAs($this->user())->get('/lupa-sandi')->assertRedirect('/terbitkan');
    }

    public function test_admin_mengisi_email_pengguna_unik_dan_disimpan_huruf_kecil(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@contoh.test']);
        $lama = $this->user(['email' => null, 'peran' => 'operator']);
        $this->actingAs($admin);

        $this->get(route('admin.users.edit', $lama->id))->assertOk()->assertSee('belum punya email');
        $this->get(route('admin.users.index'))->assertOk()->assertSee('Belum ada email');

        $this->post("/admin/pengguna/{$lama->id}/ubah", ['nama' => $lama->nama, 'username' => $lama->username, 'email' => 'admin@contoh.test', 'peran' => 'operator', 'aktif' => '1'])
            ->assertSessionHasErrors('email');
        $this->post("/admin/pengguna/{$lama->id}/ubah", ['nama' => $lama->nama, 'username' => $lama->username, 'email' => ' Lama@Contoh.TEST ', 'peran' => 'operator', 'aktif' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertSame('lama@contoh.test', $lama->fresh()->email);

        $this->post('/admin/pengguna/baru', ['nama' => 'Tanpa Email', 'username' => 'tanpa.email', 'peran' => 'operator'])->assertSessionHasNoErrors();
        $this->assertNull(User::where('username', 'tanpa.email')->value('email'));
    }
}
