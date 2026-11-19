<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Data contoh untuk membangun antarmuka sebelum controller backend digabung.
 * Bentuk tiap array mengikuti kolom tabel di PRD bagian 7.1 agar view tidak
 * perlu diubah saat closure di routes/web.php diganti controller asli.
 * ponytail: hapus kelas ini setelah HistoryController, Admin\* dan AuthController selesai.
 */
class UiDummy
{
    public static function user(): object
    {
        return (object) ['id' => 1, 'username' => 'giska', 'nama' => 'Giska Saputra', 'peran' => 'admin', 'aktif' => true];
    }

    public static function events(): array
    {
        return [
            ['id' => 1, 'kode' => '35', 'judul' => 'Koding Laravel: 20x Lebih Cepat dengan Hermes AI', 'jenis' => 'seminar', 'penerbit' => 'Xcodetraining', 'bernomor' => true, 'pakai_qr' => true, 'program_default' => null, 'aktif' => true],
            ['id' => 2, 'kode' => '36', 'judul' => 'Membangun REST API Aman dengan Sanctum', 'jenis' => 'seminar', 'penerbit' => 'Xcodetraining', 'bernomor' => true, 'pakai_qr' => true, 'program_default' => null, 'aktif' => true],
            ['id' => 3, 'kode' => '23', 'judul' => 'Dasar Ethical Hacking untuk Pemula', 'jenis' => 'seminar', 'penerbit' => 'SiberExpert', 'bernomor' => true, 'pakai_qr' => true, 'program_default' => null, 'aktif' => true],
            ['id' => 4, 'kode' => '41', 'judul' => 'Desain Antarmuka yang Mudah Dipakai', 'jenis' => 'seminar', 'penerbit' => 'CSS', 'bernomor' => true, 'pakai_qr' => false, 'program_default' => null, 'aktif' => false],
            ['id' => 5, 'kode' => '14', 'judul' => 'X-Code Bootcamp #14', 'jenis' => 'bootcamp', 'penerbit' => 'Bootcamp', 'bernomor' => false, 'pakai_qr' => false, 'program_default' => 'Penetration Testing & Cyber Security Engineer', 'aktif' => true],
            ['id' => 6, 'kode' => '15', 'judul' => 'X-Code Bootcamp #15', 'jenis' => 'bootcamp', 'penerbit' => 'Bootcamp', 'bernomor' => true, 'pakai_qr' => false, 'program_default' => 'Fullstack Web Developer', 'aktif' => true],
        ];
    }

    public static function users(): array
    {
        return [
            ['id' => 1, 'username' => 'giska', 'nama' => 'Giska Saputra', 'peran' => 'admin', 'aktif' => true, 'terakhir' => '19 Nov 2026, 15.20'],
            ['id' => 2, 'username' => 'akmal', 'nama' => 'Akmal Fauzan', 'peran' => 'admin', 'aktif' => true, 'terakhir' => '19 Nov 2026, 09.41'],
            ['id' => 3, 'username' => 'bima', 'nama' => 'Bima Aditya', 'peran' => 'operator', 'aktif' => true, 'terakhir' => '18 Nov 2026, 16.07'],
            ['id' => 4, 'username' => 'ifant', 'nama' => 'Ifant Ramadhan', 'peran' => 'operator', 'aktif' => true, 'terakhir' => '18 Nov 2026, 11.32'],
            ['id' => 5, 'username' => 'magang.lama', 'nama' => 'Rizky Pratama', 'peran' => 'operator', 'aktif' => false, 'terakhir' => '2 Sep 2026, 14.15'],
        ];
    }

