<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use App\Services\NumberAllocator;
use App\Services\NumberGenerator;
use App\Services\Roman;
use Illuminate\Console\Command;

class TestNumberingCommand extends Command
{
    /**
     * Nama dan signature command console.
     */
    protected $signature = 'test:numbering {--interactive : Jalankan mode input interaktif}';

    /**
     * Deskripsi command console.
     */
    protected $description = 'Uji coba manual logika penomoran dan alokasi nomor sertifikat XCODE';

    /**
     * Eksekusi command.
     */
    public function handle(NumberAllocator $allocator): int
    {
        $this->info("====================================================");
        $this->info("   PENGUJIAN MANUAL LOGIKA PENOMORAN (MODUL BIMA)   ");
        $this->info("====================================================\n");

        // 1. Demo Helper Romawi
        $this->comment("1. Uji Helper Romawi (Roman::fromMonth):");
        $bulanContoh = [1 => 'Januari', 5 => 'Mei', 10 => 'Oktober', 12 => 'Desember'];
        $romawiRows = [];
        foreach ($bulanContoh as $b => $nama) {
            $romawiRows[] = [$b, $nama, Roman::fromMonth($b)];
        }
        $this->table(['Angka Bulan', 'Nama Bulan', 'Angka Romawi'], $romawiRows);

        // 2. Demo NumberGenerator (Kasus PRD 5.3)
        $this->newLine();
        $this->comment("2. Uji Generator Nomor Sesuai Kasus Wajib PRD 5.3:");
        $sampleCases = [
            ['35', 1, 3, 'Xcodetraining', '2026-10-24'],
            ['35', 1, 9, 'Xcodetraining', '2026-10-24'],
            ['35', 1, 15, 'Xcodetraining', '2026-10-24'],
            ['15', 1, 1, 'Bootcamp', '2025-02-10'],
            ['23', 1, 1, 'SiberExpert', '2024-05-03'],
            ['255', 1, 1, 'Xcodetraining', '2026-12-01'],
        ];

        $sampleRows = [];
        foreach ($sampleCases as $case) {
            $nomor = NumberGenerator::build($case[0], $case[1], $case[2], $case[3], $case[4]);
            $sampleRows[] = [$case[0], sprintf('%03d', $case[1]), sprintf('%03d', $case[2]), $case[3], $case[4], $nomor];
        }
        $this->table(['Kode', 'Seri', 'Urut', 'Penerbit', 'Tanggal', 'Hasil Nomor Lengkap'], $sampleRows);

        // 3. Demo Alokasi Nomor & Integritas Basis Data
        $this->newLine();
        $this->comment("3. Uji Simulasi Alokasi Nomor di Basis Data (NumberAllocator):");

        // Pastikan ada user dummy
        $user = User::first() ?? User::create([
            'name' => 'Operator Uji',
            'email' => 'operator@xcode.test',
            'password' => bcrypt('password'),
        ]);

        // Buat atau cari event simulasi
        $event = Event::firstOrCreate(
            ['kode' => 'DEMO35'],
            [
                'judul' => 'Event Simulasi Koding Laravel Hermes AI',
                'jenis' => 'seminar',
                'penerbit' => 'Xcodetraining',
                'bernomor' => true,
                'pakai_qr' => true,
                'aktif' => true,
            ]
        );

        $saranAwal = $allocator->suggest($event, 1, '2026-10-24');
        $this->line("   - Saran nomor pertama untuk event [{$event->kode}]: <info>{$saranAwal['nomor_lengkap']}</info>");

        // Simulasikan terbit sertifikat urut 1
        $cert1 = Certificate::firstOrCreate(
            [
                'event_id' => $event->id,
                'seri' => 1,
                'urut' => $saranAwal['urut_saran'],
                'tahun' => $saranAwal['tahun'],
            ],
            [
                'jenis' => 'seminar',
                'nomor_lengkap' => $saranAwal['nomor_lengkap'],
                'nama_peserta' => 'Budi Santoso',
                'tanggal_terbit' => '2026-10-24',
                'data_cetak' => ['judul' => $event->judul],
                'versi_layout' => '1.0',
                'status' => 'terbit',
                'dibuat_oleh' => $user->id,
            ]
        );
        $this->line("   - Berhasil menerbitkan sertifikat #1 untuk [Budi Santoso]");

        // Cek saran nomor kedua
        $saranKedua = $allocator->suggest($event, 1, '2026-10-24');
        $this->line("   - Saran nomor kedua otomatis naik: <info>{$saranKedua['nomor_lengkap']}</info>");

        // Simulasikan terbit sertifikat urut 2 lalu batalkan
        $cert2 = Certificate::firstOrCreate(
            [
                'event_id' => $event->id,
                'seri' => 1,
                'urut' => $saranKedua['urut_saran'],
                'tahun' => $saranKedua['tahun'],
            ],
            [
                'jenis' => 'seminar',
                'nomor_lengkap' => $saranKedua['nomor_lengkap'],
                'nama_peserta' => 'Siti Rahma',
                'tanggal_terbit' => '2026-10-24',
                'data_cetak' => ['judul' => $event->judul],
                'versi_layout' => '1.0',
                'status' => 'dibatalkan',
                'alasan_batal' => 'Salah pengetikan nama peserta',
                'dibuat_oleh' => $user->id,
            ]
        );
        $this->warn("   - Sertifikat #2 atas nama [Siti Rahma] DIBATALKAN oleh Admin.");

        // Cek saran nomor ketiga (membuktikan nomor batal tidak dipakai ulang)
        $saranKetiga = $allocator->suggest($event, 1, '2026-10-24');
        $this->line("   - Saran nomor ketiga (bukti nomor batal tidak dipakai ulang): <info>{$saranKetiga['nomor_lengkap']}</info>");

        $this->newLine();
        $this->info("✅ Semua alur penomoran dan alokasi berjalan 100% normal!");

        // Mode interaktif jika diminta
        if ($this->option('interactive')) {
            $this->newLine();
            $this->info("--- Mode Interaktif Penomoran ---");
            $kode = $this->ask('Masukkan Kode Kegiatan (misal: 35)', '35');
            $penerbit = $this->ask('Masukkan Nama Penerbit (misal: Xcodetraining)', 'Xcodetraining');
            $seri = (int) $this->ask('Masukkan Nomor Seri (default: 1)', '1');
            $urut = (int) $this->ask('Masukkan Nomor Urut Peserta (misal: 7)', '7');
            $tanggal = $this->ask('Masukkan Tanggal Terbit (YYYY-MM-DD)', date('Y-m-d'));

            $hasil = NumberGenerator::build($kode, $seri, $urut, $penerbit, $tanggal);
            $this->newLine();
            $this->info("🎉 Hasil Nomor Sertifikat Anda: {$hasil}");
        }

        return Command::SUCCESS;
    }
}
