# Generator Sertifikat XCODE

**Product Requirements Document**

Kebutuhan produk secara rinci: peran, kebutuhan fungsional, penomoran, PDF, data, dan kriteria terima

**Produk:** Generator Sertifikat XCODE, aplikasi web berbasis Laravel 12 yang menerbitkan sertifikat seminar/webinar dan bootcamp/magang sebagai PDF

**Pemberi tugas:** Coach divisi programmer XCODE

**Penyusun:** [isi nama peserta magang]

**Versi:** Draf 1.0, 18 November 2026

**Arsitektur acuan:** Laravel 12, PHP 8.2+, MySQL 8 / MariaDB 10.6+, Blade, Vite, Eloquent ORM, TCPDF (lihat Design System)

> **Cara memakai dengan AI agent:** berikan keempat dokumen sebagai konteks, lalu kerjakan per fase (bagian 10). Tanda **[ASUMSI]** berarti belum dikonfirmasi coach. Tanda **[KALIBRASI]** berarti nilai harus disesuaikan setelah PDF dibandingkan dengan sertifikat asli. Bila ada hal di luar dokumen, agent bertanya, tidak menebak.

---

## Ketentuan tech stack (wajib)

- Framework backend: **Laravel 12**. Tidak menggunakan PHP native tanpa framework, CodeIgniter, atau framework lain sebagai pengganti.
- Runtime: **PHP 8.2+** sesuai persyaratan Laravel 12 dan Composer 2.
- Database: MySQL 8 atau MariaDB 10.6+; akses data melalui Eloquent ORM/Query Builder.
- UI: Blade, CSS dengan design tokens XCODE, JavaScript untuk interaksi form, dan Vite untuk asset build.
- PDF: TCPDF melalui Composer; QR menggunakan dukungan QR TCPDF atau pustaka kompatibel yang dipilih tim.
- Testing: PHPUnit atau Pest yang terintegrasi dengan Laravel.
- Struktur Laravel standar dipertahankan; aturan bisnis ditempatkan pada service class agar mudah diuji.

## 1. Tujuan produk

- **T1.** Operator dapat menerbitkan satu sertifikat dalam waktu kurang dari satu menit, tanpa perlu tahu aturan nomor atau letak teks.
- **T2.** Nomor sertifikat selalu sesuai format XCODE dan tidak pernah ganda.
- **T3.** PDF yang dihasilkan tampak sama dengan sertifikat asli: ukuran, font, dan posisi teks.
- **T4.** Setiap sertifikat yang terbit tercatat dan dapat diunduh ulang dengan hasil identik.
- **T5.** Penerbitan tidak lagi bergantung pada satu orang.

### 1.1 Ukuran keberhasilan

| Metrik | Cara hitung | Sasaran |
|---|---|---|
| Waktu penerbitan | Dari form terbuka sampai PDF terunduh, data sudah di tangan | Di bawah 1 menit |
| Waktu proses server | Dari klik Terbitkan sampai PDF mulai terkirim | Di bawah 3 detik |
| Akurasi nomor | Seluruh kasus uji pada bagian 5.3 | 100% lulus |
| Keunikan nomor | 50 permintaan paralel untuk satu kegiatan | Tidak ada nomor ganda |
| Kesesuaian layout | Hasil PDF ditumpuk di atas sampel asli | Selisih posisi di bawah 1 mm |
| Nama ekstrem | Pendek, panjang, apostrof, gelar, huruf berakses, 70 karakter | Tidak ada yang keluar garis atau menumpuk |
| Cakupan sampel | Tiga sertifikat seminar dan satu bootcamp direproduksi | Semua tampak sama dengan aslinya |

---

## 2. Pengguna dan peran

| Peran | Kebutuhan utama | Hak akses ringkas |
|---|---|---|
| Operator | Menerbitkan sertifikat dengan cepat dan benar | Form penerbitan, pratinjau, riwayat, unduh ulang |
| Admin | Menjaga data master dan akses | Semua hak operator, kelola kegiatan, kelola pengguna, batalkan sertifikat, uji layout, lihat audit |
| Peserta | Menerima sertifikat yang benar | Tidak memiliki akun. Hanya menerima berkas dari operator |

**[ASUMSI]** Satu akun memegang satu peran. Login memakai username dan kata sandi, tanpa SSO.

### 2.1 Persona

