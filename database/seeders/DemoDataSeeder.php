<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use App\Services\HistoryService;
use App\Services\IssueService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Data contoh untuk pengembangan dan demo: 2 operator dan 24 sertifikat dalam 6 bulan terakhir
 * (2 di antaranya dibatalkan). Sertifikat dibuat lewat IssueService/HistoryService asli, jadi
 * nomor, data cetak, dan audit log sama seperti hasil penerbitan dari form.
 *
 * Tidak berjalan di production dan tidak berjalan ulang bila sudah ada sertifikat.
 * Kata sandi operator: DEMO_PASSWORD di .env, atau dibuat acak dan dicetak sekali di terminal.
 */
class DemoDataSeeder extends Seeder
{
    private const NAMA = [
        "Mu'ammal Hamidy", 'Siti Nurhaliza Putri', 'Andi Pratama', 'Rizky Maulana', 'Dewi Lestari',
        'Bagas Saputra', 'Nadia Rahmawati', 'Fajar Hidayat', 'Putri Ayu Ningsih', 'Yoga Aditya',
        'Rina Kartika', 'Ahmad Fauzi', 'Larasati Widya', 'Galih Permana', 'Intan Permatasari',
        'Hendra Gunawan', 'Salsabila Zahra', 'Taufik Ramadhan', 'Wulan Sari', 'Arif Budiman',
        'Citra Anggraini', 'Eko Prasetyo', 'Melati Kusuma', 'Bayu Nugroho',
    ];

    private const PERUSAHAAN = ['PT Teknologi Server Indonesia', 'PT Siber Nusantara', 'CV Digital Kreatif Cilacap'];

    public function run(IssueService $issue, HistoryService $history): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoDataSeeder dilewati di production.');

            return;
        }
        if (Certificate::query()->exists()) {
            $this->command?->warn('Sudah ada sertifikat, DemoDataSeeder dilewati supaya data tidak dobel.');

            return;
        }

        $admin = User::query()->where('peran', 'admin')->where('aktif', true)->first();
        if (! $admin) {
            $this->command?->error('Belum ada admin aktif. Jalankan AdminUserSeeder dulu.');

            return;
        }

        $pembuat = [$admin, ...$this->operators()];
        $seminar = Event::query()->aktif()->where('jenis', 'seminar')->where('bernomor', true)->orderBy('id')->take(6)->get();
        $bootcamp = Event::query()->aktif()->where('jenis', 'bootcamp')->orderBy('id')->take(2)->get();
        if ($seminar->isEmpty()) {
            $this->command?->error('Belum ada kegiatan seminar aktif. Jalankan EventSeeder dulu.');

            return;
        }

        $terbit = [];
        foreach (self::NAMA as $i => $nama) {
            // Sebar mundur 0-5 bulan, beberapa sertifikat per bulan.
            $tanggal = Carbon::now('Asia/Jakarta')->startOfMonth()->subMonths($i % 6)->addDays(($i * 3) % 25);
            if ($tanggal->isFuture()) {
                $tanggal = Carbon::now('Asia/Jakarta');
            }
            $user = $pembuat[$i % count($pembuat)];
            $pakaiBootcamp = $bootcamp->isNotEmpty() && $i % 5 === 4;

            $data = $pakaiBootcamp
                ? $this->bootcampData($bootcamp[$i % $bootcamp->count()], $nama, $tanggal, $i)
                : ['jenis' => 'seminar', 'event_id' => $seminar[$i % $seminar->count()]->id, 'nama_peserta' => $nama,
                    'tanggal_terbit' => $tanggal->toDateString(), 'seri' => 1];

            $c = $issue->issue($data, $user)['certificate'];
            // Waktu terbit di daftar Riwayat mengikuti tanggal terbit, bukan waktu seeder dijalankan.
            $waktu = $tanggal->copy()->setTime(8 + $i % 9, ($i * 7) % 60);
            Certificate::query()->whereKey($c->id)->update(['created_at' => $waktu, 'updated_at' => $waktu]);
            $terbit[] = $c;
        }

        foreach ([$terbit[2], $terbit[9]] as $c) {
            $history->cancel($c, 'Salah ketik nama peserta; diterbitkan ulang dengan nama yang benar.', $admin);
        }

        $this->command?->info('DemoDataSeeder selesai: '.count($terbit).' sertifikat contoh (2 dibatalkan).');
    }

    /** @return list<User> */
    private function operators(): array
    {
        $password = (string) env('DEMO_PASSWORD', '');
        $acak = $password === '';
        if ($acak) {
            $password = Str::password(14, symbols: false);
        }

        $hasil = [];
        foreach ([['operator1', 'Rina Wulandari'], ['operator2', 'Dimas Prasetyo']] as [$username, $nama]) {
            $user = User::query()->firstOrCreate(['username' => $username], [
                'nama' => $nama, 'email' => "{$username}@contoh.test", 'password' => Hash::make($password), 'peran' => 'operator', 'aktif' => true,
            ]);
            if ($user->wasRecentlyCreated) {
                $this->command?->info("Operator contoh \"{$username}\" dibuat.");
            }
            $hasil[] = $user;
        }

        if ($acak && collect($hasil)->contains->wasRecentlyCreated) {
            $this->command?->warn("Kata sandi operator1 dan operator2: {$password}  (tampil sekali, atur DEMO_PASSWORD di .env untuk nilai tetap)");
        }

        return $hasil;
    }

    private function bootcampData(Event $event, string $nama, Carbon $tanggal, int $i): array
    {
        return [
            'jenis' => 'bootcamp', 'event_id' => $event->id, 'nama_peserta' => $nama,
            'tanggal_terbit' => $tanggal->toDateString(),
            'program' => $event->program_default ?: 'Penetration Testing & Cyber Security Engineer',
            'perusahaan' => self::PERUSAHAAN[$i % count(self::PERUSAHAAN)],
            'tanggal_mulai' => $tanggal->copy()->subMonths(3)->toDateString(),
            'tanggal_selesai' => $tanggal->copy()->subDays(3)->toDateString(),
        ];
    }
}
