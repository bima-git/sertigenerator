# Catatan Pengerjaan & Panduan Integrasi Modul Bima

**Nama / Peran:** Bima — Backend Logic, Penomoran (*Numbering Engine*) & Proses Penerbitan (*Issuance Orchestrator*)  
**Branch Git:** `feature/bima-numbering`  
**Status Tahap:** Langkah 1, Langkah 2, & Langkah 3 (Fase 2A, 2B, & 6A) Selesai — **100% Lulus Uji Otomatis (38 Tests, 97 Assertions)**  
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
     * **Parameter Query:** `event_id` (wajib), `seri` (opsional, default 1), `tanggal_terbit` (opsional, default hari ini), `urut` (opsional jika diisi manual oleh operator).
     * **Contoh Output JSON (Seminar):**
       ```json
       {
         "bernomor": true,
         "urut_saran": 2,
         "urut_aktif": 2,
         "nomor_lengkap": "35/001/002/Xcodetraining/X/2026",
         "kode": "35",
         "penerbit": "Xcodetraining",
         "seri": 1,
         "tahun": 2026,
         "is_available": true
       }
       ```
     * **Contoh Output JSON (Bootcamp):**
       ```json
       {
         "bernomor": false,
         "urut_saran": null,
         "urut_aktif": null,
         "nomor_lengkap": null,
         "kode": "15",
         "penerbit": "Bootcamp",
         "seri": null,
         "tahun": 2026,
         "is_available": true
       }
       ```
  2. **Endpoint `GET /api/kegiatan` (Nama Rute: `api.events`):**
     * **Parameter Query:** `jenis` (`seminar` atau `bootcamp`), `q` (kata kunci pencarian kode atau judul).
     * Mengembalikan daftar kegiatan aktif untuk mengisi pilihan dropdown searchable-select form Giska.

---

## 3. Hasil Pengujian Otomatis (*Automated Tests*)

Pengujian dijalankan melalui `php artisan test`. Seluruh 38 pengujian berstatus **100% HIJAU (PASS)**.

### A. Pengujian Unit Test (`tests/Unit/Services/`): 20 Tests, 47 Assertions
* **`NumberGeneratorTest` (14 pengujian):** Lulus 100% seluruh kasus uji wajib PRD 5.3 (Kasus 1 s.d. 7, kode `35`, `15`, `23`, `255`, batas bulan Januari/Desember, parsing, dan proteksi input tidak valid).
* **`RomanTest` (5 pengujian):** Lulus konversi bulan 1–12, konversi umum hingga 3999, serta penanganan exception angka di luar batas.

### B. Pengujian Feature Alokasi Database (`tests/Feature/Services/NumberAllocatorTest.php`): 11 Tests, 25 Assertions
* Lulus uji alokasi sekuensial, isolasi per seri/tahun/event, aturan sertifikat dibatalkan tidak terpakai ulang, dan penolakan duplikasi via *database unique constraint*.

### C. Pengujian Feature API Form Penerbitan (`tests/Feature/Api/NumberSuggestTest.php`): 6 Tests, 24 Assertions
1. `test_api_nomor_returns_suggestion_for_seminar`: API mengembalikan nomor usulan dan nomor lengkap yang valid.
2. `test_api_nomor_increments_when_certificate_already_exists`: API otomatis menaikkan nomor urut jika sertifikat sebelumnya sudah terbit.
3. `test_api_nomor_detects_manual_urut_availability`: API mendeteksi ketersediaan jika operator mengetik nomor urut manual (menandai `is_available: false` jika sudah ada yang memakai).
4. `test_api_nomor_returns_null_fields_for_bootcamp`: API mengembalikan `bernomor: false` untuk kegiatan bootcamp.
5. `test_api_nomor_validates_required_and_existing_event`: Validasi HTTP 422 jika `event_id` kosong atau tidak ada di database.
6. `test_api_kegiatan_returns_list_and_filters`: API mengembalikan daftar kegiatan aktif dan menyaring berdasarkan jenis (`seminar`/`bootcamp`) dan pencarian (`q`).

---

## 4. Panduan Titik Integrasi untuk Rekan Tim

| Rekan Tim | Titik Kolaborasi dengan Bima | Kontrak / Panduan Teknis |
|---|---|---|
| **Giska** (Frontend) | Form Penerbitan Dinamis | Giska kini dapat memanggil endpoint `GET /api/nomor` via `fetch()` di JavaScript form-nya. Setiap kali operator memilih kegiatan atau mengubah tanggal terbit, form memanggil endpoint ini dan langsung menampilkan `nomor_lengkap` dan `urut_saran` di panel ringkasan secara *realtime*. |
| **Ifant** (PDF Generator) | Pemasangan Nomor di Template Seminar | Ifant mencetak string `nomor_lengkap` pada koordinat A4 Seminar: **X = 259 mm (Center)**, **Baseline Y = 77.2 mm**, font Serif ~10 pt. |
| **Akmal** (Database & Admin) | Skema Tabel `events` & `certificates` | Skema yang dibuat Bima 100% identik dengan PRD 7.1. Pada transaksi penerbitan (Langkah 4), baris `events` dikunci dengan `lockForUpdate()`. |

---

## 5. Rencana Langkah Berikutnya (Langkah 4)

Tahap berikutnya yang akan dikerjakan oleh Bima:
* **Langkah 4: Orkestrasi Penerbitan Atomik (`IssueService`) (Fase 6B)**
  * Membuat Form Request `App\Http\Requests\IssueRequest` untuk validasi input penerbitan seminar dan bootcamp.
  * Membangun service `App\Services\IssueService`:
    * Eksekusi di dalam `DB::transaction()`.
    * Penguncian baris kegiatan dengan `lockForUpdate()`.
    * Generate nomor via `NumberAllocator` dan `NumberGenerator`.
    * Simpan sertifikat dengan status `terbit`, salinan `data_cetak` JSON, dan `versi_layout`.
    * Integrasi `PdfRenderer` sebelum transaksi commit (jika render gagal $\rightarrow$ rollback otomatis sehingga nomor tetap aman tidak terpakai).
  * Menyediakan endpoint `POST /terbitkan` dan `POST /pratinjau`.
  * Menulis Feature Test alur penerbitan seminar, bootcamp, dan uji atomik rollback.
