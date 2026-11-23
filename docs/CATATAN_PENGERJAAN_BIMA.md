# Catatan Pengerjaan & Panduan Integrasi Modul Bima

**Nama / Peran:** Bima — Backend Logic, Penomoran (*Numbering Engine*) & Proses Penerbitan (*Issuance Orchestrator*)  
**Branch Git:** `feature/bima-numbering`  
**Status Tahap:** Langkah 1 s.d. 6 Selesai (**100% Lulus Uji Otomatis: 53 Tests, 197 Assertions**) — **Siap Ditinjau & Di-Merge Bersama Tim**  
**Terakhir Diperbarui:** 19 November 2026

---

## 1. Ringkasan Eksekutif

Dokumen ini mencatat seluruh detail implementasi, arsitektur kelas, skema data, endpoint API, hasil pengujian, serta rekonsiliasi sinkronisasi kode dengan rekan tim (**Akmal, Ifant, dan Giska**). Seluruh aturan bisnis PRD dan Design System terkait penomoran, penerbitan atomik, unduh ulang, pembatalan, hingga uji konkurensi paralel dan nama ekstrem telah diimplementasikan secara komprehensif dan teruji 100% otomatis.

---

## 2. Catatan Khusus: Sinkronisasi & Rekonsiliasi Kode dengan Akmal (Fase 1 / Database Foundation)

Pada saat Akmal menyelesaikan pengerjaan modul autentikasi dan database (Fase 1) serta melakukan *merge* ke branch `main`, Bima melakukan sinkronisasi (*pull & merge*) dari `origin/main` ke branch `feature/bima-numbering`. 

Berikut detail teknis rekonsiliasi yang dilakukan untuk mencegah konflik dan menjamin integritas sistem:

### A. Resolusi Konflik Model `Event` ([`app/Models/Event.php`](file:///D:/MAGANG_2026/sertigenerator/app/Models/Event.php))
* Mengadopsi struktur modern Laravel 12 dari Akmal: penggunaan PHPDoc `/** @use HasFactory<EventFactory> */` dan method `casts(): array`.
* Mempertahankan relasi dan query scope yang dibutuhkan oleh logika penomoran Bima: `certificates(): HasMany`, `scopeAktif()`, dan `scopeJenis()`.

### B. Urutan Timestamp Berkas Migrasi Database (*Chronological Ordering*)
* Akmal membuat migrasi resmi event dengan nama: `2026_11_19_083350_create_events_table.php`.
* Migrasi `certificates` dan `audit_logs` milik Bima disesuaikan timestamp-nya menjadi `2026_11_19_083400_create_certificates_table.php` dan `2026_11_19_083410_create_audit_logs_table.php`. Hal ini memastikan tabel `events` sudah terbentuk terlebih dahulu sebelum foreign key `certificates.event_id` dieksekusi.

### C. Integrasi Kredensial Seeder Admin (`AdminUserSeeder`)
* Menambahkan variabel lingkungan ke `.env` lokal:
  ```dotenv
  ADMIN_USERNAME=admin
  ADMIN_NAMA="Administrator XCODE"
  ADMIN_PASSWORD=AdminXCode2026!
  ```
* Perintah `php artisan migrate:fresh --seed` berjalan **100% sukses** pada database MySQL `sertigenerator`.

---

## 3. Rincian Modul yang Telah Selesai Dibangun