- **Operator pengganti.** Anggota tim yang diminta menerbitkan sertifikat saat staf biasa berhalangan. Belum hafal format nomor. Butuh form yang menjelaskan dirinya sendiri dan pratinjau sebelum terbit.
- **Staf penerbit.** Pengguna rutin. Menerbitkan puluhan sertifikat dari satu event dan butuh mengisi nomor urut sesuai daftar peserta.
- **Admin.** Coach atau penanggung jawab. Menambah kegiatan baru beserta kodenya, membatalkan sertifikat yang keliru, dan memeriksa riwayat.

---

## 3. Ruang lingkup

### 3.1 Termasuk

- Delapan modul: Autentikasi, Data Kegiatan, Form Penerbitan, Penomoran, Generator PDF, Riwayat, Pengguna, Audit.
- Dua jenis sertifikat: Seminar/Webinar (bernomor, ber-QR) dan Bootcamp/Magang (tanpa nomor dan QR).
- Satu sertifikat untuk satu peserta setiap kali penerbitan.
- Seed daftar kegiatan dari dokumen aturan kode XCODE.
- Halaman uji layout dengan garis bantu untuk kalibrasi koordinat.

### 3.2 Tidak termasuk

- Penerbitan massal lewat CSV atau Excel (struktur disiapkan, belum dibangun).
- Halaman verifikasi publik `xcode.co.id/cekkodesertifikat` dan penghubungan ke sistem itu.
- Pengiriman sertifikat lewat email atau WhatsApp.
- Tanda tangan digital kriptografis atau blockchain.
- Jenis sertifikat lain: Siber Expert Asia, Cyber Sentinel Secure, sertifikat training lama, dan surat biasa.
- Editor visual untuk memindahkan posisi teks.
- Aplikasi ponsel. Web dibuat responsif.

---

## 4. Kebutuhan fungsional

### 4.1 Autentikasi

| ID | Kebutuhan | Kriteria terima |
|---|---|---|
| AU-01 | Masuk dengan username dan kata sandi. | Kata sandi disimpan dengan mekanisme hashing Laravel (`Hash::make` dan `Hash::check`). |
| AU-02 | Keluar menghapus sesi. | Setelah keluar, halaman terproteksi mengarahkan ke login. |
| AU-03 | Sesi aman. | session diregenerasi melalui `$request->session()->regenerate()` setelah login, cookie `HttpOnly` dan `SameSite=Lax`, sesi kedaluwarsa setelah 8 jam tanpa aktivitas. |
| AU-04 | Pembatasan percobaan masuk. | Lima kegagalan berturut-turut mengunci akun 10 menit dan pesan menyebut lamanya. |
| AU-05 | Semua halaman selain login memerlukan sesi aktif. | Akun nonaktif tidak dapat masuk dan sesinya ditolak pada permintaan berikutnya. |
| AU-06 | Pengguna mengganti kata sandinya sendiri. | Meminta kata sandi lama, minimal 10 karakter. |
| AU-07 | Halaman Admin hanya untuk peran Admin. | Operator yang membuka alamat admin menerima halaman 403. |

### 4.2 Data Kegiatan

| ID | Kebutuhan | Kriteria terima |
|---|---|---|
| KG-01 | Admin menambah kegiatan: kode, judul, jenis, penerbit, bernomor, memakai QR, program bawaan. | Kode disimpan sebagai teks. Judul maksimum 500 karakter. |
| KG-02 | Admin mengubah dan menonaktifkan kegiatan. | Kegiatan nonaktif tidak muncul di dropdown form. |
| KG-03 | Kegiatan yang sudah dipakai sertifikat tidak dapat dihapus. | Tombol hapus tidak tersedia. Hanya nonaktifkan. |
| KG-04 | Kode yang sama dengan kegiatan lain diperbolehkan setelah konfirmasi. | Peringatan menyebut kegiatan yang memakai kode itu. |
| KG-05 | Mengubah judul kegiatan tidak mengubah sertifikat lama. | Sertifikat menyimpan salinan data cetaknya. |
| KG-06 | Seed awal dari dokumen aturan kode XCODE. | Perintah Artisan seeder (misalnya `php artisan db:seed --class=EventSeeder`) idempoten dan melaporkan baris yang tidak terbaca. |
| KG-07 | Daftar kegiatan dapat dicari dan disaring menurut jenis dan status. | Pencarian menurut kode atau judul. |

### 4.3 Form Penerbitan

