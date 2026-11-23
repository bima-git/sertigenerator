# Generator Sertifikat XCODE

**Design System**

Arsitektur, struktur kode, alur routing, tata letak, komponen, tombol, dan spesifikasi layout sertifikat PDF

**Produk:** Generator Sertifikat XCODE, aplikasi web berbasis Laravel 12 untuk menerbitkan sertifikat seminar/webinar dan bootcamp/magang sebagai PDF

**Arsitektur acuan:** Laravel 12, PHP 8.2+, Composer 2, Eloquent ORM, Blade, Vite, MySQL 8 / MariaDB 10.6+, TCPDF

**Referensi visual sertifikat:** sertifikat asli XCODE (seminar: Arief Soekardi, Mourice, Mu'ammal Hamidy. Bootcamp: Akrom Masani Iksanul Amri)

**Penyusun:** [isi nama peserta magang]

**Versi:** Draf 1.1, 18 November 2026

**Dokumen terkait:** Konsep Ide, PRD, Business Flow

---

## 1. Arsitektur sistem

Aplikasi dibangun sebagai monolit Laravel 12 dengan pola MVC Laravel. Laravel menangani routing, middleware, autentikasi, validasi, sesi, CSRF, konfigurasi, logging, dan akses database. Tampilan dirender di server menggunakan Blade. Vite digunakan untuk mengelola asset CSS dan JavaScript. JavaScript dipakai untuk interaksi form yang memang membutuhkan respons langsung.

### 1.0.1 Ketentuan tech stack wajib

| Lapisan | Teknologi | Ketentuan |
|---|---|---|
| Framework | Laravel 12 | Framework utama dan wajib; jangan mengganti dengan PHP native atau framework lain |
| Runtime | PHP 8.2+ dan Composer 2 | Ikuti persyaratan Laravel 12 dan dependensi Composer |
| Database | MySQL 8 atau MariaDB 10.6+ | Skema dikelola melalui migrations; data awal melalui seeders |
| ORM | Eloquent ORM / Query Builder | Tidak membuat koneksi PDO atau repository database manual |
| Tampilan | Blade | Layout dan komponen menggunakan Blade views/components |
| Asset frontend | Vite, CSS, JavaScript | Design tokens disimpan di CSS; JavaScript untuk form dinamis |
| PDF | TCPDF melalui Composer | PDF A4 landscape, teks selectable, QR, dan watermark |
| Autentikasi | Laravel session authentication | Username/password, session regeneration, akun aktif |
| Keamanan | Middleware, Policies/Gates, CSRF, Rate Limiter Laravel | Gunakan fitur bawaan Laravel |
| Pengujian | PHPUnit atau Pest | Unit test dan feature test |
| Deploy | Nginx atau Apache, PHP-FPM | Document root harus mengarah ke direktori `public/` |

### 1.1 Alur satu permintaan

- Peramban mengirim permintaan ke entry point Laravel di `public/index.php`.
- Laravel mencocokkan rute yang didefinisikan di `routes/web.php`.
- Middleware berjalan sesuai urutan: sesi, autentikasi, status akun, role/policy, CSRF untuk permintaan yang mengubah data, dan rate limiter.
- Controller menerima request dan menggunakan Form Request atau validator Laravel. Gagal: redirect kembali dengan error per field dan old input.
- Controller memanggil service class. Service memakai Eloquent/Query Builder dan, bila perlu, `PdfRenderer`.
- Controller mengembalikan Blade view, JSON response, atau PDF response.
- Peristiwa penting ditulis ke tabel audit; kesalahan teknis dicatat melalui Laravel Log.

### 1.2 Alur penerbitan sertifikat

- `IssueController::store` menerima `IssueRequest` (Form Request) dan menggunakan data tervalidasi dari `$request->validated()`.
- `IssueService::issue` menjalankan `DB::transaction()`.
- Model kegiatan dikunci menggunakan Eloquent `lockForUpdate()` di dalam transaksi database.
- Bila kegiatan bernomor: `NumberAllocator::next` menentukan urut (bila kosong), `NumberGenerator::build` membangun nomor, unique index pada tabel sertifikat menjadi penjaga terakhir; pemeriksaan awal dapat memakai query Eloquent.
- Model sertifikat menyimpan data, termasuk `data_cetak` dan `versi_layout`.
- `PdfRenderer::render` membuat PDF di memori.
- Gagal di langkah mana pun: transaksi di-rollback otomatis oleh `DB::transaction()`, catat log, kembalikan galat terkontrol.
- Berhasil: transaksi commit, tulis audit, kirim PDF dengan response unduhan.

Kerangka implementasi Laravel 12 (pseudocode service):

```php
return DB::transaction(function () use ($data, $user) {
    $event = Event::query()
        ->whereKey($data['event_id'])
        ->lockForUpdate()
        ->firstOrFail();

    $this->assertEventUsable($event, $data['jenis']);

    $number = null;
    if ($event->bernomor) {
        $urut = $data['urut'] ?? $this->allocator->next(
            $event->id, $data['seri'], $data['tahun']
        );
        $number = NumberGenerator::build(
            $event->kode, $data['seri'], $urut,
            $event->penerbit, $data['tanggal_terbit']
        );
    }

    $certificate = Certificate::create([
        // data peserta, nomor, data_cetak, versi_layout, user_id
    ]);

    // Render sebelum transaksi selesai agar kegagalan render me-rollback data.
    $pdf = $this->pdfRenderer->render($certificate, 'terbit');
    $this->auditLogger->log($user, 'terbit', $certificate);

    return ['certificate' => $certificate, 'nomor' => $number, 'pdf' => $pdf];
});
```

Jangan memindahkan proses render ke queue untuk alur penerbitan sinkron versi pertama, karena keberhasilan render merupakan syarat commit dan unduhan.

Saran nomor urut (sertifikat dibatalkan ikut dihitung):

```sql
SELECT COALESCE(MAX(urut), 0) + 1
FROM certificates
WHERE event_id = :event_id AND seri = :seri AND tahun = :tahun;
```

### 1.3 Alur render PDF

- `PdfRenderer::render($data, $mode)` memilih berkas layout menurut jenis dan versi layout.
- TCPDF dibuat A4 mendatar, margin dan pemecah halaman otomatis dimatikan.
- Gambar latar dipasang penuh satu halaman.
- Tiap elemen pada konfigurasi dicetak. Elemen teks variabel melewati `TextFitter` untuk memilih ukuran huruf dan jumlah baris.
- Bila kegiatan memakai QR, QR dicetak sesuai mode QR.
- Mode `pratinjau` menambah tanda air PRATINJAU. Mode `dibatalkan` menambah tanda air DIBATALKAN.
- Hasil dikembalikan sebagai string byte PDF, tidak ditulis ke disk.

### 1.4 Struktur folder Laravel 12

Gunakan struktur standar Laravel. Tambahkan kelas dan direktori domain seperlunya, tanpa membuat kerangka MVC atau router sendiri.

| Jalur | Isi yang disarankan |
|---|---|
| `app/Http/Controllers` | AuthController, IssueController, HistoryController, AccountController, Admin controllers |
| `app/Http/Requests` | Form Request untuk login, penerbitan, kegiatan, pengguna, pembatalan |
| `app/Http/Middleware` | Middleware status akun dan pemeriksaan role bila belum ditangani policy |
| `app/Models` | User, Event, Certificate, AuditLog |
| `app/Services` | NumberGenerator, NumberAllocator, IssueService, PdfRenderer, TextFitter, DateId, AuditLogger |
| `app/Policies` | CertificatePolicy dan policy resource admin |
| `resources/views` | `layouts/`, `components/`, `auth/`, `issue/`, `history/`, `admin/`, `errors/` |
| `resources/css` dan `resources/js` | CSS design tokens dan JavaScript form dinamis yang dibangun oleh Vite |
| `routes/web.php` | Semua named routes aplikasi |
| `config/` | Konfigurasi aplikasi, QR, dan lokasi layout PDF |
| `config/certificate-layouts/` | Konfigurasi layout seminar/bootcamp dan arsip versi layout |
| `database/migrations` | Skema users, events, certificates, audit_logs |
| `database/seeders` | EventSeeder dan AdminUserSeeder |
| `storage/app` | File internal/private bila diperlukan; jangan simpan file sensitif di `public` |
| `storage/logs` | Log Laravel |
| `tests/Unit` dan `tests/Feature` | Uji generator nomor, allocator, PDF, autentikasi, otorisasi, penerbitan, riwayat |
| `docs/` | Dokumen konsep, PRD, business flow, design system, progress, uji manual |

### 1.5 Konfigurasi dan deploy

- Variabel lingkungan dikelola melalui `.env` dan `config/*.php`. Minimal: `APP_ENV`, `APP_URL`, `APP_TIMEZONE=Asia/Jakarta`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `SESSION_LIFETIME`, `QR_MODE`, `QR_URL`, dan `KOTA_TERBIT`.
- `.env` tidak masuk Git. Sediakan `.env.example` tanpa kredensial asli.
- Instalasi dependensi menggunakan Composer. Asset CSS/JS dikelola dengan Vite (`npm install` dan `npm run build`).
- Skema database dijalankan dengan `php artisan migrate`; data kegiatan dan admin awal melalui seeder Artisan.
- Jalankan `php artisan config:cache` dan `php artisan route:cache` sesuai kebutuhan deployment; jangan cache konfigurasi saat `.env` belum benar.
- Web server harus mengarah ke direktori `public/`, bukan root proyek. Pastikan `storage/` dan `bootstrap/cache/` dapat ditulis oleh proses PHP.
- Docker Compose dapat dipakai untuk pengembangan dengan service `app` (PHP-FPM), `web` (Nginx), `db` (MySQL), dan node/build stage untuk asset bila diperlukan. Produksi mengikuti hosting coach.
- TCPDF dipasang melalui Composer. Font yang diperlukan dikonversi/diintegrasikan sesuai dukungan TCPDF dan disimpan di lokasi internal aplikasi.
- Zona waktu ditetapkan pada `config/app.php` menjadi `Asia/Jakarta`.

## 2. Peran dan penjaga akses

Gunakan middleware dan authorization Laravel. Semua pengguna yang login dapat melihat, membuka, dan mengunduh ulang semua sertifikat; hanya Admin yang dapat membatalkan (diputuskan 2026-11-23). Jangan membuat middleware CSRF, session, atau rate limiter manual.

| Middleware | Fungsi | Bila gagal |
|---|---|---|
| `auth` | Harus masuk dan akun aktif | Ke halaman Masuk |
| `role:admin` (middleware aplikasi) atau Gate/Policy | Peran Admin | Halaman 403 dengan tautan ke Terbitkan |
| Middleware CSRF bawaan Laravel | Token CSRF valid pada seluruh form web yang mengubah data | Halaman 419 dengan tombol Muat Ulang |
| `throttle` | Batas laju: login 10 per menit per IP, penerbitan dan pratinjau 30 per menit per pengguna | Pesan menunggu beserta lamanya |
| Akses sertifikat | Semua pengguna yang login membuka semua sertifikat; hanya Admin yang membatalkan | 403 untuk pembatalan oleh Operator |

---

## 3. Alur routing

URI berbahasa Indonesia. Setelah masuk, `/` mengarahkan ke `/terbitkan`. Rute didefinisikan sebagai named routes di `routes/web.php`; nama route pada tabel berikut dipertahankan sebagai kontrak UI dan pengujian.

### 3.1 Tanpa sesi

| Metode | URI | Nama rute | Controller Laravel |
|---|---|---|---|
| GET | /masuk | login | AuthController@create |
| POST | /masuk | login.attempt | AuthController@store (throttle) |

### 3.2 Pengguna masuk (auth)

| Metode | URI | Nama rute | Controller Laravel |
|---|---|---|---|
| GET | / | home | Pengalihan ke issue.form |
| POST | /keluar | logout | AuthController@destroy |
| GET | /akun | account.edit | AccountController@edit |
| POST | /akun/sandi | account.password | AccountController@password |

### 3.3 Operator dan Admin

| Metode | URI | Nama rute | Controller Laravel |
|---|---|---|---|
| GET | /terbitkan | issue.form | IssueController@create |
| GET | /api/kegiatan | api.events | IssueController@events (JSON, parameter `jenis`, `q`) |
| GET | /api/nomor | api.number | IssueController@suggest (JSON: saran urut dan pratinjau nomor) |
| POST | /pratinjau | issue.preview | IssueController@preview (PDF inline, throttle) |
| POST | /terbitkan | issue.store | IssueController@store (PDF unduhan, throttle) |
| GET | /riwayat | history.index | HistoryController@index |
| GET | /riwayat/{id} | history.show | HistoryController@show (owner-or-admin) |
| GET | /riwayat/{id}/pdf | history.pdf | HistoryController@download (Policy: owner/admin) |

### 3.4 Admin (awalan /admin, role:admin)

| Metode | URI | Nama rute | Controller Laravel |
|---|---|---|---|
| POST | /riwayat/{id}/batalkan | history.cancel | HistoryController@cancel (role:admin) |
| GET | /admin/kegiatan | admin.events.index | Admin\EventController@index |
| GET, POST | /admin/kegiatan/baru | admin.events.create, store | Admin\EventController@create, @store |
| GET, POST | /admin/kegiatan/{id}/ubah | admin.events.edit, update | Admin\EventController@edit, @update |
| POST | /admin/kegiatan/{id}/aktif | admin.events.toggle | Admin\EventController@toggle |
| GET | /admin/pengguna | admin.users.index | Admin\UserController@index |
| GET, POST | /admin/pengguna/baru | admin.users.create, store | Admin\UserController@create, @store |
| GET, POST | /admin/pengguna/{id}/ubah | admin.users.edit, update | Admin\UserController@edit, @update |
| POST | /admin/pengguna/{id}/reset-sandi | admin.users.reset | Admin\UserController@reset |
| GET | /admin/audit | admin.audit | Admin\AuditController@index |
| GET | /admin/uji-layout | admin.layout.test | Admin\LayoutTestController@show (PDF contoh, parameter `jenis`, `grid`, `nama`) |

### 3.5 Peta pengalihan

| Kejadian | Tujuan |
|---|---|
| Masuk berhasil | /terbitkan |
| Sesi habis | /masuk lalu kembali ke halaman semula |
| Akses admin tanpa peran | Halaman 403 |
| Terbitkan berhasil | Unduhan PDF dan tetap di /terbitkan dengan pesan kilat dan kegiatan tetap terpilih |
| Validasi gagal | Kembali ke form dengan data lama |
| Simpan kegiatan atau pengguna | Kembali ke daftar dengan pesan kilat |

---

## 4. Arah visual

Aplikasi ini alat internal. Tampilannya bersih, cepat, dan memudahkan pekerjaan berulang, bukan menarik perhatian. Identitas warna diambil dari sertifikat XCODE: merah pita sebagai warna utama dan oranye sebagai aksen. Nilai warna persisnya diambil dari gambar latar saat Fase 0 dan dicatat di `docs/DESAIN.md`.

**Aturan pakai:** berkas tampilan tidak menyimpan nilai hex. Semua warna lewat variabel CSS, sehingga mengganti palet cukup di satu berkas.

### 4.1 Token warna semantik

| Token | Dipakai untuk |
|---|---|
| `--surface` | Latar halaman |
| `--surface-raised` | Kartu, panel, modal |
| `--surface-alt` | Latar baris selang-seling dan area ringkasan |
| `--fg` | Judul |
| `--fg-body` | Teks isi |
| `--fg-muted` | Keterangan dan teks bantu |
| `--line` | Garis dan batas |
| `--brand` | Warna utama (merah pita XCODE) untuk tombol utama dan tautan aktif |
| `--brand-fg` | Teks di atas warna utama |
| `--accent` | Aksen (oranye) untuk sorotan kecil |
| `--success`, `--warning`, `--danger`, `--info` | Status dan pesan |

Kontras teks isi minimal 4,5 banding 1. Hanya tema terang pada versi 1.

### 4.2 Tipografi, jarak, sudut

| Elemen | Aturan |
|---|---|
| Font | Satu keluarga sans-serif sistem untuk seluruh antarmuka. Font sertifikat terpisah dan hanya dipakai di PDF |
| Skala huruf | H1 28, H2 22, H3 18, isi 16, kecil 14, label 12 |
| Tinggi baris | Judul 1,2. Isi 1,6 |
| Kisi | Kontainer maksimal 960 px. Form dua kolom di desktop, satu kolom di ponsel |
| Jarak | Kelipatan 4 px. Antar seksi 32 px |
| Sudut | Satu nilai untuk tombol dan masukan, kartu satu tingkat lebih besar |
| Bayangan | Satu tingkat untuk kartu dan modal |
| Ikon | Set garis 20 px, tebal seragam, selalu disertai teks kecuali tombol ikon |

### 4.3 Gerak

Seminimal mungkin.

| Pola | Aturan |
|---|---|
| Sorot tombol dan baris | 150 ms, tanpa lompatan tata letak |
| Pesan kilat | Muncul tanpa animasi, hilang sendiri setelah 6 detik kecuali galat |
| Tombol memuat | Teks berganti kata kerja berjalan, tombol terkunci |
| Kurangi gerak | `prefers-reduced-motion` mematikan semua transisi |

---

## 5. Tata letak

| Tata letak | Dipakai oleh | Susunan |
|---|---|---|
| `layouts/auth` | Halaman Masuk | Satu kartu di tengah dengan logo, form, dan pesan |
| `layouts/app` | Semua halaman setelah masuk | Navbar atas, isi lebar terbatas, navigasi bawah di ponsel |
| `errors/layout` | 403, 404, 419, 429, 500 | Pesan, penjelasan, satu tombol tindakan |

### 5.1 Navigasi

| Area | Isi menu |
|---|---|
| Navbar operator | Terbitkan, Riwayat. Kanan: nama pengguna dengan menu Akun dan Keluar |
| Navbar admin | Terbitkan, Riwayat, Kegiatan, Pengguna, Uji Layout, Audit. Kanan: menu Akun dan Keluar |
| Navigasi bawah (ponsel) | Terbitkan, Riwayat, Menu (membuka sisa tautan) |

### 5.2 Titik henti

Dirancang dari 360 px. Titik henti: sm 640, md 768, lg 1024. Form dua kolom mulai dari md. Tabel menjadi kartu bertumpuk di bawah md.

### 5.3 Susunan halaman Terbitkan

| Area | Isi |
|---|---|
| Kolom kiri (form) | Segmen Jenis, field sesuai jenis, tombol aksi |
| Kolom kanan (panel Ringkasan) | Nomor lengkap, nama berkas, judul yang akan tercetak, daftar peringatan, tautan Lihat Layout Contoh |
| Di ponsel | Panel Ringkasan pindah di atas tombol aksi |

---

## 6. Komponen

Komponen antarmuka dibuat sebagai Blade components di `resources/views/components` dan dipanggil dengan sintaks komponen Blade.

| Komponen | Fungsi | Catatan |
|---|---|---|
| `button`, `link-button` | Tombol dan tautan bergaya tombol | Lihat bagian 7 |
| `card` | Wadah isi | Opsi: polos, sorot |
| `field`, `text-input`, `select`, `date-input`, `number-input`, `textarea` | Masukan form dengan label dan galat | Label selalu terlihat, bukan hanya placeholder |
| `searchable-select` | Dropdown kegiatan dengan pencarian | Mendukung ratusan kegiatan, mencari menurut kode dan judul |
| `segmented` | Pilihan jenis sertifikat | Dua pilihan, satu aktif |
| `badge` | Status | Warna dari token status, selalu disertai teks |
| `number-preview` | Pratinjau nomor lengkap | Memecah nomor per segmen dengan label kecil di bawahnya |
| `summary-panel` | Panel Ringkasan penerbitan | Nomor, nama berkas, judul tercetak, peringatan |
| `alert` | Peringatan dan galat dalam halaman | Varian info, warning, danger |
| `flash` | Pesan kilat | Hilang sendiri kecuali galat |
| `modal` | Dialog konfirmasi | Fokus terkunci, tutup dengan Esc, wajib memuat alasan untuk pembatalan |
| `table` | Tabel data | Menjadi kartu di ponsel |
| `pagination` | Paginasi | 25 baris per halaman |
| `empty-state` | Keadaan kosong | Satu kalimat dan satu tombol tindakan |
| `page-header` | Judul halaman | Judul, keterangan, tombol tindakan utama di kanan |
| `status-badge-cert` | Status sertifikat | Terbit (success), Dibatalkan (danger) |
| `filter-bar` | Bilah cari dan saringan riwayat | Chip jenis dan status, rentang tanggal |

### 6.1 Keadaan halaman

- **Memuat.** Tombol aksi berganti kata kerja berjalan dan terkunci. Tabel menampilkan kerangka abu-abu berbentuk baris.
- **Kosong.** Menyebut apa yang belum ada dan tombol untuk memulainya, misalnya Belum ada sertifikat. Terbitkan yang pertama.
- **Galat.** Menyebut apa yang terjadi dan langkah berikutnya. Tidak ada kode galat mentah, hanya kode rujukan pendek.
- **Berhasil.** Pesan kilat yang menyebut hasil dan nomor.

---

## 7. Tombol

### 7.1 Varian

| Varian | Tampilan | Dipakai untuk | Batas |
|---|---|---|---|
| primary | Latar brand, teks brand-fg | Tindakan utama halaman | Satu per tampilan |
| secondary | Latar surface-raised, garis line | Tindakan pendamping | Bebas |
| ghost | Tanpa latar, teks fg | Tindakan ringan | Bebas |
| danger | Latar danger | Batalkan sertifikat, nonaktifkan | Selalu dengan konfirmasi |
| icon | Ikon saja, lingkaran | Menutup, menyalin | Wajib `aria-label` |
| segmen | Deret pilihan, satu aktif berlatar brand | Memilih jenis sertifikat | Dua sampai lima pilihan |
| chip | Pil kecil bergaris, aktif berlatar brand | Saringan daftar | Dapat digulir mendatar di ponsel |
| sakelar | Tuas hidup dan mati dengan label | Bernomor, pakai QR, garis bantu | Keadaan juga ditulis dengan teks |

### 7.2 Ukuran dan keadaan

| Ukuran | Tinggi | Huruf | Dipakai |
|---|---|---|---|
| sm | 36 px | 14 px | Baris tabel, tindakan kecil |
| md | 44 px | 14 sampai 16 px | Bawaan semua form |
| lg | 52 px | 16 px | Terbitkan & Unduh, Masuk |

| Keadaan | Aturan |
|---|---|
| Bawaan | Sesuai varian |
| Sorot | Latar satu tingkat lebih gelap atau terang, 150 ms |
| Fokus | Cincin 2 px warna accent dengan jarak 2 px, selalu terlihat saat memakai papan ketik |
| Ditekan | Skala 0,98 |
| Memuat | Teks berganti kata kerja berjalan (Menerbitkan, Membuat pratinjau, Menyimpan), tombol terkunci, lebar tidak berubah |
| Nonaktif | Opasitas 50%, kursor dilarang, alasan ditulis di dekatnya |

- Label tombol menyebut aksinya: Terbitkan & Unduh, Unduh Ulang, Tambah Kegiatan. Tidak ada Kirim atau OK.
- Ikon di kiri label. Tombol ikon saja wajib punya `aria-label`.
- Aksi yang mengubah data memakai form POST dengan token CSRF, bukan tautan.
- Di ponsel tombol utama form selebar penuh dan menempel di bawah layar.
- Mencegah kirim ganda: tombol terkunci setelah diklik sampai respons kembali. Untuk unduhan, tombol dibuka kembali setelah beberapa detik.

### 7.3 Rincian tombol per halaman

#### Masuk

| Tombol | Varian | Aksi | Keadaan khusus |
|---|---|---|---|
| Masuk | primary lg | POST /masuk | Memuat: Memeriksa. Nonaktif sementara saat akun terkunci, sisa waktu ditulis |
| Tampilkan kata sandi | icon | Mengganti tampilan masukan | `aria-label` wajib |

#### Terbitkan

| Tombol | Varian | Aksi | Keadaan khusus |
|---|---|---|---|
| Seminar/Webinar, Bootcamp/Magang | segmen | Menampilkan field sesuai jenis | Mengganti jenis tidak menghapus nama peserta |
| Gunakan Nomor Saran | ghost sm | Mengisi nomor urut dengan saran sistem | Muncul bila nomor urut diubah dari saran |
| Rapikan Kapitalisasi | ghost sm | Mengubah huruf awal tiap kata pada nama | Tidak berjalan otomatis |
| Kembalikan Judul Asli | ghost sm | Mengisi judul cetak dari judul kegiatan | Muncul bila judul cetak diubah |
| Pratinjau | secondary md | POST /pratinjau, hasil di tab baru | Memuat: Membuat pratinjau. Tidak memakai nomor |
| Terbitkan & Unduh | primary lg | POST /terbitkan, hasil unduhan | Nonaktif sampai kolom wajib terisi, alasan ditulis. Memuat: Menerbitkan |
| Kosongkan Form | ghost md | Mengosongkan field kecuali jenis dan kegiatan | Dengan konfirmasi bila ada isian |
| Lihat Layout Contoh | ghost sm | Membuka gambar tata letak jenis terpilih di modal | |

#### Riwayat

| Tombol | Varian | Aksi | Keadaan khusus |
|---|---|---|---|
| Cari | primary md | GET riwayat dengan parameter | |
| Hapus Saringan | ghost md | Mengosongkan semua saringan | Tersembunyi bila tidak ada saringan |
| Seminar, Bootcamp, Terbit, Dibatalkan | chip | Menyaring daftar | Dapat digabung |
| Lihat | ghost sm | Ke detail sertifikat | |
| Unduh Ulang | secondary sm | GET riwayat/{id}/pdf | Pada sertifikat dibatalkan tampil peringatan dan PDF bertanda air |
| Batalkan | danger sm | Modal meminta alasan, POST batalkan | Hanya Admin dan hanya status Terbit |
| Salin Nomor | icon | Menyalin nomor lengkap | Berganti Tersalin 2 detik. `aria-label` wajib |

#### Kegiatan (Admin)

| Tombol | Varian | Aksi | Keadaan khusus |
|---|---|---|---|
| Tambah Kegiatan | primary md | Ke form kegiatan baru | |
| Simpan Kegiatan | primary md | POST simpan | Bila kode sudah dipakai, modal konfirmasi dulu |
| Bernomor, Pakai QR | sakelar | Mengatur perilaku kegiatan | Untuk jenis bootcamp bawaannya mati |
| Nonaktifkan / Aktifkan | danger sm / secondary sm | POST aktif | Nonaktifkan dengan konfirmasi |
| Ubah | ghost sm | Ke form ubah | |

#### Pengguna (Admin)

| Tombol | Varian | Aksi | Keadaan khusus |
|---|---|---|---|
| Tambah Pengguna | primary md | Ke form baru | |
| Simpan Pengguna | primary md | POST simpan | Menolak bila melanggar aturan admin aktif terakhir |
| Reset Sandi | secondary sm | POST reset-sandi | Kata sandi sementara tampil satu kali |
| Nonaktifkan | danger sm | POST dengan konfirmasi | Tidak tersedia untuk diri sendiri |

#### Uji Layout (Admin)

| Tombol | Varian | Aksi | Keadaan khusus |
|---|---|---|---|
| Garis Bantu | sakelar | Menambah grid milimeter dan label koordinat pada PDF uji | |
| Buat PDF Uji | primary md | GET uji-layout dengan parameter | Nama contoh dapat diubah untuk menguji nama panjang |
| Nama Uji Cepat | chip | Mengisi nama contoh: pendek, panjang, apostrof, bergelar, 70 karakter | |

---

## 8. Spesifikasi form Terbitkan

### 8.1 Field seminar/webinar

| Field | Tipe | Aturan | Catatan tampilan |
|---|---|---|---|
| Kegiatan | searchable-select | Wajib, hanya kegiatan seminar aktif | Menampilkan `kode - judul` |
| Judul pada sertifikat | text-input | Terisi dari judul kegiatan, dapat diubah, maksimum 200 karakter | Peringatan bila lebih dari dua baris pada ukuran huruf minimum |
| Nama lengkap peserta | text-input | Wajib, 2 sampai 100 karakter | Penghitung karakter. Tombol Rapikan Kapitalisasi |
| Seri | number-input | 1 sampai 999, bawaan 1 | Ditampilkan 3 digit |
| Nomor urut | number-input | 1 sampai 999, terisi saran | Keterangan: mengikuti daftar peserta |
| Tanggal terbit | date-input | Wajib, bawaan hari ini | Peringatan bila lebih dari satu tahun dari hari ini |

### 8.2 Field bootcamp/magang

| Field | Tipe | Aturan | Catatan tampilan |
|---|---|---|---|
| Bootcamp | searchable-select | Wajib, hanya kegiatan bootcamp aktif | Contoh: X-Code Bootcamp #14 |
| Program | text-input | Wajib, maksimum 150 karakter, terisi dari program bawaan | Contoh: Penetration Testing & Cyber Security Engineer |
| Nama lengkap peserta | text-input | Seperti seminar | |
| Perusahaan penempatan | text-input | Wajib, maksimum 150 karakter | Contoh: PT Teknologi Keamanan Digital |
| Tanggal mulai | date-input | Wajib | |
| Tanggal selesai | date-input | Wajib, tidak sebelum tanggal mulai | |
| Tanggal terbit | date-input | Wajib, bawaan hari ini | |

### 8.3 Panel Ringkasan

| Baris | Isi |
|---|---|
| Nomor sertifikat | Nomor lengkap dengan segmen berlabel. Untuk bootcamp tertulis Tanpa nomor |
| Nama berkas | `Sertifikat_Seminar_Muammal_Hamidy.pdf` |
| Judul tercetak | Satu atau dua baris menurut hasil pengepasan teks |
| Peringatan | Nama kembar, tanggal jauh, judul terlalu panjang, nama sangat panjang |

### 8.4 Perilaku skrip form

- Memilih kegiatan memanggil `/api/nomor` dan mengisi saran urut serta pratinjau nomor.
- Mengubah seri atau tanggal terbit memanggil `/api/nomor` lagi.
- Saran urut tidak menimpa nomor yang sudah diubah operator, kecuali operator menekan Gunakan Nomor Saran.
- Mengganti jenis menyembunyikan field yang tidak dipakai dan mengosongkan nilainya sebelum dikirim.
- Semua validasi dilakukan ulang di server.

---

## 9. Spesifikasi sertifikat PDF

### 9.1 Umum

| Aspek | Ketentuan |
|---|---|
| Ukuran | A4 mendatar, 297 x 210 mm, satu halaman |
| Satuan | Milimeter, titik asal pojok kiri atas |
| Latar | `assets/templates/background.png`, minimal 3508 x 2480 px. Dipakai untuk kedua jenis |
| Konversi dari sampel | 1 px pada render 1308 x 924 = 0,2271 mm |
| Font | Serif bermetrik Times (Tinos atau Liberation Serif) dibenamkan. **[ASUMSI]** sampai file font asli diterima |
| Warna teks | Hampir hitam, sama dengan sampel |
| Jenis perataan | L = kiri, C = tengah pada koordinat X yang diberikan |
| Rujukan Y | Garis dasar teks (baseline). TCPDF menaruh teks dari batas atas sel, sehingga renderer menambah tinggi ascent font |

**Seluruh angka di bawah diturunkan dari sampel dan bersifat perkiraan awal [KALIBRASI].** Ukuran huruf dikalibrasi dengan lebar teks pada sampel, bukan dengan melihat tinggi huruf.

### 9.2 Seminar/Webinar

| Elemen | Rata | X (mm) | Baseline Y (mm) | Ukuran awal | Catatan |
|---|---|---|---|---|---|
| Judul besar `SERTIFIKAT SEMINAR` | L | 9,5 | 36,3 | ~40 pt | Lebar pada sampel ~153 mm |
| Judul kegiatan | L | 9,5 | 46,3 | ~18 pt | Lebar maksimum 210 mm, maksimum dua baris, jarak baris 7,5 mm, minimum 12 pt |
| Nama peserta | C | 120 | 151,7 | ~28 pt | Lebar maksimum 148 mm, minimum 16 pt |
| Nomor sertifikat | C | 259 | 77,2 | ~10 pt | Lebar pada sampel ~55 mm, maksimum 58 mm |
| QR code | - | 240,3 (kiri) | 87,0 (atas) | 36,5 mm persegi | Isi menurut mode QR |
| Teks alamat verifikasi | C | 259 | 128,3 | ~8,5 pt | `xcode.co.id/cekkodesertifikat`, lebar pada sampel ~39 mm |
| Tanggal `Yogyakarta, {tanggal}` | C | 259 | 158,5 | ~14 pt | Lebar pada sampel ~62 mm |

### 9.3 Bootcamp/Magang

| Elemen | Rata | X (mm) | Baseline Y (mm) | Ukuran awal | Catatan |
|---|---|---|---|---|---|
| Judul besar `SERTIFIKAT MAGANG` | L | 9,5 | 36,3 | ~40 pt | Sama dengan seminar |
| Subjudul baris 1 (bootcamp) | L | 9,5 | 47,7 | ~19 pt | Contoh: X-Code Bootcamp #14 |
| Subjudul baris 2 (program) | L | 9,5 | 55,9 | ~19 pt | Lebar maksimum 210 mm, minimum 13 pt |
| Nama peserta | C | 120 | 151,7 | ~28 pt | Seperti seminar |
| Paragraf baris 1 | C | 120,4 | 165,8 | ~14 pt | Lebar maksimum 165 mm |
| Paragraf baris 2 | C | 120,4 | 171,0 | ~14 pt | Jarak baris ~5,2 mm |
| Tanggal `Yogyakarta, {tanggal}` | C | 259 | 158,5 | ~14 pt | Seperti seminar |

Isi paragraf: `Peserta telah melaksanakan program magang di {perusahaan}` pada baris 1 dan `terhitung dari {tanggal mulai} hingga {tanggal selesai}.` pada baris 2. Bila baris 1 terlalu panjang, huruf mengecil sampai minimum 11 pt.

### 9.4 Elemen tetap

Elemen berikut idealnya sudah menjadi bagian gambar latar. Bila latar dari coach tidak memuatnya, cetak dari konfigurasi dengan nilai cadangan berikut.

| Elemen | Cadangan | Catatan |
|---|---|---|
| Kaligrafi `Sertifikat` dan `diberikan kepada` | Wajib dari latar | Bukan font biasa, tidak ada cadangan |
| `xcode.or.id` | C, X 259, baseline 70, ~21 pt | Seminar dan bootcamp |
| Garis di bawah nama | Garis 0,4 mm dari X 46,6 ke 194,6 pada Y 156,7 | |
| Garis tanda tangan | Garis dari X 230,5 ke 287,3 pada Y 185,3 | |
| Tanda tangan | Gambar PNG transparan, kira-kira X 244 sampai 277, Y 169 sampai 185 | Berkas `signature.png` |
| `Cynthia` | C, X 259, baseline 192,6, tebal, ~14 pt | |
| `Direktur` | C, X 259, baseline 198,2, ~11 pt | |

### 9.5 Pengepasan teks (`TextFitter`)

| Aturan | Rincian |
|---|---|
| Masukan | Teks, lebar maksimum, ukuran awal, ukuran minimum, jumlah baris maksimum |
| Langkah 1 | Ukur lebar dengan font terpasang pada ukuran awal |
| Langkah 2 | Bila meluap, turunkan ukuran 0,5 pt sampai muat atau mencapai minimum |
| Langkah 3 | Di ukuran minimum dan masih meluap: bagi pada spasi terdekat ke baris berikutnya bila jumlah baris maksimum mengizinkan |
| Langkah 4 | Bila tetap tidak muat: lempar `RenderException`. Form menampilkan peringatan sebelum terbit dan penerbitan ditolak |
| Nama dua baris | Baris pertama naik 8 mm dari garis dasar nama, baris kedua tetap di garis dasar |
| Tidak boleh | Memotong teks dengan tanda elipsis atau menyingkat nama |

### 9.6 QR code

| Mode | Isi QR | Keterangan |
|---|---|---|
| `static` (bawaan) | `https://xcode.co.id/cekkodesertifikat` | Sama untuk semua peserta. **[ASUMSI]** sampai isi QR asli dipastikan dengan memindai |
| `per_nomor` | `https://xcode.co.id/cekkodesertifikat?kode={nomor terenkode URL}` | Hanya bila sistem verifikasi mendukung |

QR dibuat dengan `write2DBarcode` jenis `QRCODE,M`, ukuran cetak minimal 25 mm dengan tepi kosong di sekitarnya.

### 9.7 Tanda air

| Mode | Teks | Tampilan |
|---|---|---|
| `pratinjau` | PRATINJAU | Diagonal, abu-abu transparan, di atas seluruh halaman |
| `dibatalkan` | DIBATALKAN | Diagonal, merah transparan, di atas seluruh halaman |
| `terbit` | Tidak ada | |

### 9.8 Struktur berkas konfigurasi layout

```php
// config/layout/seminar.php
return [
  'versi'      => '1.0',
  'background' => 'assets/templates/background.png',
  'font'       => ['serif' => 'tinos', 'serif_bold' => 'tinosb'],
  'page'       => ['size' => 'A4', 'orientation' => 'L'],
  'elements'   => [
    'judul_besar'    => ['type' => 'text', 'text' => 'SERTIFIKAT SEMINAR',
                         'x' => 9.5, 'y' => 36.3, 'align' => 'L', 'size' => 40],
    'judul_kegiatan' => ['type' => 'fit', 'field' => 'judul_cetak',
                         'x' => 9.5, 'y' => 46.3, 'align' => 'L',
                         'size' => 18, 'min_size' => 12, 'max_w' => 210, 'max_lines' => 2, 'line_h' => 7.5],
    'nama'           => ['type' => 'fit', 'field' => 'nama_peserta',
                         'x' => 120, 'y' => 151.7, 'align' => 'C',
                         'size' => 28, 'min_size' => 16, 'max_w' => 148, 'max_lines' => 2, 'line_h' => 8],
    'nomor'          => ['type' => 'fit', 'field' => 'nomor_lengkap',
                         'x' => 259, 'y' => 77.2, 'align' => 'C',
                         'size' => 10, 'min_size' => 8, 'max_w' => 58],
    'url_verifikasi' => ['type' => 'text', 'text' => 'xcode.co.id/cekkodesertifikat',
                         'x' => 259, 'y' => 128.3, 'align' => 'C', 'size' => 8.5],
    'tanggal'        => ['type' => 'text', 'field' => 'tanggal_kota',
                         'x' => 259, 'y' => 158.5, 'align' => 'C', 'size' => 14],
    'qr'             => ['type' => 'qr', 'x' => 240.3, 'y' => 87.0, 'size' => 36.5],
  ],
];
```

Aturan versi: setiap perubahan koordinat menaikkan `versi`, dan berkas versi sebelumnya disalin ke `config/layout/v{versi}/` agar unduh ulang sertifikat lama tetap sama.

### 9.9 Prosedur kalibrasi

1. Siapkan latar bersih dan font. Jalankan perintah Artisan khusus, misalnya `php artisan certificates:make-samples` untuk menghasilkan empat PDF dari data sampel: Arief Soekardi (urut 3), Mourice (urut 9), Mu'ammal Hamidy (urut 15), dan Akrom Masani Iksanul Amri (bootcamp).
2. Ubah PDF ke gambar pada ukuran yang sama dengan sampel dan tumpuk dengan sampel asli (selisih atau transparansi 50%).
3. Ukur selisih tiap elemen dalam milimeter. Sesuaikan X, Y, dan ukuran sampai selisih di bawah 1 mm.
4. Gunakan halaman Uji Layout dengan Garis Bantu untuk membaca koordinat langsung pada PDF.
5. Uji nama pendek, panjang, apostrof, bergelar, huruf berakses, dan 70 karakter.
6. Naikkan versi layout bila ada perubahan setelah ada sertifikat yang terbit.

---

## 10. Aturan teks antarmuka

- Bahasa Indonesia sehari-hari, singkat, tanpa istilah teknis yang tidak dijelaskan.
- Tanpa emoji dan tanpa tanda seru yang dipaksakan.
- Pesan galat menyebut kejadian dan langkah berikutnya.
- Istilah tetap: **Terbitkan** (membuat sertifikat resmi), **Pratinjau** (contoh tidak resmi), **Batalkan** (menandai sertifikat tidak berlaku), **Nonaktifkan** (untuk kegiatan dan pengguna).
- Nomor selalu ditampilkan dengan huruf berlebar tetap agar mudah dibaca dan dibandingkan.

| Situasi | Teks |
|---|---|
| Berhasil terbit | Sertifikat atas nama {nama} diterbitkan dengan nomor {nomor}. Unduhan dimulai. |
| Nomor sudah dipakai | Nomor {nomor} sudah dipakai oleh {nama lain}. Gunakan nomor urut {saran}. |
| Nama kembar | {Nama} sudah punya sertifikat pada kegiatan ini dengan nomor {nomor}. Lanjutkan hanya bila ini orang yang berbeda atau memang perlu diterbitkan ulang. |
| Nama kosong | Nama peserta wajib diisi. |
| Nama berisi tanda tidak diizinkan | Nama hanya boleh berisi huruf, spasi, titik, koma, apostrof, dan tanda hubung. |
| Tanggal selesai salah | Tanggal selesai tidak boleh sebelum tanggal mulai. |
| Judul terlalu panjang | Judul terlalu panjang untuk muat di sertifikat. Persingkat pada kolom Judul pada sertifikat. |
| Render gagal | Sertifikat belum bisa dibuat dan nomor tidak terpakai. Coba lagi. Bila berulang, hubungi admin dengan kode {kode}. |
| Login salah | Username atau kata sandi tidak cocok. |
| Akun terkunci | Terlalu banyak percobaan. Coba lagi dalam {menit} menit. |
| Sesi habis | Sesi Anda berakhir. Silakan masuk lagi. |
| Dialog pembatalan | Batalkan sertifikat {nomor}? Sertifikat tidak dihapus tetapi ditandai tidak berlaku. Tulis alasan pembatalan. |

---

## 11. Daftar periksa sebelum rilis

- Semua rute di bagian 3 terdaftar dan tiap tombol di bagian 7.3 mengarah ke rute yang ada.
- Tidak ada nilai hex di berkas tampilan.
- Semua halaman lulus pada lebar 360 px tanpa gulir mendatar.
- Fokus papan ketik terlihat di semua tombol dan masukan.
- Tiga sertifikat seminar sampel dan satu sertifikat bootcamp sampel direproduksi dengan selisih posisi di bawah 1 mm.
- Nama `Mu'ammal Hamidy` tercetak benar di PDF, form, dan riwayat.
- Nama 70 karakter tidak meluap dan tidak menimpa elemen lain.
- Uji 50 permintaan paralel pada satu kegiatan menghasilkan nomor berbeda semua.
- Render yang dipaksa gagal tidak meninggalkan baris sertifikat dan nomor tetap bebas.
- Unduh ulang menghasilkan PDF identik secara visual dengan unduhan pertama, termasuk setelah judul kegiatan diubah dan versi layout dinaikkan.
- Semua POST menolak permintaan tanpa token CSRF yang valid.
- Masukan `<script>` pada nama tidak dieksekusi di halaman mana pun.
- Operator yang membuka alamat admin menerima 403, dan operator tidak dapat membuka sertifikat milik orang lain.
- Folder `config`, `src`, `storage`, dan `assets/templates` tidak dapat diakses lewat alamat web.
- `.env` tidak ada di repositori dan riwayat commit.
- PDF terbuka normal di Chrome, Adobe Reader, dan ponsel, font tertanam, teks dapat dipilih.
- Pengguna awal admin dibuat lewat skrip dengan kata sandi yang ditentukan saat instalasi, bukan tertanam di kode.
- README memuat cara instal, konfigurasi `.env`, seed kegiatan, kalibrasi layout, cara menambah kegiatan, dan cara menambah jenis sertifikat baru.

---

XCODE | Design System
