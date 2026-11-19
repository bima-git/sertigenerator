# Generator Sertifikat Otomatis XCODE

Aplikasi web internal berbasis **Laravel 12** yang digunakan oleh divisi training dan operasional **XCODE** untuk menerbitkan sertifikat kegiatan (Seminar/Webinar dan Bootcamp/Magang) secara terstandarisasi, aman, dan otomatis dalam format PDF.

---

## 1. Gambaran Umum & Tujuan Sistem

Generator Sertifikat XCODE dirancang untuk mempermudah operator dan administrator dalam mengelola data kegiatan, peserta, penomoran resmi, hingga penerbitan sertifikat tanpa risiko nomor ganda atau inkonsistensi format.

### Tujuan Utama:
1. **Kecepatan:** Operator dapat menerbitkan sertifikat resmi dalam waktu kurang dari satu menit.
2. **Akurasi & Integritas Penomoran:** Penomoran sertifikat mengikuti formula baku XCODE secara otomatis tanpa duplikasi, bahkan pada kondisi permintaan penerbitan bersamaan (*concurrent issuance*).
3. **Keterulangan (Reproducibility):** Setiap sertifikat yang telah diterbitkan tersimpan dalam riwayat dan dapat diunduh ulang dengan hasil data cetak yang identik secara visual.
4. **Keamanan & Akuntabilitas:** Hak akses berbasis peran (Admin dan Operator), proteksi akun admin, serta pencatatan audit log lengkap untuk setiap aksi administratif dan mutasi data.

---

## 2. Tech Stack & Dependensi

* **Framework Backend:** Laravel 12 (PHP 8.2+)
* **Basis Data:**
  * **Development & Testing:** SQLite / MySQL 8 / MariaDB 10.6+
  * **Produksi:** MySQL 8 / MariaDB 10.6+ melalui Eloquent ORM & Query Builder
* **Frontend & Tampilan:** Blade Templating Engine, Tailwind CSS v4, Vite 7
* **Tipografi & Ikon:**
  * `@fontsource-variable/geist` & `@fontsource-variable/geist-mono`
  * `@phosphor-icons/web`
* **PDF Rendering:** `PdfRenderer` biner compliant `%PDF-1.4` dengan dukungan watermark (*PRATINJAU*, *DIBATALKAN*), siap diintegrasikan dengan modul layout TCPDF.
* **Testing:** PHPUnit 11.5 dengan unit testing dan feature testing berbasis SQLite in-memory.

---

## 3. Persyaratan Sistem

Sebelum menjalankan proyek, pastikan perangkat telah terpasang:
* **PHP:** Versi 8.2 atau lebih baru (direkomendasikan PHP 8.3 / 8.4 / 8.5).
  * Ekstensi PHP wajib: `pdo`, `pdo_sqlite` (untuk testing/dev), `pdo_mysql` (untuk database MySQL), `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`.
* **Composer:** Versi 2.x
* **Node.js:** Versi 18.x atau lebih baru & **NPM** Versi 9.x atau lebih baru.

---

## 4. Panduan Instalasi & Konfigurasi

### Langkah 1: Kloning & Masuk ke Direktori Proyek
```bash
git clone https://github.com/bima-git/sertigenerator.git
cd sertigenerator
```

### Langkah 2: Instalasi Dependensi Backend & Frontend
```bash
# Instalasi paket PHP Composer
composer install

# Instalasi paket JavaScript NPM
npm install
```

### Langkah 3: Konfigurasi File Lingkungan (.env)
Salin berkas contoh konfigurasi `.env.example` menjadi `.env`:
```bash
# Windows PowerShell
copy .env.example .env

# Linux / macOS
cp .env.example .env
```

Generate application encryption key:
```bash
php artisan key:generate
```

### Langkah 4: Pengaturan Basis Data pada `.env`
Buka file `.env` dan sesuaikan driver basis data:

#### Opsi A: Menggunakan SQLite (Rekomendasi Cepat untuk Dev Lokal)
Pastikan berkas `database/database.sqlite` sudah ada:
```bash
# Windows PowerShell
New-Item -ItemType File -Path database\database.sqlite -Force

# Linux / macOS
touch database/database.sqlite
```
Konfigurasi pada `.env`:
```dotenv
DB_CONNECTION=sqlite
```