| ID | Kebutuhan | Kriteria terima |
|---|---|---|
| FM-01 | Pilihan jenis: Seminar/Webinar atau Bootcamp/Magang. | Mengganti jenis menampilkan field yang sesuai tanpa memuat ulang halaman. Server tetap memvalidasi ulang. |
| FM-02 | Field seminar: kegiatan, nama peserta, seri, nomor urut, tanggal terbit. | Memilih kegiatan mengisi kode, judul, dan penerbit otomatis. |
| FM-03 | Field bootcamp: bootcamp, program, nama peserta, perusahaan, tanggal mulai, tanggal selesai, tanggal terbit. | Program terisi dari program bawaan kegiatan dan dapat diubah. |
| FM-04 | Tanggal terbit bawaan hari ini menurut zona waktu Asia/Jakarta. | Dapat diubah. Tanggal lebih dari satu tahun dari hari ini memunculkan peringatan, bukan penolakan. |
| FM-05 | Nomor urut terisi saran sistem dan dapat ditimpa. | Saran dihitung ulang saat kegiatan, seri, atau tanggal berubah. |
| FM-06 | Pratinjau nomor lengkap tampil langsung di panel ringkasan. | Berubah saat field terkait berubah. |
| FM-07 | Tombol Pratinjau membuka PDF di tab baru. | Tidak memakai nomor urut dan tidak menulis ke riwayat. PDF diberi tanda air PRATINJAU. |
| FM-08 | Tombol Terbitkan & Unduh menjalankan alur penerbitan. | PDF terunduh otomatis bila berhasil. |
| FM-09 | Tombol Rapikan Kapitalisasi pada nama. | Mengubah huruf awal tiap kata, tidak mengubah apostrof, gelar, atau singkatan yang sudah kapital. Tidak berjalan otomatis. |
| FM-10 | Pesan kesalahan per field, data tidak hilang saat validasi gagal. | Kesalahan tampil di bawah field dan ringkasannya di atas form. |
| FM-11 | Peringatan bila nama yang sama sudah terbit pada kegiatan itu. | Peringatan menyebut nomor yang sudah ada. Operator boleh melanjutkan. |

### 4.4 Penomoran

| ID | Kebutuhan | Kriteria terima |
|---|---|---|
| NM-01 | Nomor dibangun dengan format `{kode}/{seri}/{urut}/{penerbit}/{bulan romawi}/{tahun}`. | Seluruh kasus uji bagian 5.3 lulus. |
| NM-02 | Nomor hanya dibuat untuk kegiatan dengan `bernomor` aktif. | Kegiatan tanpa nomor menyimpan nomor kosong. |
| NM-03 | Saran nomor urut: nilai tertinggi untuk kegiatan, seri, dan tahun yang sama ditambah satu. | Sertifikat dibatalkan ikut dihitung agar nomor tidak dipakai ulang. |
| NM-04 | Operator dapat menimpa nomor urut. | Ditolak bila kombinasi kegiatan, seri, urut, dan tahun sudah ada. |
| NM-05 | Keunikan dijaga di database. | Kolom `nomor_lengkap` dan kombinasi kegiatan, seri, urut, tahun masing-masing `UNIQUE`. |
| NM-06 | Pembuatan nomor aman dari penerbitan bersamaan. | Transaksi dengan penguncian baris kegiatan. Uji paralel tidak menghasilkan nomor ganda. |
| NM-07 | Bulan dan tahun dalam nomor mengikuti tanggal terbit. | Tanggal terbit 2026-10-24 menghasilkan `X/2026`. |
| NM-08 | Nomor yang sudah terbit tidak berubah. | Tidak ada fitur mengubah nomor. Koreksi lewat pembatalan dan penerbitan baru. |

### 4.5 Generator PDF

