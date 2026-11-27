<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tampilan login email dan lupa/reset kata sandi (bagian Giska dari rencana login email).
 * Backend (kolom email: Akmal; rute POST reset: Bima) belum ada, jadi diuji dua keadaan:
 * sebelum (tampilan aman, mengarah ke Admin) dan sesudah (form aktif).
 */
class EmailAuthUiTest extends TestCase
{
    use RefreshDatabase;

    /** Tiruan rute POST milik modul Bima, dengan nama bawaan Laravel. */
    private function rutePostReset(): void
    {
        Route::middleware('web')->post('/lupa-sandi', fn () => back()->with('status', 'passwords.sent'))->name('password.email');
        Route::middleware('web')->post('/reset-sandi', fn () => redirect('/masuk'))->name('password.update');
        app('router')->getRoutes()->refreshNameLookups();
    }

    public function test_sebelum_backend_siap_login_tetap_username_tanpa_tautan_lupa_sandi(): void
    {
        $this->get('/masuk')->assertOk()->assertSee('Username')->assertDontSee('Lupa kata sandi?');
        $this->get('/lupa-sandi')->assertOk()->assertSee('belum aktif')->assertDontSee('Kirim Tautan Reset');
        $this->get('/reset-sandi/token-uji?email=a@b.id')->assertOk()->assertSee('belum aktif');
    }

    public function test_label_login_email_aktif_lewat_konfigurasi(): void
    {
        config(['auth.login_email' => true]);

        $this->get('/masuk')->assertOk()->assertSee('Email atau username');
    }

    public function test_setelah_rute_reset_ada_form_lupa_dan_reset_aktif(): void
    {
        $this->rutePostReset();

        $this->get('/masuk')->assertOk()->assertSee('Lupa kata sandi?');
        $this->get('/lupa-sandi')->assertOk()->assertSee('Kirim Tautan Reset');
        $this->get('/reset-sandi/token-uji?email=a@b.id')->assertOk()
            ->assertSee('value="token-uji"', false)
            ->assertSee('value="a@b.id"', false)
            ->assertSee('Simpan Kata Sandi');

        // Pesan setelah kirim tidak membocorkan apakah email terdaftar.
        $this->withSession(['status' => 'passwords.sent'])->get('/lupa-sandi')->assertSee('Jika email itu terdaftar');
    }

    public function test_tautan_reset_kedaluwarsa_menawarkan_tautan_baru(): void
    {
        $this->rutePostReset();

        $this->withSession(['errors' => (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag(['email' => ['Token reset tidak valid.']]))])
            ->get('/reset-sandi/lama?email=a@b.id')
            ->assertOk()->assertSee('Token reset tidak valid.')->assertSee('Minta tautan baru');
    }

    public function test_halaman_lupa_sandi_hanya_untuk_tamu(): void
    {
        $this->actingAs(User::factory()->create())->get('/lupa-sandi')->assertRedirect('/terbitkan');
    }

    public function test_kolom_email_pengguna_muncul_setelah_migrasi_email(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->get(route('admin.users.create'))->assertOk()->assertDontSee('name="email"', false);

        Schema::table('users', fn (Blueprint $t) => $t->string('email')->nullable());

        $this->get(route('admin.users.create'))->assertOk()->assertSee('name="email"', false);
        $this->get(route('admin.users.edit', $admin->id))->assertOk()->assertSee('belum punya email');
        $this->get(route('admin.users.index'))->assertOk()->assertSee('Belum ada email');
        $this->get('/akun')->assertOk()->assertSee('Belum ada email');
    }
}
