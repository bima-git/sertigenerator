# Catatan Pengerjaan & Panduan Integrasi Modul Bima

**Nama / Peran:** Bima — Backend Logic, Penomoran (*Numbering Engine*) & Proses Penerbitan (*Issuance Orchestrator*)  
**Branch Git:** `feature/bima-numbering`  
**Status Tahap:** Langkah 1 & Langkah 2 (Fase 2A & Fase 2B) Selesai — **100% Lulus Uji Otomatis (32 Tests, 73 Assertions)**  
**Terakhir Diperbarui:** 19 November 2026

---

## 1. Ringkasan Eksekutif

Dokumen ini mencatat seluruh detail implementasi, arsitektur kelas, skema data, dan hasil pengujian yang telah diselesaikan oleh Bima. Dokumen ini dirancang sebagai referensi teknis yang transparan dan dapat dibagikan kepada seluruh rekan tim (**Akmal, Ifant, dan Giska**) agar proses integrasi berjalan lancar tanpa tumpang tindih.

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
     * Menghitung nilai maksimum urut pada kombinasi `(event_id, seri, tahun)` lalu ditambah 1:
       $$\text{COALESCE}(\max(\text{urut}), 0) + 1$$
     * **Sertifikat yang berstatus `dibatalkan` tetap ikut dihitung**, sehingga nomor yang sudah pernah dibatalkan tidak akan pernah dialokasikan ulang untuk peserta baru.
  2. `isAvailable(int $eventId, int $seri, int $urut, int $tahun): bool`
     * Memeriksa apakah kombinasi nomor tertentu masih bebas sebelum disimpan.
  3. `isNumberTaken(string $nomorLengkap): bool`
     * Pengecekan cepat ketersediaan nomor lengkap di database.
  4. `suggest(Event|int $event, int $seri = 1, DateTimeInterface|string|null $tanggalTerbit = null, ?int $manualUrut = null): array`
     * Helper terpadu yang menghasilkan payload saran nomor lengkap, status ketersediaan, serta penanganan otomatis untuk kegiatan tanpa nomor (bootcamp).

---

## 3. Hasil Pengujian Otomatis (*Automated Tests*)

Pengujian dijalankan melalui `php artisan test`. Seluruh 32 pengujian berstatus **100% HIJAU (PASS)**.

### A. Pengujian Unit Test (`tests/Unit/Services/`): 20 Tests, 47 Assertions
* **`NumberGeneratorTest` (14 pengujian):** Lulus 100% seluruh kasus uji wajib PRD 5.3 (Kasus 1 s.d. 7, kode `35`, `15`, `23`, `255`, batas bulan Januari/Desember, parsing, dan proteksi input tidak valid).
* **`RomanTest` (5 pengujian):** Lulus konversi bulan 1–12, konversi umum hingga 3999, serta penanganan exception angka di luar batas.

### B. Pengujian Feature / Integrasi Database (`tests/Feature/Services/NumberAllocatorTest.php`): 11 Tests, 25 Assertions
1. `test_next_returns_one_when_no_certificates_exist`: Alokasi awal dimulai dari nomor 1.
2. `test_next_increments_sequentially`: Nomor urut bertambah sekuensial (1 -> 2 -> 3).
3. `test_next_follows_max_value_when_non_sequential`: Jika ada loncatan nomor manual oleh operator, saran berikutnya adalah `MAX + 1`.
4. `test_allocation_is_isolated_per_series`: Penomoran diisolasi per nomor seri (seri 1 tidak mencampuri seri 2).
5. `test_allocation_is_isolated_per_year`: Penomoran diisolasi per tahun terbit (tahun 2025 tidak mencampuri tahun 2026).
6. `test_allocation_is_isolated_per_event`: Penomoran diisolasi antar-event yang berbeda.
7. `test_cancelled_certificates_are_included_in_next_calculation`: Sertifikat berstatus dibatalkan tetap dihitung, nomor batal tidak dipakai ulang.
8. `test_is_available_checks_availability`: Mendeteksi nomor bebas vs nomor yang sudah terpakai (termasuk yang dibatalkan).
9. `test_suggest_returns_complete_payload_for_numbered_event`: Menghasilkan payload lengkap dan valid untuk form penerbitan seminar.
10. `test_suggest_returns_null_fields_for_unnumbered_event`: Menghasilkan `bernomor => false` dan nomor `null` untuk kegiatan bootcamp.
11. `test_database_enforces_unique_constraint`: Database menolak duplikasi nomor via `QueryException` pada *unique constraint*.

---

## 4. Panduan Titik Integrasi untuk Rekan Tim

| Rekan Tim | Titik Kolaborasi dengan Bima | Kontrak / Panduan Teknis |
|---|---|---|
| **Giska** (Frontend) | Form Penerbitan Dinamis | Giska akan memanggil endpoint `GET /api/nomor` (Langkah 3) yang langsung didukung oleh `NumberAllocator::suggest()`. Output menyajikan saran urut dan pratinjau string nomor secara *realtime*. |
| **Ifant** (PDF Generator) | Pemasangan Nomor di Template Seminar | Ifant mencetak string `nomor_lengkap` pada koordinat A4 Seminar: **X = 259 mm (Center)**, **Baseline Y = 77.2 mm**, font Serif ~10 pt. |
| **Akmal** (Database & Admin) | Skema Tabel `events` & `certificates` | Skema yang dibuat Bima 100% identik dengan PRD 7.1. Pada transaksi penerbitan (Langkah 4), baris `events` dikunci dengan `lockForUpdate()`. |

---

## 5. Rencana Langkah Berikutnya (Langkah 3)

Tahap berikutnya yang akan dikerjakan oleh Bima:
* **Langkah 3: Endpoint API Penomoran untuk Frontend Giska (`GET /api/nomor`)**
  * Membangun controller / method `IssueController@suggest` (Route: `api.number`).
  * Menerima query parameter: `event_id`, `seri` (default 1), `tanggal_terbit`, dan `urut` (opsional jika ditimpa operator).
  * Mengembalikan respons JSON siap pakai untuk frontend form dinamis Giska.
  * Menulis Feature Test untuk memastikan respon API konsisten dan menangani validasi input.