| ID | Kebutuhan | Kriteria terima |
|---|---|---|
| PD-01 | A4 mendatar (297 x 210 mm), satu halaman. | Diperiksa pada setiap PDF uji. |
| PD-02 | Satu gambar latar bersama untuk kedua jenis. | Latar tidak memuat teks variabel. |
| PD-03 | Posisi, font, dan ukuran teks dibaca dari konfigurasi per jenis. | Tidak ada angka koordinat di kode selain di berkas konfigurasi. |
| PD-04 | Font dibenamkan dan mendukung apostrof, titik, koma, tanda hubung, `&`, `#`, dan huruf Latin berakses. | `Mu'ammal` tercetak benar. |
| PD-05 | Nama rata tengah di atas garis dan mengecil otomatis bila terlalu lebar. | Ukuran turun sampai batas minimum. Bila masih tidak muat, nama dipecah dua baris atau penerbitan ditolak dengan pesan. |
| PD-06 | Judul kegiatan seminar satu baris, mengecil bila panjang. | Bila di batas minimum masih meluap, form menampilkan peringatan sebelum terbit. |
| PD-07 | Seminar mencetak nomor di bawah `xcode.or.id`, QR, dan teks alamat verifikasi di bawah QR. | Posisi sama dengan sampel. |
| PD-08 | Bootcamp mencetak dua baris subjudul dan paragraf dua baris. | Tanpa nomor dan QR. |
| PD-09 | Tanggal dicetak dalam bahasa Indonesia: `Yogyakarta, 24 Oktober 2026`. | Nama bulan dari tabel di kode, bukan dari locale server. |
| PD-10 | Nama berkas `Sertifikat_{Jenis}_{Nama}.pdf`. | Karakter tidak aman dibuang. `Mu'ammal Hamidy` menjadi `Muammal_Hamidy`. |
| PD-11 | Teks pada PDF dapat dipilih dan dicari. | Teks tidak diubah menjadi gambar. |
| PD-12 | Metadata PDF terisi: judul, penulis, subjek. | Judul memuat nama peserta. |
| PD-13 | QR berisi satu alamat tetap `https://xcode.co.id/cekkodesertifikat`. | Diputuskan tim (catatan Ifan): mode per nomor dihapus. Alamat diatur di `config/certificate.php`. |

### 4.6 Riwayat

| ID | Kebutuhan | Kriteria terima |
|---|---|---|
| RW-01 | Daftar sertifikat: waktu terbit, jenis, kegiatan, nama, nomor, pembuat, status. | Terbaru di atas. Paginasi 25 baris. |
| RW-02 | Cari dan saring: nama, nomor, kegiatan, jenis, status, rentang tanggal. | Kombinasi saringan bekerja bersamaan. |
| RW-03 | Unduh ulang. | PDF dirender ulang dari data tersimpan dan identik secara visual dengan unduhan pertama. |
| RW-04 | Detail sertifikat. | Menampilkan data cetak, nomor, pembuat, dan riwayat perubahan status. |
| RW-05 | Admin membatalkan sertifikat dengan alasan. | Status menjadi Dibatalkan, baris tidak dihapus, nomor tidak dipakai ulang. |
| RW-06 | Unduh ulang sertifikat yang dibatalkan memberi peringatan jelas. | PDF diberi tanda air DIBATALKAN. |
| RW-07 | Operator dan Admin melihat semua sertifikat. | Diputuskan 2026-11-23. Pembatalan tetap khusus Admin (RW-05). |

### 4.7 Pengguna

| ID | Kebutuhan | Kriteria terima |
|---|---|---|
| PG-01 | Admin menambah pengguna: username, nama, peran, kata sandi awal. | Username unik. |
| PG-02 | Admin mengubah peran, menonaktifkan, dan mereset kata sandi. | Admin tidak dapat menonaktifkan dirinya sendiri. |
| PG-03 | Selalu ada minimal satu Admin aktif. | Perubahan yang melanggar ditolak dengan pesan. |

### 4.8 Audit

| ID | Kebutuhan | Kriteria terima |
|---|---|---|
| AD-01 | Peristiwa penting tercatat: masuk, gagal masuk, terbit, unduh ulang, batal, ubah kegiatan, ubah pengguna. | Memuat pengguna, waktu, alamat IP, dan target. |
| AD-02 | Admin melihat dan menyaring log audit. | Log tidak dapat diubah dari aplikasi. |
| AD-03 | Kesalahan sistem dicatat ke berkas log, bukan ditampilkan ke operator. | Operator hanya melihat pesan umum dan kode rujukan. |

---

## 5. Spesifikasi penomoran

### 5.1 Format

```
{kode}/{seri}/{urut}/{penerbit}/{bulan_romawi}/{tahun}
```

Contoh: `35/001/003/Xcodetraining/X/2026`

