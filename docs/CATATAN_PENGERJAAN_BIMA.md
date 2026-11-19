# Catatan Pengerjaan & Panduan Integrasi Modul Bima

**Nama / Peran:** Bima — Backend Logic, Penomoran (*Numbering Engine*) & Proses Penerbitan (*Issuance Orchestrator*)  
**Branch Git:** `feature/bima-numbering`  
**Status Tahap:** Langkah 1 s.d. 5 Selesai — **100% Lulus Uji Otomatis (50 Tests, 152 Assertions)**  
**Terakhir Diperbarui:** 19 November 2026

---

## 1. Ringkasan Eksekutif

Dokumen ini mencatat seluruh detail implementasi, arsitektur kelas, skema data, endpoint API, hasil pengujian, serta rekonsiliasi sinkronisasi kode dengan rekan tim (**Akmal, Ifant, dan Giska**). Seluruh aturan bisnis PRD dan Design System terkait penomoran, penerbitan atomik, unduh ulang, dan pembatalan telah diimplementasikan secara komprehensif dan teruji otomatis.

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
* **Lokasi Berkas:**
  * Service: [`app/Services/HistoryService.php`](file:///D:/MAGANG_2026/sertigenerator/app/Services/HistoryService.php)
  * Controller: [`app/Http/Controllers/HistoryController.php`](file:///D:/MAGANG_2026/sertigenerator/app/Http/Controllers/HistoryController.php)
  * Rute: [`routes/web.php`](file:///D:/MAGANG_2026/sertigenerator/routes/web.php)
* **Aturan Bisnis & Logika Kritis (PRD 4.6 & Business Flow 3.5, 3.6, AB-11 s.d. AB-15):**
  1. **Unduh Ulang (`HistoryService::download` / `GET /riwayat/{id}/pdf`):**
     * Mengambil salinan data cetak JSON (`data_cetak`) dan `versi_layout` dari baris sertifikat yang tersimpan. Menjamin hasil unduh ulang **100% identik secara visual** dengan unduhan pertama walaupun judul master kegiatan telah diubah kemudian.
     * Jika sertifikat masih berstatus `terbit`, PDF dirender bersih tanpa tanda air.
     * Jika sertifikat berstatus `dibatalkan`, PDF otomatis dirender dengan tanda air diagonal **`DIBATALKAN`**.
     * Setiap kali unduh ulang dilakukan, sistem mencatat log audit dengan aksi `'unduh_ulang'`.
  2. **Pembatalan Sertifikat (`HistoryService::cancel` / `POST /riwayat/{id}/batalkan`):**
     * **Otorisasi Khusus Admin:** Hanya pengguna dengan peran `admin` (`$user->isAdmin()`) yang berhak membatalkan. Operator non-admin ditolak dengan kode `403 Forbidden`.
     * **Alasan Pembatalan Wajib:** Operator/Admin wajib mengisi teks alasan pembatalan (3–255 karakter).
     * **Immutability Data:** Baris sertifikat **tidak dihapus** dari database, melainkan diubah statusnya menjadi `'dibatalkan'`, serta mencatat `dibatalkan_oleh` (ID admin) dan `dibatalkan_pada` (waktu sekarang).
     * **Pencegahan Pemakaian Ulang Nomor (AB-13):** Nomor urut yang telah dibatalkan dijamin **tidak akan pernah dipakai ulang** saat menerbitkan sertifikat baru pada kegiatan dan seri yang sama.
     * Mencatat jejak ke tabel `audit_logs` dengan aksi `'batal'`.

---

## 4. Hasil Pengujian Otomatis (*Automated Tests*)

Pengujian dijalankan melalui `php artisan test`. Seluruh **50 pengujian berstatus 100% HIJAU (PASS) dengan 152 assertions**.

### A. Pengujian Unit Test (`tests/Unit/Services/`): 20 Tests, 47 Assertions
* `NumberGeneratorTest` & `RomanTest`: 100% Lulus kasus uji PRD 5.3.

### B. Pengujian Feature Alokasi Database (`tests/Feature/Services/NumberAllocatorTest.php`): 11 Tests, 25 Assertions
* Lulus uji alokasi nomor berurutan, isolasi per seri/tahun, dan integritas database.

### C. Pengujian Feature API Form (`tests/Feature/Api/NumberSuggestTest.php`): 6 Tests, 24 Assertions
* Lulus uji respons saran nomor, ketersediaan nomor manual, dan penyaringan kegiatan.

### D. Pengujian Feature Penerbitan Atomik (`tests/Feature/IssueCertificateTest.php`): 6 Tests, 31 Assertions
* Lulus uji penerbitan seminar, bootcamp, pratinjau tanpa simpan, dan rollback atomik saat render gagal.

### E. Pengujian Feature Unduh Ulang & Pembatalan (`tests/Feature/HistoryTest.php`): 6 Tests, 24 Assertions
1. `test_download_active_certificate_success`: Unduh ulang sertifikat sah menghasilkan PDF identik tanpa watermark batal dan mencatat log audit `unduh_ulang`.
2. `test_admin_can_cancel_certificate_with_reason`: Admin berhasil membatalkan sertifikat dengan alasan, data tersimpan, dan log audit `batal` tercatat.
3. `test_download_cancelled_certificate_contains_cancelled_watermark`: Sertifikat dibatalkan yang diunduh ulang otomatis memuat watermark `DIBATALKAN`.
4. `test_cancelled_certificate_number_is_never_reused`: Nomor sertifikat yang dibatalkan terbukti tidak pernah dipakai ulang (`next` otomatis meloncat ke nomor berikutnya).
5. `test_non_admin_cannot_cancel_certificate`: Operator biasa ditolak dengan HTTP 403 saat mencoba membatalkan.
6. `test_cancel_requires_reason`: Validasi HTTP 422 jika alasan pembatalan kosong.

---

## 5. Panduan Titik Integrasi untuk Rekan Tim

| Rekan Tim | Titik Kolaborasi dengan Bima | Kontrak / Panduan Teknis |
|---|---|---|
| **Giska** (Frontend) | Halaman Riwayat & Detail | • Tombol "Unduh Ulang" pada baris tabel riwayat mengarah ke tautan `GET /riwayat/{id}/pdf`.<br>• Tombol "Batalkan" (hanya tampil untuk Admin pada sertifikat status Terbit) membuka modal input alasan dan mem-POST ke `POST /riwayat/{id}/batalkan` dengan field `alasan`. |
| **Ifant** (PDF Generator) | Watermark Mode | Saat Ifant menyempurnakan `PdfRenderer`, pastikan parameter `$mode` dihormati: `'terbit'` (tanpa tanda air), `'pratinjau'` (watermark PRATINJAU), dan `'dibatalkan'` (watermark DIBATALKAN). |
| **Akmal** (Database & Admin) | Hak Akses Admin & Audit | Pembatalan hanya dapat dilakukan oleh user dengan `$user->isAdmin() == true`. Seluruh aksi pembatalan tercatat di tabel `audit_logs` untuk keperluan rekap audit admin. |

---

## 6. Rencana Langkah Berikutnya (Langkah 6)

Tahap berikutnya yang akan dikerjakan oleh Bima:
* **Langkah 6: Pengujian Beban Konkurensi & Integrasi Akhir (Fase 8)**
  * Uji konkurensi paralel (simulasi 50 permintaan penerbitan bersamaan pada satu event untuk membuktikan *zero duplicate*).
  * Uji nama ekstrem (panjang 70 karakter, karakter apostrof, gelar, aksen Latin).
  * Pembersihan kode, verifikasi akhir seluruh kriteria terima PRD, dan persiapan *Pull Request* resmi ke repositori tim.
