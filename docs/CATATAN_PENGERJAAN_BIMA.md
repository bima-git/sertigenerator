# Catatan Pengerjaan & Panduan Integrasi Modul Bima

**Nama / Peran:** Bima — Backend Logic, Penomoran (*Numbering Engine*) & Proses Penerbitan (*Issuance Orchestrator*)  
**Branch Git:** `feature/bima-numbering`  
**Status Tahap:** Langkah 1, Langkah 2, Langkah 3, & Langkah 4 Selesai — **100% Lulus Uji Otomatis (44 Tests, 128 Assertions)**  
**Terakhir Diperbarui:** 19 November 2026

---

## 1. Ringkasan Eksekutif

Dokumen ini mencatat seluruh detail implementasi, arsitektur kelas, skema data, endpoint API, dan hasil pengujian yang telah diselesaikan oleh Bima. Dokumen ini dirancang sebagai referensi teknis yang transparan dan dapat dibagikan kepada seluruh rekan tim (**Akmal, Ifant, dan Giska**) agar proses integrasi berjalan lancar tanpa tumpang tindih.

---

## 2. Rincian Modul yang Telah Selesai Dibangun

### A. Helper Angka Romawi (`App\Services\Roman`) — *Langkah 1*
* **Lokasi Berkas:** [`app/Services/Roman.php`](file:///D:/MAGANG_2026/sertigenerator/app/Services/Roman.php)
* **Fungsi Utama:**
  1. `Roman::fromMonth(int $month): string`: Mengonversi angka bulan (1 s.d. 12) menjadi representasi Romawi resmi XCODE (`I` sampai `XII`). Melempar `InvalidArgumentException` jika bulan di luar rentang 1–12.
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
     * Format padding 3 digit otomatis pada seri (`001`) dan nomor urut (`003`).
     * Parsing tanggal fleksibel (`Carbon`, `DateTime`, atau string `'2026-10-24'`) dengan zona waktu `Asia/Jakarta`.
     * Validasi ketat input (kode/penerbit tidak boleh kosong, seri/urut wajib bilangan bulat positif).
  2. `NumberGenerator::parse(string $nomor): array`: Memecah string nomor resmi menjadi array segmen terstruktur.
  3. `NumberGenerator::isValid(string $nomor): bool`: Validasi regex format nomor.

---

### C. Skema Basis Data & Model Eloquent — *Langkah 2*
Sesuai **PRD Bagian 7.1**, dibuat tabel dan model data pendukung penomoran:
1. **Tabel & Model `Event`:**
   * Berkas: [`database/migrations/2026_11_19_000001_create_events_table.php`](file:///D:/MAGANG_2026/sertigenerator/database/migrations/2026_11_19_000001_create_events_table.php) & [`app/Models/Event.php`](file:///D:/MAGANG_2026/sertigenerator/app/Models/Event.php)
   * Menyimpan master kegiatan: `kode`, `judul`, `jenis`, `penerbit`, `bernomor`, `pakai_qr`, `program_default`, `aktif`.
2. **Tabel & Model `Certificate`:**
   * Berkas: [`database/migrations/2026_11_19_000002_create_certificates_table.php`](file:///D:/MAGANG_2026/sertigenerator/database/migrations/2026_11_19_000002_create_certificates_table.php) & [`app/Models/Certificate.php`](file:///D:/MAGANG_2026/sertigenerator/app/Models/Certificate.php)
   * Dilengkapi constraint integritas ganda:
     * `UNIQUE KEY (nomor_lengkap)`
     * `UNIQUE KEY uq_event_seri_urut_tahun (event_id, seri, urut, tahun)`
3. **Tabel & Model `AuditLog`:**
   * Berkas: [`database/migrations/2026_11_19_000003_create_audit_logs_table.php`](file:///D:/MAGANG_2026/sertigenerator/database/migrations/2026_11_19_000003_create_audit_logs_table.php) & [`app/Models/AuditLog.php`](file:///D:/MAGANG_2026/sertigenerator/app/Models/AuditLog.php)
   * Mencatat seluruh aksi penting: pelaku (`user_id`), jenis aksi (`aksi`), `target_id`, `detail` JSON, IP, dan timestamp.

---

### D. Service Pengalokasi Nomor Urut (`App\Services\NumberAllocator`) — *Langkah 2*
* **Lokasi Berkas:** [`app/Services/NumberAllocator.php`](file:///D:/MAGANG_2026/sertigenerator/app/Services/NumberAllocator.php)
* **Aturan Bisnis Sesuai PRD NM-03 & NM-04:**
  1. `next(int $eventId, int $seri, int $tahun): int`
     * Menghitung nilai maksimum urut pada kombinasi `(event_id, seri, tahun)` ditambah 1:
       $$\text{COALESCE}(\max(\text{urut}), 0) + 1$$
     * **Sertifikat yang berstatus `dibatalkan` tetap ikut dihitung**, sehingga nomor yang sudah pernah dibatalkan tidak akan pernah dialokasikan ulang untuk peserta baru.
  2. `isAvailable(int $eventId, int $seri, int $urut, int $tahun): bool`
     * Memeriksa apakah kombinasi nomor tertentu masih bebas sebelum disimpan.
  3. `isNumberTaken(string $nomorLengkap): bool`
     * Pengecekan cepat ketersediaan nomor lengkap di database.
  4. `suggest(Event|int $event, int $seri = 1, DateTimeInterface|string|null $tanggalTerbit = null, ?int $manualUrut = null): array`
     * Helper terpadu yang menghasilkan payload saran nomor lengkap, status ketersediaan, serta penanganan otomatis untuk kegiatan tanpa nomor (bootcamp).

---

### E. Controller & Rute API Form Penerbitan (`IssueController`) — *Langkah 3*
* **Lokasi Berkas:** [`app/Http/Controllers/IssueController.php`](file:///D:/MAGANG_2026/sertigenerator/app/Http/Controllers/IssueController.php) & [`routes/web.php`](file:///D:/MAGANG_2026/sertigenerator/routes/web.php)
* **Sesuai Design System Bagian 3.3:**
  1. **Endpoint `GET /api/nomor` (Nama Rute: `api.number`):**
     * Menghasilkan saran nomor urut dan *live preview* nomor lengkap untuk form Giska.
  2. **Endpoint `GET /api/kegiatan` (Nama Rute: `api.events`):**
     * Mengembalikan daftar kegiatan aktif untuk mengisi komponen dropdown searchable-select form.

---

### F. Orkestrasi Penerbitan Atomik (`IssueService`) — *Langkah 4*
* **Lokasi Berkas:**
  * Service: [`app/Services/IssueService.php`](file:///D:/MAGANG_2026/sertigenerator/app/Services/IssueService.php)
  * Form Request: [`app/Http/Requests/IssueRequest.php`](file:///D:/MAGANG_2026/sertigenerator/app/Http/Requests/IssueRequest.php)
  * Renderer PDF: [`app/Services/PdfRenderer.php`](file:///D:/MAGANG_2026/sertigenerator/app/Services/PdfRenderer.php)
  * Audit Logger: [`app/Services/AuditLogger.php`](file:///D:/MAGANG_2026/sertigenerator/app/Services/AuditLogger.php)
* **Aturan Bisnis & Logika Kritis (PRD 4.3, 4.4, 4.5 & Business Flow 3.10):**
  1. **`IssueService::issue(array $data, User|int $user): array`:**
     * Menjalankan seluruh proses di dalam transaksi database `DB::transaction()`.
     * Mengunci baris kegiatan dengan `lockForUpdate()` untuk mencegah tabrakan alokasi nomor ganda pada request bersamaan.
     * Mengalokasikan nomor jika kegiatan bernomor dan memvalidasi keunikan nomor urut.
     * Membangun salinan data cetak JSON (`data_cetak`) yang disimpan permanen di tabel sertifikat.
     * **Kaidah Atomik:** Render PDF dilakukan di dalam transaksi **sebelum commit**. Jika terjadi kegagalan render (misal out-of-memory TCPDF), transaksi otomatis di-*rollback*, data sertifikat tidak tersimpan, dan nomor urut tetap bebas.
     * Mencatat jejak ke tabel `audit_logs`.
     * Menghasilkan nama berkas aman sesuai PRD PD-10: `Sertifikat_{Jenis}_{NamaSafe}.pdf` (misal: `Mu'ammal Hamidy` $\rightarrow$ `Muammal_Hamidy`).
  2. **`IssueService::preview(array $data): array`:**
     * Menghasilkan contoh PDF ber-watermark `PRATINJAU` secara inline.
     * Tidak menjalankan transaksi, tidak memotong kuota nomor urut, dan tidak menyimpan data ke database.
  3. **Aksi Rute pada Controller (`IssueController`):**
     * `POST /terbitkan` (Nama Rute: `issue.store`): Mengembalikan response download biner PDF langsung (`Content-Disposition: attachment`).
     * `POST /pratinjau` (Nama Rute: `issue.preview`): Mengembalikan response tampilan PDF inline di tab baru (`Content-Disposition: inline`).

---

## 3. Hasil Pengujian Otomatis (*Automated Tests*)

Pengujian dijalankan melalui `php artisan test`. Seluruh **44 pengujian berstatus 100% HIJAU (PASS) dengan 128 assertions**.

### A. Pengujian Unit Test (`tests/Unit/Services/`): 20 Tests, 47 Assertions
* `NumberGeneratorTest` (14 tests) & `RomanTest` (5 tests): Lulus 100% kasus uji PRD 5.3.

### B. Pengujian Feature Alokasi Database (`tests/Feature/Services/NumberAllocatorTest.php`): 11 Tests, 25 Assertions
* Lulus uji alokasi sekuensial, isolasi nomor per seri/tahun/kegiatan, dan nomor batal tidak dipakai ulang.

### C. Pengujian Feature API Form (`tests/Feature/Api/NumberSuggestTest.php`): 6 Tests, 24 Assertions
* Lulus uji API nomor saran, deteksi nomor terpakai, dan filtering daftar kegiatan aktif.

### D. Pengujian Feature Penerbitan Atomik (`tests/Feature/IssueCertificateTest.php`): 6 Tests, 31 Assertions
1. `test_issue_seminar_certificate_success`: Penerbitan seminar sukses, nomor lengkap valid, download header sesuai, cert & audit log tersimpan.
2. `test_issue_bootcamp_certificate_success`: Penerbitan bootcamp sukses, nomor `null`, data perusahaan & tanggal magang tersimpan di `data_cetak`.
3. `test_preview_certificate_does_not_save_to_database`: Pratinjau memuat watermark `PRATINJAU` dan tidak menambah baris di database.
4. `test_atomic_rollback_when_pdf_rendering_fails`: Simulasi render PDF gagal $\rightarrow$ transaksi di-rollback secara atomik (0 baris di `certificates`, nomor tetap bebas).
5. `test_duplicate_manual_urut_is_rejected`: Penolakan nomor urut ganda jika diisi manual oleh operator.
6. `test_validation_rejects_bootcamp_invalid_dates`: Validasi HTTP 422 jika tanggal selesai sebelum tanggal mulai.

---

## 4. Panduan Titik Integrasi untuk Rekan Tim

| Rekan Tim | Titik Kolaborasi dengan Bima | Kontrak / Panduan Teknis |
|---|---|---|
| **Giska** (Frontend) | Form Penerbitan Dinamis | • Panggil `GET /api/nomor` untuk saran nomor & preview string.<br>• Form `POST /terbitkan` mengirim data ke `issue.store` untuk langsung mengunduh PDF.<br>• Tombol Pratinjau mengirim data ke `POST /pratinjau` (`issue.preview`) untuk membuka PDF di tab baru. |
| **Ifant** (PDF Generator) | Engine Rendering PDF | Kelas `App\Services\PdfRenderer` siap menerima integrasi layout TCPDF resmi dari Ifant. Method `render(Certificate $cert, string $mode)` menerima model sertifikat dan mode (`terbit`, `pratinjau`, `dibatalkan`). |
| **Akmal** (Database & Admin) | Data Transaksi & Audit Log | Seluruh penerbitan tercatat di tabel `certificates` dan `audit_logs` dengan format data cetak JSON yang lengkap. |

---

## 5. Rencana Langkah Berikutnya (Langkah 5)

Tahap berikutnya yang akan dikerjakan oleh Bima:
* **Langkah 5: Logika Unduh Ulang & Pembatalan Sertifikat (Fase 7 - Kontribusi Bima)**
  * Membangun method unduh ulang (`HistoryController@download` / `HistoryService`):
    * Membaca salinan `data_cetak` JSON dari baris sertifikat tersimpan.
    * Me-render ulang PDF menggunakan versi layout yang tercatat pada saat terbit (identik visual 100%).
    * Jika sertifikat berstatus `dibatalkan`, PDF otomatis memunculkan tanda air `DIBATALKAN`.
  * Membangun aksi pembatalan (`HistoryController@cancel`):
    * Rute: `POST /riwayat/{id}/batalkan` (Khusus Admin).
    * Validasi alasan pembatalan (wajib diisi).
    * Menandai status menjadi `dibatalkan`, mencatat ID admin pembatal, dan waktu pembatalan.
    * Menjamin nomor batal tidak bisa diaktifkan kembali.
  * Menulis Feature Test untuk unduh ulang dan pembatalan.