| Segmen | Arti | Sumber nilai | Status |
|---|---|---|---|
| `kode` | Kode kegiatan, satu judul punya satu kode (35 = Koding Laravel: 20x Lebih Cepat dengan Hermes AI) | Tabel kegiatan | Pasti dari dokumen aturan kode |
| `seri` | Angka kedua, selalu `001` di semua contoh | Field form, bawaan 001 | **[ASUMSI]** artinya belum diketahui |
| `urut` | Nomor urut peserta, 3 digit. Sampel: 003, 009, 015 | Saran sistem, bisa diubah | Pasti dari sampel |
| `penerbit` | `Xcodetraining`, atau varian `CSS`, `Bootcamp`, `SiberExpert` | Kolom kegiatan | Pasti dari dokumen |
| `bulan_romawi` | Bulan tanggal terbit, I sampai XII | Dihitung | Pasti |
| `tahun` | Tahun tanggal terbit | Dihitung | Pasti |

### 5.2 Aturan

1. Kode kegiatan **bukan** penghitung otomatis. Ditetapkan manual, tidak selalu berurutan, ada varian, dan sebagian dipakai bersama beberapa versi kegiatan.
2. Kode disimpan sebagai teks.
3. Nomor urut bersifat per kombinasi kegiatan, seri, dan tahun terbit.
4. Pembuatan nomor memakai transaksi dan `SELECT ... FOR UPDATE` pada baris kegiatan.
5. Format dibangun hanya oleh `NumberGenerator::build`.
6. Kegiatan bootcamp bawaannya tidak bernomor. Dokumen aturan kode mencatat format bootcamp lama (`15/001/001/Bootcamp/II/2025`), sehingga penomoran tetap harus bisa diaktifkan per kegiatan.

### 5.3 Kasus uji wajib

| Input | Hasil |
|---|---|
| kode `35`, seri 1, urut 3, `Xcodetraining`, 2026-10-24 | `35/001/003/Xcodetraining/X/2026` |
| kode `35`, seri 1, urut 9, `Xcodetraining`, 2026-10-24 | `35/001/009/Xcodetraining/X/2026` |
| kode `35`, seri 1, urut 15, `Xcodetraining`, 2026-10-24 | `35/001/015/Xcodetraining/X/2026` |
| kode `15`, seri 1, urut 1, `Bootcamp`, 2025-02-10 | `15/001/001/Bootcamp/II/2025` |
| kode `23`, seri 1, urut 1, `SiberExpert`, 2024-05-03 | `23/001/001/SiberExpert/V/2024` |
| kode `255`, seri 1, urut 1, `Xcodetraining`, 2026-12-01 | `255/001/001/Xcodetraining/XII/2026` |
| Kombinasi kegiatan, seri, urut, tahun yang sama diajukan dua kali | Ditolak dengan pesan duplikat, tidak ada baris baru |
| 50 permintaan paralel pada satu kegiatan tanpa urut diisi | 50 nomor berbeda tanpa celah error |
| Render PDF dipaksa gagal | Tidak ada baris tersimpan dan nomor tetap bebas |
| Tanggal terbit 2026-01-31 dan 2026-12-31 | `I/2026` dan `XII/2026` |

---

## 6. Spesifikasi PDF

Rincian koordinat, font, dan aturan pengepasan teks ada di Design System bagian 9. Ringkasannya:

| Aspek | Ketentuan |
|---|---|
| Ukuran | A4 mendatar, satu halaman |
| Latar | Satu gambar bersih tanpa teks variabel, minimal 300 dpi (3508 x 2480 px) |
| Teks variabel seminar | Judul besar, judul kegiatan, nama, nomor, tanggal, teks alamat verifikasi |
| Teks variabel bootcamp | Judul besar, dua subjudul, nama, paragraf, tanggal |
| QR | Seminar saja, sekitar 36 mm persegi, isi menurut mode QR |
| Elemen tetap | Kaligrafi Sertifikat, diberikan kepada, `xcode.or.id`, garis nama, tanda tangan, Cynthia, Direktur. Diharapkan sudah ada di latar |
| Tanda air | PRATINJAU pada pratinjau, DIBATALKAN pada sertifikat yang dibatalkan |

---

## 7. Model data

| Kelompok | Tabel | Isi pokok |
|---|---|---|
| Akun | `users` | Username, kata sandi ter-hash, peran, status aktif, percobaan gagal |
| Master | `events` | Kode, judul, jenis, penerbit, bernomor, pakai QR, program bawaan, aktif |
| Penerbitan | `certificates` | Kegiatan, nomor, nama, tanggal terbit, salinan data cetak, versi layout, status |
| Sistem | `audit_log` | Pelaku, aksi, target, rincian, IP, waktu |

Status disimpan sebagai nilai terbatas: `CertificateStatus` (Terbit, Dibatalkan), `EventType` (seminar, bootcamp), `Role` (operator, admin).