#### Opsi B: Menggunakan MySQL / MariaDB
Buat database bernama `sertigenerator` pada server MySQL Anda, lalu atur pada `.env`:
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sertigenerator
DB_USERNAME=root
DB_PASSWORD=rahasia_anda
```

### Langkah 5: Migrasi Basis Data
Jalankan migrasi skema tabel (users, events, certificates, audit_logs, sessions, cache, jobs):
```bash
php artisan migrate
```

### Langkah 6: Konfigurasi Akun Administrator Awal & Data Seeder
Pada file `.env`, tentukan kredensial akun administrator awal yang akan dibuat oleh seeder.

> [!WARNING]
> **Keamanan Kredensial:** Pengembang **wajib** mengganti nilai `ADMIN_PASSWORD` pada file `.env` lokal dengan kata sandi yang unik dan kuat (minimal 12 karakter kombinasi huruf, angka, dan simbol) sebelum menjalankan perintah seeder. Jangan pernah membiarkan nilai placeholder default digunakan pada lingkungan staging atau produksi!

Contoh konfigurasi pada `.env`:
```dotenv
ADMIN_USERNAME=admin
ADMIN_NAMA="Administrator XCODE"
ADMIN_PASSWORD="GantiDenganPasswordKuatDanUnikMinimal12Karakter!"
```

Jalankan perintah seeder untuk membuat akun Admin dan data master kegiatan XCODE:
```bash
# Membuat akun admin awal (membaca variabel ADMIN_* pada .env)
php artisan db:seed --class=AdminUserSeeder

# Mengisi master data kegiatan resmi dari dokumen aturan kode XCODE
php artisan db:seed --class=EventSeeder

# ATAU jalankan seluruh seeder sekaligus:
php artisan db:seed
```

---

## 5. Menjalankan Aplikasi

### Mode Pengembangan (Development)
Jalankan server aplikasi Laravel dan asset bundler Vite secara paralel:

```bash
# Terminal 1: Web Server Laravel
php artisan serve

# Terminal 2: Vite Dev Server
npm run dev
```

Aplikasi dapat diakses melalui browser pada alamat:
`http://127.0.0.1:8000`

### Build Aset Frontend untuk Produksi
Jika ingin mengkompilasi file CSS dan JavaScript ke direktori `public/build`:
```bash
npm run build
```

---

## 6. Fitur yang Tersedia (Kondisi Aktual Repository)

### A. Autentikasi & Keamanan Akun
* **Masuk & Keluar:** Login menggunakan username dan password ter-hash (`/masuk`), logout dengan pembersihan sesi aman (`/keluar`).
* **Proteksi Percobaan Masuk:** Penguncian akun otomatis selama 10 menit setelah 5 kali gagal login berturut-turut.
* **Manajemen Sesi:** Sesi kedaluwarsa otomatis setelah 8 jam (480 menit) tanpa aktivitas, regenerasi token sesi saat login untuk mencegah session fixation.
* **Pengaturan Akun Pengguna:** Pengguna dapat memperbarui nama dan mengganti kata sandi mandiri (`/akun`).

### B. Event Management (Backend Administrasi Kegiatan)
* **Tambah Kegiatan:** Admin dapat mendaftarkan kegiatan baru (`/admin/kegiatan/baru`) dengan atribut jenis (seminar/bootcamp), kode, judul, penerbit, program bawaan, opsi nomor, dan opsi QR.
* **Edit Kegiatan:** Memperbarui data kegiatan tanpa merusak data cetak historis sertifikat lama (`/admin/kegiatan/{id}/ubah`).
* **Aktif / Nonaktif Kegiatan:** Mengubah status kegiatan (`/admin/kegiatan/{id}/aktif`). Kegiatan nonaktif otomatis disembunyikan dari dropdown penerbitan.
* **Integritas Data:** Tidak ada rute penghapusan permanen kegiatan demi menjaga riwayat integritas sertifikat.
* **Dukungan Kode Ganda:** Kode kegiatan yang sama antar-versi kegiatan diizinkan sesuai aturan bisnis PRD KG-04.
* **Pencarian & Penyaringan:** Pencarian cepat menurut kode/judul dan filter jenis kegiatan.

