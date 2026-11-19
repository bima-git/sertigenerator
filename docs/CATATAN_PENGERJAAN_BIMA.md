# Catatan Pengerjaan & Panduan Integrasi Modul Bima

**Nama / Peran:** Bima — Backend Logic, Penomoran (*Numbering Engine*) & Proses Penerbitan (*Issuance Orchestrator*)  
**Branch Git:** `feature/bima-numbering`  
**Status Tahap:** Langkah 1 (Fase 2A - Engine Penomoran Murni) Selesai — **100% Lulus Uji Otomatis**  
**Terakhir Diperbarui:** 19 November 2026

---

## 1. Ringkasan Eksekutif

Dokumen ini mencatat seluruh detail implementasi, arsitektur kelas, dan hasil pengujian yang telah diselesaikan oleh Bima. Dokumen ini dapat digunakan sebagai referensi penjelasan teknis kepada rekan kolaborator tim (**Akmal, Ifant, dan Giska**) untuk memastikan titik integrasi antar-modul berjalan selaras sesuai PRD dan Design System.

---

## 2. Rincian Modul yang Telah Selesai Dibangun (Langkah 1)

### A. Helper Angka Romawi (`App\Services\Roman`)
* **Lokasi Berkas:** [`app/Services/Roman.php`](file:///D:/MAGANG_2026/sertigenerator/app/Services/Roman.php)
* **Fungsi Utama:**
  1. `Roman::fromMonth(int $month): string`
     * Mengonversi angka bulan (1 s.d. 12) menjadi representasi Romawi resmi XCODE (`I` sampai `XII`).
     * Dilengkapi validasi ketat: melempar `InvalidArgumentException` jika bulan di luar rentang 1–12.
  2. `Roman::toRoman(int $number): string`
     * Konversi umum angka desimal (1 s.d. 3999) ke angka Romawi (mendukung kebutuhan formatting lainnya).

---

### B. Service Pembangun Nomor Sertifikat (`App\Services\NumberGenerator`)
* **Lokasi Berkas:** [`app/Services/NumberGenerator.php`](file:///D:/MAGANG_2026/sertigenerator/app/Services/NumberGenerator.php)
* **Aturan Bisnis Sesuai PRD:**
  Sesuai aturan bisnis **AB-03**, seluruh nomor sertifikat resmi pada sistem ini **hanya boleh dibangun** melalui kelas `NumberGenerator::build()`.
* **Formula Resmi:**
  ```text
  {kode}/{seri}/{urut}/{penerbit}/{bulan_romawi}/{tahun}
  Contoh: 35/001/003/Xcodetraining/X/2026
  ```
* **Fungsi-Fungsi yang Tersedia:**
  1. `NumberGenerator::build(string $kode, int|string $seri, int|string $urut, string $penerbit, DateTimeInterface|string $tanggalTerbit): string`
     * **`kode`**: Kode kegiatan (string teks, misal: `'35'`, `'255'`, `'15'`).
     * **`seri`**: Nomor seri (otomatis di-*pad* 3 digit menjadi `'001'`).
     * **`urut`**: Nomor urut peserta (otomatis di-*pad* 3 digit, misal 3 menjadi `'003'`, 15 menjadi `'015'`).
     * **`penerbit`**: Nama penerbit resmi (misal: `'Xcodetraining'`, `'Bootcamp'`, `'SiberExpert'`).
     * **`tanggalTerbit`**: Menerima objek `Carbon`, `DateTime`, maupun string tanggal (misal `'2026-10-24'`). Zona waktu dipatok ke `Asia/Jakarta`. Bulan dikonversi otomatis ke angka Romawi dan tahun diekstrak 4 digit.
  2. `NumberGenerator::parse(string $nomor): array`
     * Memecah string nomor sertifikat yang sudah jadi ke dalam array terstruktur: `['kode', 'seri', 'urut', 'penerbit', 'bulan_romawi', 'tahun']`.
  3. `NumberGenerator::isValid(string $nomor): bool`
     * Memvalidasi apakah sebuah string mematuhi pola format resmi sertifikat XCODE.

---

## 3. Hasil Pengujian Unit Test (Otomatis)

Pengujian dilakukan menggunakan PHPUnit / test suite Laravel (`php artisan test --testsuite=Unit`). Seluruh kasus uji wajib yang tercantum pada **PRD Bagian 5.3** telah diuji dan **100% HIJAU (PASS)**.

### Matriks Pengujian Kasus Uji Wajib PRD 5.3:

| Kasus | Input Pengujian | Target Hasil PRD | Status Uji |
|:---:|---|---|:---:|
| **1** | Kode `35`, seri 1, urut 3, `Xcodetraining`, `2026-10-24` | `35/001/003/Xcodetraining/X/2026` | ✅ **PASS** |
| **2** | Kode `35`, seri 1, urut 9, `Xcodetraining`, `2026-10-24` | `35/001/009/Xcodetraining/X/2026` | ✅ **PASS** |
| **3** | Kode `35`, seri 1, urut 15, `Xcodetraining`, `2026-10-24` | `35/001/015/Xcodetraining/X/2026` | ✅ **PASS** |
| **4** | Kode `15`, seri 1, urut 1, `Bootcamp`, `2025-02-10` | `15/001/001/Bootcamp/II/2025` | ✅ **PASS** |
| **5** | Kode `23`, seri 1, urut 1, `SiberExpert`, `2024-05-03` | `23/001/001/SiberExpert/V/2024` | ✅ **PASS** |
| **6** | Kode `255`, seri 1, urut 1, `Xcodetraining`, `2026-12-01` | `255/001/001/Xcodetraining/XII/2026` | ✅ **PASS** |
| **7** | Batas awal & akhir tahun (`2026-01-31` & `2026-12-31`) | `I/2026` dan `XII/2026` | ✅ **PASS** |
| **8** | Input invalid (kode kosong, seri <= 0, urut <= 0) | Melempar `InvalidArgumentException` | ✅ **PASS** |
| **9** | Parsing & validasi regex nomor lengkap | Komponen segmen terekstraksi tepat | ✅ **PASS** |

* **Total Test Suite Unit:** **20 passed, 47 assertions** (Waktu eksekusi: ~0.29s).

---

## 4. Panduan Titik Integrasi untuk Rekan Tim

### 1. Untuk Giska (Frontend & UI):
* **Fungsi yang akan dipakai:** Saat Giska membuat form dinamis penerbitan di Fase 6, form akan memanggil endpoint `GET /api/nomor`.
* Output dari endpoint tersebut akan memanfaatkan `NumberGenerator::build()` untuk mengirim string nomor lengkap sehingga komponen Blade `number-preview` dan `summary-panel` dapat menampilkan pratinjau nomor secara *realtime* kepada operator sebelum tombol terbitkan diklik.

### 2. Untuk Ifant (PDF Generator):
* **Kebutuhan PDF:** Di template PDF Seminar, string nomor sertifikat dicetak pada posisi:
  * Koordinat: **X = 259 mm (Center)**, **Baseline Y = 77.2 mm**.
  * Font: Serif ~10 pt (maksimal lebar 58 mm).
* Ifant cukup mengambil field `nomor_lengkap` dari model sertifikat yang telah digenerate oleh modul Bima.

### 3. Untuk Akmal (Database & Administrasi):
* **Struktur Kolom Database:**
  * Tabel `certificates` membutuhkan kolom:
    * `seri SMALLINT UNSIGNED NULL`
    * `urut SMALLINT UNSIGNED NULL`
    * `tahun SMALLINT UNSIGNED NOT NULL`
    * `nomor_lengkap VARCHAR(120) NULL UNIQUE`
    * `UNIQUE KEY uq_event_seri_urut_tahun (event_id, seri, urut, tahun)`
* **Aturan Konkurensi:** Pada transaksi penerbitan, baris tabel `events` wajib dikunci menggunakan `lockForUpdate()` agar alokasi nomor urut bebas dari *race condition*.

---

## 5. Rencana Langkah Berikutnya (Langkah 2)

Tahap berikutnya yang akan dikerjakan oleh Bima:
1. Menyiapkan service **`App\Services\NumberAllocator`**:
   * Menghitung nomor urut saran sistem (`COALESCE(MAX(urut), 0) + 1`) per `(event_id, seri, tahun)`.
   * Menjamin nomor dari sertifikat yang berstatus **dibatalkan tetap dihitung** (tidak boleh dipakai ulang).
   * Fitur pengecekan ketersediaan nomor jika operator menginput nomor urut manual.
2. Menulis Feature/Integration Test untuk `NumberAllocator`.