### 7.1 Skema SQL

```sql
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  peran ENUM('operator','admin') NOT NULL DEFAULT 'operator',
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  gagal_login TINYINT UNSIGNED NOT NULL DEFAULT 0,
  terkunci_sampai DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(10) NOT NULL,              -- teks: 35, 255, 588
  judul VARCHAR(500) NOT NULL,
  jenis ENUM('seminar','bootcamp') NOT NULL,
  penerbit VARCHAR(50) NOT NULL DEFAULT 'Xcodetraining',
  bernomor TINYINT(1) NOT NULL DEFAULT 1,
  pakai_qr TINYINT(1) NOT NULL DEFAULT 1,
  program_default VARCHAR(200) NULL,      -- untuk bootcamp
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_kode (kode),
  KEY idx_jenis_aktif (jenis, aktif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE certificates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id INT UNSIGNED NOT NULL,
  jenis ENUM('seminar','bootcamp') NOT NULL,
  seri SMALLINT UNSIGNED NULL,
  urut SMALLINT UNSIGNED NULL,
  tahun SMALLINT UNSIGNED NOT NULL,       -- tahun tanggal terbit
  nomor_lengkap VARCHAR(120) NULL UNIQUE, -- NULL bila tidak bernomor
  nama_peserta VARCHAR(100) NOT NULL,
  tanggal_terbit DATE NOT NULL,
  data_cetak JSON NOT NULL,               -- salinan semua teks yang dicetak
  versi_layout VARCHAR(20) NOT NULL,
  status ENUM('terbit','dibatalkan') NOT NULL DEFAULT 'terbit',
  alasan_batal VARCHAR(255) NULL,
  dibatalkan_oleh INT UNSIGNED NULL,
  dibatalkan_pada DATETIME NULL,
  dibuat_oleh INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_event_seri_urut_tahun (event_id, seri, urut, tahun),
  KEY idx_nama (nama_peserta),
  KEY idx_tanggal (tanggal_terbit),
  FOREIGN KEY (event_id) REFERENCES events(id),
  FOREIGN KEY (dibuat_oleh) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  aksi VARCHAR(50) NOT NULL,              -- masuk, gagal_masuk, terbit, unduh_ulang, batal, ubah_event, ubah_user
  target_id INT UNSIGNED NULL,
  detail JSON NULL,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_aksi_waktu (aksi, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Catatan: kolom `UNIQUE` yang boleh `NULL` di MySQL mengizinkan banyak nilai `NULL`, sehingga sertifikat bootcamp tanpa nomor dan tanpa urut tidak saling bertabrakan.

### 7.2 Isi `data_cetak`

Salinan ini membuat unduh ulang tetap sama walau kegiatan diubah kemudian.

```json
{
  "judul_besar": "SERTIFIKAT SEMINAR",
  "judul_kegiatan": "Koding Laravel: 20x Lebih Cepat dengan Hermes AI",
  "kode": "35",
  "penerbit": "Xcodetraining",
  "kota": "Yogyakarta"
}
```

Untuk bootcamp ditambah `program`, `perusahaan`, `tanggal_mulai`, `tanggal_selesai`, dan `bootcamp`.

### 7.3 Seed kegiatan

Skrip `database/seeders/EventSeeder.php` membaca daftar pada dokumen aturan kode XCODE.

- Bagian Sertifikat Seminar masuk sebagai jenis `seminar`, penerbit `Xcodetraining`, `bernomor=1`, `pakai_qr=1`. Contoh: kode `35` Koding Laravel: 20x Lebih Cepat dengan Hermes AI, kode `335` Penetration Testing + AI, kode `255` Ethical Web Hacking, Post Exploitation & Defense (+ GSOCKET).
- Kode yang dipakai beberapa kegiatan (39, 59, 72, 74) dimasukkan apa adanya sebagai baris terpisah.
- Bootcamp dimasukkan sebagai jenis `bootcamp` dengan `bernomor=0` dan `pakai_qr=0`.
- Webinar Siber Expert Asia (`SiberExpert`) dan Cyber Sentinel Secure (`CSS`) tidak di-seed pada versi 1.
- Format lama (kode surat 01 sampai 17, contoh tahun 2019) diabaikan.
- Skrip dapat dijalankan berulang tanpa menggandakan baris dan menampilkan daftar baris yang gagal dibaca.

---

## 8. Kebutuhan non-fungsional

| Aspek | Kebutuhan |
|---|---|
| Kinerja | Satu PDF dibuat di bawah 3 detik pada server biasa. Halaman form terbuka di bawah 2 detik. |
| Keamanan | Gunakan Eloquent ORM atau Laravel Query Builder (parameter binding); hindari SQL mentah yang menggabungkan input pengguna. Blade melakukan escaping secara bawaan; gunakan `{!! !!}` hanya untuk HTML tepercaya. Aktifkan middleware CSRF, autentikasi, otorisasi/policy, dan rate limiter Laravel. Kredensial hanya di `.env`. Web root wajib `public/`; file privat dan konfigurasi tidak boleh dapat diakses langsung. |
| Validasi | Semua dilakukan di server. Validasi di peramban hanya untuk kenyamanan. |
| Keandalan | Penerbitan atomik. Kegagalan membuat PDF tidak meninggalkan nomor terpakai. |
| Keterulangan | Unduh ulang menghasilkan PDF yang sama dari data tersimpan dan versi layout yang tercatat. |
| Zona waktu | Aplikasi memakai Asia/Jakarta untuk tanggal bawaan dan nama bulan. |
| Aksesibilitas | Kontras memadai, fokus papan ketik terlihat, label field selalu terlihat, tombol minimal 44 px. |
| Responsif | Dapat dipakai di laptop dan tablet, dirancang dari lebar 360 px. |
| Bahasa | Antarmuka bahasa Indonesia sehari-hari. |
| Kompatibilitas | Laravel 12 dengan PHP 8.2 atau lebih baru; MySQL 8 atau MariaDB 10.6 atau lebih baru. Laravel 12 menjadi framework wajib, bukan opsi. |
| Mutu kode | Standar Laravel 12 dan PSR-12/PSR-4; controller tipis, Form Request untuk validasi, service class untuk aturan bisnis, Eloquent model untuk data, migrations/seeders untuk skema dan data awal, serta PHPUnit/Pest untuk tes penomoran, tanggal, transaksi, dan validasi. |
| Pencatatan | Kesalahan dicatat melalui Laravel logging ke `storage/logs`, tidak ditampilkan ke operator. |

---

## 9. Asumsi dan pertanyaan terbuka

| # | Pertanyaan | Asumsi sementara | Dampak bila salah |
|---|---|---|---|
| 1 | Arti angka kedua (`001`) dan kapan berubah | Field `seri`, bawaan 001, bisa diubah | Hanya label dan aturan reset |
| 2 | Bootcamp selalu tanpa nomor dan QR | Ya, diatur `events.bernomor` dan `pakai_qr` | Aktifkan flag tanpa mengubah kode |
| 3 | Isi QR seminar | Alamat tetap `https://xcode.co.id/cekkodesertifikat`. Pindai satu sampel untuk memastikan | Bila per nomor, ubah mode QR di konfigurasi dan sistem verifikasi harus membaca data kita |
| 4 | Sistem verifikasi sudah ada dan perlu terhubung | Di luar versi 1, data tersimpan di tabel `certificates` | Perlu API baca-saja |
| 5 | Siber Expert Asia dan Cyber Sentinel Secure masuk lingkup | Belum | Perlu desain latar dan layout baru |
| 6 | Tanda tangan, nama penanda tangan, dan kota selalu tetap | Tetap, bagian dari latar | Tambah field di form |
| 7 | Judul besar seminar selalu SERTIFIKAT SEMINAR, termasuk untuk webinar | Ya | Jadikan field per kegiatan |
| 8 | Siapa menambah kegiatan baru | Admin | Ubah hak akses |
| 9 | Nomor sertifikat yang dibatalkan boleh dipakai ulang untuk koreksi peserta yang sama | Tidak boleh | Perlu aturan keunikan yang mengabaikan baris dibatalkan |
| 10 | Nomor urut direset per tahun atau per kegiatan | Per kombinasi kegiatan, seri, dan tahun | Ubah query penghitung |
| 11 | File latar bersih, tanda tangan transparan, dan font asli | Diminta ke coach. Sementara memakai Tinos atau Liberation Serif | Kalibrasi tertunda |
| 12 | Versi framework, PHP, dan hosting | Laravel 12, PHP 8.2+, MySQL 8 atau MariaDB 10.6+ | Wajib menggunakan Laravel 12; hosting harus mendukung ekstensi dan Composer yang diperlukan |
| 13 | Operator melihat sertifikat siapa saja | **Diputuskan 2026-11-23:** semua sertifikat (lihat, detail, unduh ulang). Pembatalan tetap khusus Admin | Ubah saringan riwayat |

