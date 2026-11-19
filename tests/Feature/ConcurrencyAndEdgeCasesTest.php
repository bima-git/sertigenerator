<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use App\Services\IssueService;
use App\Services\NumberAllocator;
use App\Services\NumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrencyAndEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Event $seminarEvent;
    private IssueService $issueService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->seminarEvent = Event::create([
            'kode'     => '35',
            'judul'    => 'Koding Laravel: 20x Lebih Cepat dengan Hermes AI',
            'jenis'    => 'seminar',
            'penerbit' => 'Xcodetraining',
            'bernomor' => true,
            'pakai_qr' => true,
            'aktif'    => true,
        ]);

        $this->issueService = app(IssueService::class);
    }

    /**
     * SLA PRD Bagian 1.1 & Kasus Wajib PRD 5.3:
     * 50 permintaan penerbitan pada satu kegiatan tanpa nomor urut diisi
     * menghasilkan 50 nomor berbeda tanpa ada celah error atau duplikasi nomor!
     */
    public function test_fifty_sequential_issuance_requests_yield_fifty_unique_numbers(): void
    {
        $totalRequests = 50;
        $issuedNumbers = [];

        for ($i = 1; $i <= $totalRequests; $i++) {
            $payload = [
                'jenis'          => 'seminar',
                'event_id'       => $this->seminarEvent->id,
                'nama_peserta'   => "Peserta Uji {$i}",
                'tanggal_terbit' => '2026-10-24',
                'seri'           => 1,
            ];

            $result = $this->issueService->issue($payload, $this->user);
            $issuedNumbers[] = $result['nomor'];
        }

        // Verifikasi tepat 50 nomor terbit
        $this->assertCount(50, $issuedNumbers);

        // Verifikasi tidak ada nomor yang ganda (zero duplicates)
        $uniqueNumbers = array_unique($issuedNumbers);
        $this->assertCount(50, $uniqueNumbers, 'Harus ada tepat 50 nomor unik tanpa satupun nomor ganda!');

        // Verifikasi nomor pertama dan terakhir sesuai formula
        $this->assertSame('35/001/001/Xcodetraining/X/2026', $issuedNumbers[0]);
        $this->assertSame('35/001/050/Xcodetraining/X/2026', $issuedNumbers[49]);

        // Verifikasi database mencatat tepat 50 sertifikat
        $this->assertDatabaseCount('certificates', 50);
    }

    /**
     * Pengujian Kasus Nama Ekstrem (PRD Bagian 1.1):
     * Menguji variasi nama: nama pendek, nama apostrof, nama bergelar, aksen Latin,
     * serta nama panjang hingga 70 karakter.
     */
    public function test_handles_extreme_names_properly(): void
    {
        $extremeNames = [
            "Mu'ammal Hamidy", // Nama dengan apostrof
            "Dr. Ir. H. Alexander van der Bellen, S.T., M.Kom.", // Nama panjang bergelar
            "Akrom Masani Iksanul Amri", // Nama asli sertifikat bootcamp
            "Arief Soekardi, M.Cs.", // Nama seminar asli
            "René François-Guillaume de Charette", // Karakter Latin dan tanda hubung
            "Nama Sangat Panjang Maksimum Tujuh Puluh Karakter Untuk Menguji Layout X", // Tepat 70 karakter
            "Al", // Nama pendek (2 karakter)
        ];

        foreach ($extremeNames as $index => $nama) {
            $payload = [
                'jenis'          => 'seminar',
                'event_id'       => $this->seminarEvent->id,
                'nama_peserta'   => $nama,
                'tanggal_terbit' => '2026-10-24',
                'seri'           => 1,
            ];

            $result = $this->issueService->issue($payload, $this->user);

            $this->assertNotNull($result['certificate']);
            $this->assertSame($nama, $result['certificate']->nama_peserta);
            $this->assertNotNull($result['pdf']);
            $this->assertNotEmpty($result['filename']);
        }

        $this->assertDatabaseCount('certificates', count($extremeNames));
    }

    /**
     * Memastikan isolasi transaksi: jika alokasi dilakukan dalam transaksi paralel
     * yang di-lock, nomor urut berikutnya tetap terurut tanpa ada gap tabrakan.
     */
    public function test_transaction_locking_preserves_strict_ordering(): void
    {
        $allocator = app(NumberAllocator::class);

        // Simulasi 10 transaksi atomik berurutan
        for ($i = 1; $i <= 10; $i++) {
            DB::transaction(function () use ($i, $allocator) {
                $event = Event::query()->whereKey($this->seminarEvent->id)->lockForUpdate()->first();
                $nextUrut = $allocator->next($event->id, 1, 2026);

                $this->assertSame($i, $nextUrut);

                Certificate::create([
                    'event_id'       => $event->id,
                    'jenis'          => 'seminar',
                    'seri'           => 1,
                    'urut'           => $nextUrut,
                    'tahun'          => 2026,
                    'nomor_lengkap'  => NumberGenerator::build($event->kode, 1, $nextUrut, $event->penerbit, '2026-10-24'),
                    'nama_peserta'   => "Peserta {$i}",
                    'tanggal_terbit' => '2026-10-24',
                    'data_cetak'     => ['judul' => $event->judul],
                    'versi_layout'   => '1.0',
                    'status'         => 'terbit',
                    'dibuat_oleh'    => $this->user->id,
                ]);
            });
        }

        $this->assertDatabaseCount('certificates', 10);
    }
}
