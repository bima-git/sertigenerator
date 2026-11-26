<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Butir PRD yang dilengkapi setelah analisis 2026-11-26:
 * FM-10, FM-11, KG-04, KG-07, RW-02, PD-05/PD-06, AD-03.
 */
class PrdCompletionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Event $seminar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->seminar = Event::create([
            'kode' => '35', 'judul' => 'Koding Laravel: 20x Lebih Cepat dengan Hermes AI', 'jenis' => 'seminar',
            'penerbit' => 'Xcodetraining', 'bernomor' => true, 'pakai_qr' => true, 'aktif' => true,
        ]);
        $this->actingAs($this->admin);
    }

    private function payload(array $extra = []): array
    {
        return $extra + [
            'jenis' => 'seminar', 'event_id' => $this->seminar->id,
            'nama_peserta' => 'Budi Santoso', 'tanggal_terbit' => '2026-10-24', 'seri' => 1,
        ];
    }

    /** Nama yang tidak muat bahkan di ukuran minimum dua baris. */
    private function namaKepanjangan(): string
    {
        return trim(str_repeat('Wwwwwwwww ', 10));
    }

    public function test_fm11_nama_sama_pada_kegiatan_yang_sama_dilaporkan_dengan_nomornya(): void
    {
        $this->post(route('issue.store'), $this->payload())->assertOk();
        $lain = Event::create(['kode' => '36', 'judul' => 'Lain', 'jenis' => 'seminar', 'penerbit' => 'Xcodetraining', 'bernomor' => true, 'pakai_qr' => true, 'aktif' => true]);

        $this->getJson(route('api.same-name', ['event_id' => $this->seminar->id, 'nama' => '  budi   SANTOSO ']))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.nomor', '35/001/001/Xcodetraining/X/2026');
        $this->getJson(route('api.same-name', ['event_id' => $lain->id, 'nama' => 'Budi Santoso']))->assertOk()->assertJsonCount(0);
    }

    public function test_fm11_sertifikat_dibatalkan_tidak_dihitung_nama_sama(): void
    {
        $this->post(route('issue.store'), $this->payload())->assertOk();
        Certificate::query()->update(['status' => 'dibatalkan']);

        $this->getJson(route('api.same-name', ['event_id' => $this->seminar->id, 'nama' => 'Budi Santoso']))->assertJsonCount(0);
    }

    public function test_pd05_nama_tidak_muat_ditolak_sebagai_galat_isian_dan_tidak_menyimpan(): void
    {
        $this->postJson(route('issue.store'), $this->payload(['nama_peserta' => $this->namaKepanjangan()]))
            ->assertStatus(422)->assertJsonValidationErrors('nama_peserta');

        $this->assertSame(0, Certificate::count());
    }

    public function test_pd06_cek_teks_memberi_tahu_sebelum_terbit(): void
    {
        $this->postJson(route('api.text-check'), $this->payload())->assertOk()->assertJson(['muat' => true]);
        $this->postJson(route('api.text-check'), $this->payload(['nama_peserta' => $this->namaKepanjangan()]))
            ->assertOk()->assertJson(['muat' => false, 'field' => 'nama_peserta']);
        $this->assertSame(0, Certificate::count());
    }

    public function test_fm10_form_menampilkan_ringkasan_kesalahan_dari_server(): void
    {
        $this->withSession(['errors' => (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag(['nama_peserta' => ['Nama wajib diisi.']]))])
            ->get(route('issue.form'))
            ->assertOk()->assertSee('Ada isian yang perlu diperbaiki')->assertSee('Nama wajib diisi.');
    }

    public function test_kg04_form_kegiatan_menyiapkan_konfirmasi_kode_yang_sudah_dipakai(): void
    {
        $this->get(route('admin.events.create'))->assertOk()
            ->assertSee('data-kode-dipakai', false)
            ->assertSee('Koding Laravel: 20x Lebih Cepat dengan Hermes AI');
        // Saat mengubah kegiatan itu sendiri, kodenya tidak dianggap dipakai kegiatan lain.
        $this->get(route('admin.events.edit', $this->seminar->id))->assertOk()
            ->assertDontSee('"kode":"35"', false);
    }

    public function test_kg07_daftar_kegiatan_disaring_menurut_status(): void
    {
        Event::create(['kode' => '99', 'judul' => 'Kegiatan Lama Nonaktif', 'jenis' => 'seminar', 'penerbit' => 'Xcodetraining', 'bernomor' => true, 'pakai_qr' => true, 'aktif' => false]);

        $this->get('/admin/kegiatan?status=nonaktif')->assertOk()->assertSee('Kegiatan Lama Nonaktif')->assertDontSee('Koding Laravel');
        $this->get('/admin/kegiatan?status=aktif')->assertOk()->assertSee('Koding Laravel')->assertDontSee('Kegiatan Lama Nonaktif');
    }

    public function test_rw02_riwayat_dicari_menurut_kegiatan(): void
    {
        $this->post(route('issue.store'), $this->payload())->assertOk();

        // Diperiksa dari daftar hasil, karena nama juga muncul di panel Aktivitas terbaru.
        $jumlah = fn (int $n) => fn ($list) => $list->total() === $n;
        $this->get('/riwayat?q=Hermes')->assertOk()->assertViewHas('certificates', $jumlah(1));
        $this->get('/riwayat?q=35')->assertOk()->assertViewHas('certificates', $jumlah(1));
        $this->get('/riwayat?q=Python')->assertOk()->assertViewHas('certificates', $jumlah(0));
    }

    public function test_ad03_galat_sistem_menampilkan_kode_rujukan(): void
    {
        config(['app.debug' => false]);
        Route::middleware('web')->get('/uji-galat', fn () => throw new \RuntimeException('meledak'));

        $this->get('/uji-galat')->assertStatus(500)->assertSee('Kode rujukan');
    }
}