### C. User Management (Backend Administrasi Pengguna)
* **Tambah Pengguna Baru:** Admin dapat menambahkan operator atau admin baru (`/admin/pengguna/baru`). Sistem menghasilkan kata sandi sementara yang aman secara otomatis dan menampilkannya sekali di layar.
* **Edit Pengguna:** Memperbarui data pengguna, peran, dan status akun aktif/nonaktif (`/admin/pengguna/{id}/ubah`).
* **Reset Kata Sandi:** Admin dapat mereset kata sandi pengguna (`/admin/pengguna/{id}/reset-sandi`), menghasilkan kata sandi sementara baru, serta membuka kunci akun yang terkunci akibat kegagalan login.
* **Proteksi Keamanan Berlapis (Server-Side):**
  * *Self-deactivation protection:* Admin tidak dapat menonaktifkan akunnya sendiri.
  * *Self-demotion protection:* Admin tidak dapat menurunkan perannya sendiri menjadi operator.
  * *Last active admin protection:* Admin aktif terakhir di sistem dilindungi dari penonaktifan maupun penurunan peran, menjamin sistem selalu memiliki minimal satu administrator aktif.

### D. Penomoran Sertifikat Otomatis (Numbering Engine)
* **Formula Resmi:** Mengikuti format baku: `{kode}/{seri}/{urut}/{penerbit}/{bulan_romawi}/{tahun}` (contoh: `35/001/003/Xcodetraining/X/2026`).
* **Alokasi Nomor Atomik:** Menggunakan penguncian transaksi database baris kegiatan (`lockForUpdate`) untuk mencegah nomor ganda saat ada banyak permintaan bersamaan.
* **Pencegahan Penggunaan Ulang:** Sertifikat yang dibatalkan tetap dihitung dalam penentuan nomor urut agar nomor urut lama tidak dipakai kembali.

### E. Formulir Penerbitan Sertifikat
* **Penerbitan Interaktif:** Formulir adaptif untuk jenis Seminar/Webinar dan Bootcamp/Magang (`/terbitkan`).
* **Saran Nomor Otomatis:** Perhitungan nomor urut berikutnya secara otomatis melalui endpoint `/api/nomor`.
* **Pratinjau Instan:** Pratinjau PDF di tab baru dengan tanda air `PRATINJAU` tanpa menyimpan baris ke database (`/pratinjau`).
* **Penerbitan Atomik:** Transaksi penyimpanan database, verifikasi nomor unik, render PDF, dan unduhan berkas langsung (`/terbitkan`).

### F. Riwayat & Pembatalan Sertifikat
* **Pencarian & Saringan Riwayat:** Riwayat penerbitan dengan filter nama peserta, nomor, jenis, status, dan rentang tanggal (`/riwayat`).
* **Unduh Ulang Identik:** Mengunduh ulang PDF sertifikat dari salinan `data_cetak` yang tersimpan (`/riwayat/{id}/pdf`).
* **Pembatalan Resmi:** Admin dapat membatalkan sertifikat dengan mencantumkan alasan pembatalan. Sertifikat berstatus dibatalkan akan memiliki watermark `DIBATALKAN` saat diunduh ulang (`/riwayat/{id}/batalkan`).

### G. Audit Logging
* **Pencatatan Audit:** Seluruh peristiwa sistem (`masuk`, `gagal_masuk`, `terbit`, `unduh_ulang`, `batal`, `ubah_event`, `ubah_user`) dicatat ke tabel `audit_logs` bersama identitas pengguna, target, rincian tindakan, alamat IP, dan stempel waktu.
* **Keamanan Kredensial:** Kata sandi, hash kata sandi, maupun kata sandi sementara tidak pernah dicatat ke audit log.
* **Pemantauan Admin:** Admin dapat memantau dan memfilter log audit berdasarkan jenis aksi (`/admin/audit`).

---

## 7. Fitur yang Belum Tersedia / Di Luar Lingkup (Non-Scope)

Fitur-fitur berikut **belum tersedia** pada versi saat ini sesuai ruang lingkup PRD:
* Penerbitan massal otomatis melalui impor berkas CSV atau Excel.
* Pengiriman berkas sertifikat secara otomatis via email atau WhatsApp ke peserta.
* Halaman verifikasi publik sertifikat pada domain luar (`xcode.co.id/cekkodesertifikat`).
* Tanda tangan digital kriptografis (digital signature / PKI / blockchain).

---

## 8. Panduan Menjalankan Pengujian (Testing)

Aplikasi dilengkapi dengan rangkaian pengujian otomatis (*test suite*) yang mencakup unit test dan feature test. Pengujian menggunakan SQLite in-memory dan database transaction sehingga tidak mengubah data riil.

