<?php

namespace Tests\Feature\Api;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberSuggestTest extends TestCase
{
    use RefreshDatabase;

    private Event $seminarEvent;
    private Event $bootcampEvent;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user); // semua rute selain /masuk wajib login (PRD AU-05)

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
     * Memastikan endpoint /api/nomor mengembalikan saran nomor untuk kegiatan seminar.
     */
    public function test_api_nomor_returns_suggestion_for_seminar(): void
    {
        $response = $this->getJson(route('api.number', [
            'event_id' => $this->seminarEvent->id,
            'seri' => 1,
            'tanggal_terbit' => '2026-10-24',
        ]));

        $response->assertOk()
            ->assertJson([
                'bernomor' => true,
                'urut_saran' => 1,
                'urut_aktif' => 1,
                'nomor_lengkap' => '35/001/001/Xcodetraining/X/2026',
                'kode' => '35',
                'penerbit' => 'Xcodetraining',
                'seri' => 1,
                'tahun' => 2026,
                'is_available' => true,
            ]);
    }

    /**
     * Memastikan /api/nomor otomatis menaikkan nomor urut jika sertifikat sudah ada.
     */
    public function test_api_nomor_increments_when_certificate_already_exists(): void
    {
        Certificate::create([
            'event_id' => $this->seminarEvent->id,
            'jenis' => 'seminar',
            'seri' => 1,
            'urut' => 1,
            'tahun' => 2026,
            'nomor_lengkap' => '35/001/001/Xcodetraining/X/2026',
            'nama_peserta' => 'Peserta Satu',
            'tanggal_terbit' => '2026-10-24',
            'data_cetak' => ['judul' => $this->seminarEvent->judul],
            'versi_layout' => '1.0',
            'status' => 'terbit',
            'dibuat_oleh' => $this->user->id,
        ]);

        $response = $this->getJson(route('api.number', [
            'event_id' => $this->seminarEvent->id,
            'seri' => 1,
            'tanggal_terbit' => '2026-10-24',
        ]));

        $response->assertOk()
            ->assertJson([
                'bernomor' => true,
                'urut_saran' => 2,
                'nomor_lengkap' => '35/001/002/Xcodetraining/X/2026',
            ]);
    }

    /**
     * Memastikan /api/nomor mendeteksi jika nomor manual yang diinput operator sudah terpakai.
     */
    public function test_api_nomor_detects_manual_urut_availability(): void
    {
        Certificate::create([
            'event_id' => $this->seminarEvent->id,
            'jenis' => 'seminar',
            'seri' => 1,
            'urut' => 3,
            'tahun' => 2026,
            'nomor_lengkap' => '35/001/003/Xcodetraining/X/2026',
            'nama_peserta' => 'Peserta Tiga',
            'tanggal_terbit' => '2026-10-24',
            'data_cetak' => ['judul' => $this->seminarEvent->judul],
            'versi_layout' => '1.0',
            'status' => 'terbit',
            'dibuat_oleh' => $this->user->id,
        ]);

        // Cek nomor urut 3 yang sudah terpakai
        $responseTaken = $this->getJson(route('api.number', [
            'event_id' => $this->seminarEvent->id,
            'seri' => 1,
            'urut' => 3,
            'tanggal_terbit' => '2026-10-24',
        ]));

        $responseTaken->assertOk()
            ->assertJson([
                'urut_aktif' => 3,
                'is_available' => false,
            ]);

        // Cek nomor urut 5 yang masih bebas
        $responseAvailable = $this->getJson(route('api.number', [
            'event_id' => $this->seminarEvent->id,
            'seri' => 1,
            'urut' => 5,
            'tanggal_terbit' => '2026-10-24',
        ]));

        $responseAvailable->assertOk()
            ->assertJson([
                'urut_aktif' => 5,
                'is_available' => true,
            ]);
    }

    /**
     * Memastikan /api/nomor mengembalikan status bernomor false untuk kegiatan bootcamp.
     */
    public function test_api_nomor_returns_null_fields_for_bootcamp(): void
    {
        $response = $this->getJson(route('api.number', [
            'event_id' => $this->bootcampEvent->id,
            'tanggal_terbit' => '2026-10-24',
        ]));

        $response->assertOk()
            ->assertJson([
                'bernomor' => false,
                'urut_saran' => null,
                'nomor_lengkap' => null,
                'kode' => '15',
                'is_available' => true,
            ]);
    }

    /**
     * Memastikan validasi request menolak event_id yang tidak ada.
     */
    public function test_api_nomor_validates_required_and_existing_event(): void
    {
        $this->getJson(route('api.number'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['event_id']);

        $this->getJson(route('api.number', ['event_id' => 99999]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['event_id']);
    }

    /**
     * Memastikan endpoint /api/kegiatan mengembalikan daftar kegiatan aktif dan mendukung filter.
     */
    public function test_api_kegiatan_returns_list_and_filters(): void
    {
        // Event nonaktif tidak boleh muncul
        Event::create([
            'kode' => 'NONAKTIF',
            'judul' => 'Event Lama',
            'jenis' => 'seminar',
            'aktif' => false,
        ]);

        // Uji ambil semua kegiatan aktif
        $responseAll = $this->getJson(route('api.events'));
        $responseAll->assertOk()
            ->assertJsonCount(2);

        // Uji filter jenis = seminar
        $responseSeminar = $this->getJson(route('api.events', ['jenis' => 'seminar']));
        $responseSeminar->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['kode' => '35']);

        // Uji filter pencarian q
        $responseSearch = $this->getJson(route('api.events', ['q' => 'Hermes']));
        $responseSearch->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['kode' => '35']);
    }
}