**Pertanyaan siap kirim ke coach**

> 1. Pada nomor `35/001/003/Xcodetraining/X/2026`, angka `001` di tengah artinya apa dan kapan berubah?
> 2. Sertifikat bootcamp sekarang memang tanpa nomor dan QR untuk semua bootcamp?
> 3. QR di sertifikat seminar menuju alamat yang sama untuk semua peserta, atau berbeda per nomor?
> 4. Mohon dikirim file latar tanpa teks, file tanda tangan transparan, dan font yang dipakai.
> 5. Apakah Siber Expert Asia dan Cyber Sentinel Secure termasuk lingkup tugas ini?
> 6. Bila ada koreksi nama, nomor lama boleh dipakai lagi atau harus nomor baru?

---

## 10. Urutan pembangunan

| Fase | Isi | Selesai bila |
|---|---|---|
| 0 | Fondasi Laravel 12: buat proyek Composer, konfigurasi `.env`, koneksi database, route, Blade layout, Vite, dan token CSS | Aplikasi Laravel berjalan, halaman login awal dan halaman 404 tampil |
| 1 | Skema basis data, pengguna awal, autentikasi, sesi, CSRF, pembatasan login | Operator dan admin dapat masuk dan keluar |
| 2 | Penomoran: `Roman`, `NumberGenerator`, `NumberAllocator`, uji unit dan uji paralel | Seluruh kasus uji bagian 5.3 hijau |
| 3 | Data kegiatan: repository, seed dari dokumen, halaman admin kegiatan | Daftar kegiatan terisi dan dapat dikelola |
| 4 | Generator PDF seminar: latar, font, QR, pengepasan teks, `DateId`, halaman uji layout | Tiga sampel seminar tampak sama dengan aslinya |
| 5 | Generator PDF bootcamp | Sampel bootcamp tampak sama dengan aslinya |
| 6 | Form penerbitan dinamis, saran nomor, pratinjau, `IssueService`, unduhan | Alur dari form sampai PDF terunduh berjalan |
| 7 | Riwayat, unduh ulang, pembatalan, audit, kelola pengguna | Semua daftar dapat dicari dan unduh ulang identik |
| 8 | Pengujian akhir, nama ekstrem, keamanan, README | Seluruh kriteria terima lulus |

