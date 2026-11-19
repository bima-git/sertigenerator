<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! filter_var(env('SEED_DUMMY_EVENTS', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->command?->warn(
                'EventSeeder dilewati: daftar kode kegiatan resmi belum tersedia. ' .
                'Set SEED_DUMMY_EVENTS=true hanya jika ingin memuat data contoh untuk pengujian.'
            );

            return;
        }

        $dibuat = 0;
        $dilewati = 0;

        foreach ($this->dummyEvents() as $data) {
            $event = Event::query()->firstOrCreate(
                ['judul' => $data['judul']],
                $data
            );

            if ($event->wasRecentlyCreated) {
                $dibuat++;
            } else {
                $dilewati++;
            }
        }

        $this->command?->info(
            "EventSeeder selesai: {$dibuat} data contoh dibuat, {$dilewati} sudah ada."
        );
    }

    /**
     * Data contoh untuk pengujian, bukan daftar kegiatan resmi XCODE.
     *
     * @return array<int, array<string, mixed>>
     */
    private function dummyEvents(): array
    {
        return [
            [
                'kode' => 'CONTOH-01',
                'judul' => '[CONTOH] Seminar Uji Coba Satu',
                'jenis' => 'seminar',
                'penerbit' => 'Contoh Penerbit',
                'bernomor' => true,
                'pakai_qr' => true,
                'program_default' => null,
                'aktif' => true,
            ],
            [
                'kode' => 'CONTOH-02',
                'judul' => '[CONTOH] Seminar Uji Coba Dua',
                'jenis' => 'seminar',
                'penerbit' => 'Contoh Penerbit',
                'bernomor' => true,
                'pakai_qr' => true,
                'program_default' => null,
                'aktif' => true,
            ],
            [
                'kode' => 'CONTOH-03',
                'judul' => '[CONTOH] Bootcamp Uji Coba Satu',
                'jenis' => 'bootcamp',
                'penerbit' => 'Contoh Penerbit',
                'bernomor' => false,
                'pakai_qr' => false,
                'program_default' => 'Program Uji Coba',
                'aktif' => true,
            ],
            [
                'kode' => 'CONTOH-04',
                'judul' => '[CONTOH] Bootcamp Uji Coba Nonaktif',
                'jenis' => 'bootcamp',
                'penerbit' => 'Contoh Penerbit',
                'bernomor' => false,
                'pakai_qr' => false,
                'program_default' => 'Program Uji Coba',
                'aktif' => false,
            ],
        ];
    }
}