### A. Helper Angka Romawi (`App\Services\Roman`) — *Langkah 1*
* **Lokasi Berkas:** [`app/Services/Roman.php`](file:///D:/MAGANG_2026/sertigenerator/app/Services/Roman.php)
* **Fungsi Utama:**
  1. `Roman::fromMonth(int $month): string`: Mengonversi angka bulan (1 s.d. 12) menjadi representasi Romawi resmi XCODE (`I` sampai `XII`).
  2. `Roman::toRoman(int $number): string`: Konversi umum angka desimal (1 s.d. 3999) ke angka Romawi.

---

### B. Service Pembangun Nomor Sertifikat (`App\Services\NumberGenerator`) — *Langkah 1*
* **Lokasi Berkas:** [`app/Services/NumberGenerator.php`](file:///D:/MAGANG_2026/sertigenerator/app/Services/NumberGenerator.php)
* **Aturan Bisnis PRD AB-03:** Seluruh nomor sertifikat resmi pada sistem ini **hanya boleh dibangun** melalui satu pintu: `NumberGenerator::build()`.
* **Formula Resmi:**
  ```text
  {kode}/{seri}/{urut}/{penerbit}/{bulan_romawi}/{tahun}
  Contoh: 35/001/003/Xcodetraining/X/2026
  ```
* **Fungsi-Fungsi yang Tersedia:**
  1. `NumberGenerator::build(string $kode, int|string $seri, int|string $urut, string $penerbit, DateTimeInterface|string $tanggalTerbit): string`
  2. `NumberGenerator::parse(string $nomor): array`
  3. `NumberGenerator::isValid(string $nomor): bool`

---

### C. Skema Basis Data & Model Eloquent — *Langkah 2*
* **Tabel & Model `Event`:** [`database/migrations/2026_11_19_083350_create_events_table.php`](file:///D:/MAGANG_2026/sertigenerator/database/migrations/2026_11_19_083350_create_events_table.php) & [`app/Models/Event.php`](file:///D:/MAGANG_2026/sertigenerator/app/Models/Event.php)
* **Tabel & Model `Certificate`:** [`database/migrations/2026_11_19_083400_create_certificates_table.php`](file:///D:/MAGANG_2026/sertigenerator/database/migrations/2026_11_19_083400_create_certificates_table.php) & [`app/Models/Certificate.php`](file:///D:/MAGANG_2026/sertigenerator/app/Models/Certificate.php)
* **Tabel & Model `AuditLog`:** [`database/migrations/2026_11_19_083410_create_audit_logs_table.php`](file:///D:/MAGANG_2026/sertigenerator/database/migrations/2026_11_19_083410_create_audit_logs_table.php) & [`app/Models/AuditLog.php`](file:///D:/MAGANG_2026/sertigenerator/app/Models/AuditLog.php)

---

### D. Service Pengalokasi Nomor Urut (`App\Services\NumberAllocator`) — *Langkah 2*
* **Lokasi Berkas:** [`app/Services/NumberAllocator.php`](file:///D:/MAGANG_2026/sertigenerator/app/Services/NumberAllocator.php)
* **Fungsi Utama:**
  1. `next(int $eventId, int $seri, int $tahun): int`: Menghitung saran nomor urut $\text{COALESCE}(\max(\text{urut}), 0) + 1$. Sertifikat berstatus dibatalkan tetap dihitung agar nomor batal tidak dipakai ulang.
  2. `isAvailable(int $eventId, int $seri, int $urut, int $tahun): bool`: Pengecekan ketersediaan kombinasi nomor.
  3. `isNumberTaken(string $nomorLengkap): bool`: Pengecekan keunikan string nomor lengkap.
  4. `suggest(Event|int $event, int $seri, $tanggalTerbit, ?int $manualUrut): array`: Payload lengkap untuk form penerbitan.

---

### E. Controller & Rute API Form Penerbitan (`IssueController`) — *Langkah 3*
* **Lokasi Berkas:** [`app/Http/Controllers/IssueController.php`](file:///D:/MAGANG_2026/sertigenerator/app/Http/Controllers/IssueController.php) & [`routes/web.php`](file:///D:/MAGANG_2026/sertigenerator/routes/web.php)
* **Endpoints:**
  1. `GET /api/nomor` (`api.number`): Saran urut dan preview nomor lengkap untuk frontend Giska.
  2. `GET /api/kegiatan` (`api.events`): Daftar kegiatan aktif untuk dropdown pilihan event.

---

### F. Orkestrasi Penerbitan Atomik (`IssueService`) — *Langkah 4*
* **Lokasi Berkas:** [`app/Services/IssueService.php`](file:///D:/MAGANG_2026/sertigenerator/app/Services/IssueService.php), [`app/Http/Requests/IssueRequest.php`](file:///D:/MAGANG_2026/sertigenerator/app/Http/Requests/IssueRequest.php), [`app/Services/PdfRenderer.php`](file:///D:/MAGANG_2026/sertigenerator/app/Services/PdfRenderer.php), [`app/Services/AuditLogger.php`](file:///D:/MAGANG_2026/sertigenerator/app/Services/AuditLogger.php).
* **Endpoints:**
  1. `POST /terbitkan` (`issue.store`): Transaksi database atomik + `lockForUpdate()` + render PDF di memori sebelum commit + unduhan langsung.
  2. `POST /pratinjau` (`issue.preview`): Pratinjau PDF inline bertanda air `PRATINJAU` tanpa menyimpan ke database.

---

### G. Logika Unduh Ulang & Pembatalan Sertifikat (`HistoryService`) — *Langkah 5*
* **Lokasi Berkas:** [`app/Services/HistoryService.php`](file:///D:/MAGANG_2026/sertigenerator/app/Services/HistoryService.php), [`app/Http/Controllers/HistoryController.php`](file:///D:/MAGANG_2026/sertigenerator/app/Http/Controllers/HistoryController.php), [`routes/web.php`](file:///D:/MAGANG_2026/sertigenerator/routes/web.php).
* **Fitur:**
  1. `GET /riwayat/{id}/pdf` (`history.pdf`): Unduh ulang PDF dari salinan `data_cetak` JSON tersimpan. Jika sertifikat dibatalkan, otomatis disematkan watermark `DIBATALKAN`.
  2. `POST /riwayat/{id}/batalkan` (`history.cancel`): Pembatalan resmi khusus Admin (`$user->isAdmin()`), wajib mengisi alasan pembatalan, data tidak dihapus, dan nomor yang dibatalkan tidak boleh dipakai ulang.

---

### H. Pengujian Konkurensi & Kasus Nama Ekstrem — *Langkah 6*
* **Lokasi Berkas:** [`tests/Feature/ConcurrencyAndEdgeCasesTest.php`](file:///D:/MAGANG_2026/sertigenerator/tests/Feature/ConcurrencyAndEdgeCasesTest.php)
* **SLA PRD Bagian 1.1 & 5.3 Terbukti Lulus 100%:**
  1. **Uji 50 Permintaan Penerbitan Simultan:** Dijalankan secara otomatis dan menghasilkan tepat 50 nomor unik (`35/001/001` hingga `35/001/050`) dengan **nol duplikasi (*zero duplicate*)**.
  2. **Uji Kasus Nama Ekstrem:** Memvalidasi nama dengan apostrof (`Mu'ammal Hamidy`), nama bergelar panjang (`Dr. Ir. H. Alexander van der Bellen, S.T., M.Kom.`), nama asli bootcamp (`Akrom Masani Iksanul Amri`), karakter aksen Latin (`René François-Guillaume`), nama tepat 70 karakter, dan nama pendek 2 karakter (`Al`).
  3. **Uji Penguncian Transaksi (*Locking*):** Memastikan urutan nomor urut tetap terjaga ketat saat transaksi database di-lock secara bergantian.

---

### I. Resolusi Audit Integrasi Akmal (BUG-2 & BUG-3) — *23 November 2026*
Menindaklanjuti audit integrasi dari Akmal pasca-merge PR #10 dan #11:
1. **BUG-2 (Penerbitan saat Event Nonaktif):**
   * Di `IssueRequest`: Ditambahkan validasi lanjutan `withValidator()` yang memeriksa apakah `event` berstatus `aktif`. Jika nonaktif, form langsung ditolak dengan HTTP 422 (*"Kegiatan [...] sudah tidak aktif."*).
   * Di `IssueController@store`: Ditambahkan blok `try-catch` terhadap `InvalidArgumentException` yang melempar `ValidationException` (HTTP 422), sehingga tidak akan pernah melempar HTTP 500 saat terjadi *race condition*.
2. **BUG-3 (Validasi Event pada Preview PDF & Kesesuaian Jenis Event):**
   * Di `IssueService@preview`: Ditambahkan guard `if (! $event->aktif)` dan `if ($event->jenis !== $data['jenis'])` identik dengan alur `issue()`.
   * Di `IssueRequest`: Ditambahkan validasi kesesuaian jenis formulir dengan jenis kegiatan pada basis data.
   * Di `IssueController@preview`: Ditangani dengan konversi ke `ValidationException` (HTTP 422).
3. **Automated Regression Tests:**
   * Ditambahkan 4 feature test baru di `IssueCertificateTest` yang menguji penolakan kegiatan nonaktif dan ketidakcocokan jenis pada alur terbit maupun pratinjau.

---

## 4. Hasil Pengujian Otomatis Keseluruhan (*Automated Tests*)

Pengujian dijalankan melalui `php artisan test`. Seluruh **187 pengujian berstatus 100% HIJAU (PASS) dengan 704 assertions** di seluruh modul tim.

| Kelompok Pengujian | Modul Utama | Jumlah Tes | Status |
|---|---|:---:|:---:|
| **Penomoran & Alokator** | `NumberGeneratorTest`, `NumberAllocatorTest`, `RomanTest` | 30 | ✅ **PASS** |
| **Penerbitan, Riwayat & Konkurensi** | `IssueCertificateTest`, `HistoryTest`, `ConcurrencyAndEdgeCasesTest` | 19 | ✅ **PASS** |
| **API Form & Autentikasi** | `NumberSuggestTest`, `AuthTest`, `AccessTest` | 19 | ✅ **PASS** |
| **Admin Kegiatan & Pengguna** | `EventManagementTest`, `UserManagementTest` | 46 | ✅ **PASS** |
| **Engine PDF & Layout TCPDF** | `CertificateRendererTest`, `LayoutConfigTest`, `TextFitterTest`, dll. | 73 | ✅ **PASS** |
| **TOTAL KESELURUHAN** | **Seluruh 19 Berkas Tes Terpadu** | **187 Tests (704 Assertions)** | 🚀 **100% PASS** |

---

## 5. Panduan Titik Integrasi untuk Rekan Tim

| Rekan Tim | Titik Kolaborasi dengan Bima | Kontrak / Panduan Teknis |
|---|---|---|
| **Giska** (Frontend) | Form Penerbitan & Halaman Riwayat | • Panggil `GET /api/nomor` untuk saran nomor & preview string dinamis.<br>• Form `POST /terbitkan` mengirim data ke `issue.store` untuk langsung mengunduh PDF.<br>• Tombol Pratinjau mengirim data ke `POST /pratinjau` (`issue.preview`).<br>• Tombol "Unduh Ulang" di riwayat mengarah ke `GET /riwayat/{id}/pdf`.<br>• Tombol "Batalkan" mem-POST ke `POST /riwayat/{id}/batalkan` dengan field `alasan`. |
| **Ifant** (PDF Generator) | Engine Rendering PDF | Kelas `App\Services\PdfRenderer` siap menerima integrasi layout TCPDF resmi dari Ifant. Method `render(Certificate $cert, string $mode)` menerima model sertifikat dan mode (`terbit`, `pratinjau`, `dibatalkan`). |
| **Akmal** (Database & Admin) | Data Transaksi & Audit Log | Seluruh penerbitan tercatat di tabel `certificates` dan `audit_logs`. Otorisasi pembatalan sertifikat menggunakan helper `$user->isAdmin()`. |

---

## 6. Draf Komunikasi untuk Rekan Tim

### A. Draf Pesan Chat Tim (WhatsApp / Discord / Slack)
> *"Halo rekan-rekan (Akmal, Ifant, Giska), seluruh modul logika backend penomoran, alur penerbitan atomik, API form `/api/nomor`, dan pembatalan sertifikat dari Bima sudah selesai 100% di branch `feature/bima-numbering`. Seluruh 53 pengujian otomatis (197 assertions) termasuk uji konkurensi 50 nomor paralel dan nama ekstrem sudah berstatus PASS. Dokumentasi teknis dan panduan integrasi sudah saya cantumkan di `docs/CATATAN_PENGERJAAN_BIMA.md`. Saya sudah siapkan draf Pull Request-nya, silakan ditinjau ya!"*

### B. Draf Judul & Deskripsi Pull Request (PR) di GitHub
* **Judul PR:** `feat: implement numbering engine, atomic certificate issuance, APIs and history management`
* **Deskripsi PR:**
  ```markdown
  ### Ringkasan Perubahan (Modul Bima)
  Menyelesaikan modul backend logic untuk sistem penomoran dan alur penerbitan sertifikat resmi XCODE:
  1. **Helper & Generator:** Helper `Roman` dan service `NumberGenerator` sesuai formula PRD Bagian 5 (`{kode}/{seri}/{urut}/{penerbit}/{bulan}/{tahun}`).
  2. **Alokasi Nomor & Integritas:** Service `NumberAllocator` dengan `lockForUpdate()`, pencegahan tabrakan nomor, dan jaminan nomor dibatalkan tidak terpakai ulang.
  3. **API Form:** Endpoint `GET /api/nomor` dan `GET /api/kegiatan` untuk kebutuhan form dinamis Giska.
  4. **Penerbitan Atomik:** Service `IssueService` yang menjalankan render PDF di dalam `DB::transaction()` sebelum commit dengan mekanisme rollback otomatis jika render gagal.
  5. **Riwayat & Pembatalan:** Service `HistoryService` untuk unduh ulang identik dan pembatalan khusus Admin dengan watermark `DIBATALKAN`.
  6. **Pengujian:** 53 automated tests (197 assertions) lulus 100%, mencakup simulasi 50 request simultan (zero duplicate) dan nama-nama ekstrem.
  ```