Setiap fase ditutup dengan tes hijau, halaman dibuka di peramban, dan commit.

---

## 11. Risiko produk dan penanganan

| Risiko | Penanganan |
|---|---|
| Format nomor salah membuat sertifikat resmi tidak sah | Satu fungsi pembangun nomor, uji dengan contoh asli, pratinjau nomor di form |
| Nomor ganda karena dua operator bersamaan | Transaksi, penguncian baris, `UNIQUE` |
| Operator salah memasukkan nomor urut | Nomor saran, peringatan nama kembar, pembatalan tercatat |
| Nama panjang merusak layout | Pengecilan otomatis, batas 100 karakter, pratinjau |
| Font tidak sama dengan aslinya | Font bermetrik setara, minta file asli |
| Layout berubah dan merusak unduh ulang lama | Versi layout dicatat, konfigurasi versi lama dipertahankan, data cetak disalin |
| Arti angka kedua ternyata mengikuti aturan tertentu | Field `seri` terpisah dan aturan di satu fungsi |
| Penyalahgunaan akses | Peran, audit, pembatalan tanpa penghapusan |
| Latar dari coach masih memuat teks | Minta versi bersih. Cadangan: tutup dengan kotak warna latar pada area teks |

---

## 12. Aturan bagi AI agent

1. Jangan menebak aturan nomor di luar bagian 5. Pakai nilai bawaan dan tandai komentar `// ASUMSI`.
2. Semua koordinat dan teks tetap berada di `config/layout/`.
3. Selalu pakai prepared statements dan escape output. Jangan menaruh kredensial di repositori.
4. Tulis tes bersama kode untuk `NumberGenerator`, `NumberAllocator`, `DateId`, dan validasi.
5. Render PDF dilakukan sebelum `COMMIT`. Jangan memindahkannya setelahnya.
6. Setelah tiap fase, ringkas apa yang dibuat, cara mengujinya, dan hal yang masih diasumsikan.
7. Bila dokumen bertentangan atau kurang jelas, berhenti dan ajukan satu pertanyaan singkat.

---

XCODE | PRD