### Menjalankan Seluruh Rangkaian Pengujian:
```bash
php artisan test
```

### Menjalankan Pengujian Modul Administrasi (Event & User Management):
```bash
# Menjalankan seluruh feature test modul Admin
php artisan test tests/Feature/Admin

# Menjalankan spesifik test Event Management
php artisan test tests/Feature/Admin/EventManagementTest.php

# Menjalankan spesifik test User Management
php artisan test tests/Feature/Admin/UserManagementTest.php
```

### Menjalankan Pengujian Penomoran & Penerbitan:
```bash
# Uji unit pembangun nomor dan angka romawi
php artisan test tests/Unit/Services

# Uji konkurensi alokasi nomor dan kasus nama ekstrem
php artisan test tests/Feature/ConcurrencyAndEdgeCasesTest.php

# Uji alur penerbitan sertifikat
php artisan test tests/Feature/IssueCertificateTest.php
```

> **Catatan Pengujian Headless / CI:**  
> Untuk pengujian feature yang me-render tampilan Blade lengkap di lingkungan tanpa server Vite aktif, pengujian backend memanggil `$this->withoutVite()` agar pengujian fungsional berjalan independen dari ketersediaan build manifest asset.

---

## 9. Struktur Direktori Utama

```text
sertigenerator/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   ├── EventController.php    # Pengelolaan kegiatan admin
│   │   │   │   └── UserController.php     # Pengelolaan pengguna admin
│   │   │   ├── AccountController.php      # Pengaturan profil & kata sandi
│   │   │   ├── AuthController.php         # Masuk, keluar, & throttle
│   │   │   ├── HistoryController.php      # Riwayat & pembatalan
│   │   │   └── IssueController.php        # Formulir & aksi penerbitan
│   │   ├── Middleware/
│   │   │   └── EnsureUserIsActive.php     # Proteksi penolakan akun nonaktif
│   │   └── Requests/
│   │       ├── EventRequest.php           # Validasi formulir kegiatan
│   │       ├── IssueRequest.php           # Validasi penerbitan sertifikat
│   │       ├── LoginRequest.php           # Validasi autentikasi
│   │       └── UserRequest.php            # Validasi formulir pengguna & proteksi peran
│   ├── Models/
│   │   ├── AuditLog.php                   # Model catatan audit sistem
│   │   ├── Certificate.php                # Model sertifikat & snapshot data cetak
│   │   ├── Event.php                      # Model master data kegiatan
│   │   └── User.php                       # Model akun pengguna & peran
│   └── Services/
│       ├── AuditLogger.php                # Service pencatatan peristiwa audit
│       ├── HistoryService.php             # Logika unduh ulang & pembatalan
│       ├── IssueService.php               # Logika penerbitan atomik & penguncian nomor
│       ├── NumberAllocator.php            # Penentu nomor urut berikutnya
│       ├── NumberGenerator.php            # Pembangun nomor resmi XCODE
│       ├── PdfRenderer.php                # Generator biner PDF
│       └── Roman.php                      # Konversi angka bulan ke romawi
├── database/
│   ├── migrations/                        # Skema database terurut kronologis
│   └── seeders/
│       ├── AdminUserSeeder.php            # Pembuatan akun admin awal dari .env
│       ├── DatabaseSeeder.php             # Seeder master
│       └── EventSeeder.php                # Master kegiatan resmi XCODE
├── resources/
│   ├── css/                               # Tailwind CSS v4 & custom design tokens
│   ├── js/                                # JavaScript interaktif form & dialog
│   └── views/                             # Template antarmuka Blade
│       ├── admin/                         # Halaman kegiatan, pengguna, audit
│       ├── auth/                          # Halaman masuk
│       ├── history/                       # Halaman riwayat & detail sertifikat
│       ├── issue/                         # Halaman penerbitan sertifikat
│       └── layouts/                       # Layout bersama & navigasi
├── routes/
│   └── web.php                            # Definisi seluruh rute aplikasi
└── tests/
    ├── Feature/
    │   ├── Admin/                         # Test feature Event & User Management
    │   └── ...                            # Test autentikasi, penerbitan, riwayat
    └── Unit/                              # Test unit penomoran & angka romawi
```

---

## 10. Lisensi & Hak Cipta

Proyek ini dikembangkan secara internal untuk **PT Teknologi Server Indonesia (XCODE)**. Seluruh hak cipta desain sertifikat dan aturan penomoran merupakan milik XCODE.