    public static function certificates(): array
    {
        $rows = [
            [1, 15, "Mu'ammal Hamidy", '2026-10-24', 'terbit', 'Giska Saputra'],
            [1, 9, 'Mourice', '2026-10-24', 'terbit', 'Bima Aditya'],
            [1, 3, 'Arief Soekardi', '2026-10-24', 'terbit', 'Bima Aditya'],
            [5, null, 'Akrom Masani Iksanul Amri', '2026-10-20', 'terbit', 'Giska Saputra'],
            [2, 4, 'Dewi Kartika Sari', '2026-10-12', 'dibatalkan', 'Ifant Ramadhan'],
            [2, 3, 'Nur Aisyah Rahmawati', '2026-10-12', 'terbit', 'Ifant Ramadhan'],
            [3, 2, 'Bagus Setiawan', '2026-09-30', 'terbit', 'Akmal Fauzan'],
            [3, 1, 'Raden Ayu Prameswari Wulandari', '2026-09-30', 'terbit', 'Akmal Fauzan'],
        ];
        $events = collect(self::events())->keyBy('id');

        return collect($rows)->map(function ($r, $i) use ($events) {
            [$eventId, $urut, $nama, $tgl, $status, $pembuat] = $r;
            $e = $events[$eventId];
            $date = Carbon::parse($tgl);
            $nomor = $urut ? sprintf('%s/001/%03d/%s/%s/%s', $e['kode'], $urut, $e['penerbit'], self::roman($date->month), $date->year) : null;

            return [
                'id' => 128 - $i,
                'event' => $e,
                'jenis' => $e['jenis'],
                'nomor_lengkap' => $nomor,
                'nama_peserta' => $nama,
                'tanggal_terbit' => $date,
                'status' => $status,
                'pembuat' => $pembuat,
                'versi_layout' => '1.0',
                'alasan_batal' => $status === 'dibatalkan' ? 'Salah ketik nama peserta, diterbitkan ulang.' : null,
                'dibatalkan_pada' => $status === 'dibatalkan' ? $date->copy()->addDay()->setTime(10, 12) : null,
                'created_at' => $date->copy()->setTime(14, 30 - $i),
                'data_cetak' => $e['jenis'] === 'seminar'
                    ? ['judul_cetak' => $e['judul'], 'nama_peserta' => $nama, 'tanggal_kota' => 'Yogyakarta, '.$date->translatedFormat('j F Y')]
                    : ['bootcamp' => $e['judul'], 'program' => $e['program_default'], 'nama_peserta' => $nama, 'perusahaan' => 'PT Teknologi Keamanan Digital', 'tanggal_mulai' => '1 Juli 2026', 'tanggal_selesai' => '30 September 2026', 'tanggal_kota' => 'Yogyakarta, '.$date->translatedFormat('j F Y')],
            ];
        })->all();
    }

    public static function certificate(int $id): ?array
    {
        return collect(self::certificates())->firstWhere('id', $id);
    }

    /** Jumlah terbit per bulan, enam bulan terakhir. Contoh saja. */
    public static function monthly(): array
    {
        return ['Jun' => 18, 'Jul' => 31, 'Agu' => 24, 'Sep' => 46, 'Okt' => 63, 'Nov' => 37];
    }

    public static function audit(): array
    {
        return [
            ['waktu' => '19 Nov 2026, 15.20', 'pelaku' => 'Giska Saputra', 'aksi' => 'masuk', 'target' => null, 'detail' => 'Masuk dari peramban Chrome', 'ip' => '10.0.4.21'],
            ['waktu' => '19 Nov 2026, 14.30', 'pelaku' => 'Giska Saputra', 'aksi' => 'terbit', 'target' => '35/001/015/Xcodetraining/X/2026', 'detail' => "Mu'ammal Hamidy", 'ip' => '10.0.4.21'],
            ['waktu' => '19 Nov 2026, 13.02', 'pelaku' => 'Bima Aditya', 'aksi' => 'unduh_ulang', 'target' => '35/001/009/Xcodetraining/X/2026', 'detail' => 'Mourice', 'ip' => '10.0.4.18'],
            ['waktu' => '13 Okt 2026, 10.12', 'pelaku' => 'Akmal Fauzan', 'aksi' => 'batal', 'target' => '36/001/004/Xcodetraining/X/2026', 'detail' => 'Salah ketik nama peserta', 'ip' => '10.0.4.9'],
            ['waktu' => '12 Okt 2026, 08.55', 'pelaku' => 'Akmal Fauzan', 'aksi' => 'ubah_event', 'target' => 'Kegiatan 36', 'detail' => 'Judul diperbarui', 'ip' => '10.0.4.9'],
            ['waktu' => '11 Okt 2026, 21.40', 'pelaku' => null, 'aksi' => 'gagal_masuk', 'target' => null, 'detail' => 'Username: operator1', 'ip' => '182.1.77.30'],
            ['waktu' => '2 Sep 2026, 14.15', 'pelaku' => 'Giska Saputra', 'aksi' => 'ubah_user', 'target' => 'magang.lama', 'detail' => 'Dinonaktifkan', 'ip' => '10.0.4.21'],
        ];
    }

    public static function roman(int $month): string
    {
        return ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$month - 1];
    }
}
