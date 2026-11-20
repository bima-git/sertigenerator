# Catatan Pengerjaan & Panduan Modul Ifant (Generator PDF)

**Nama / Peran:** Ifant — PDF Generator & Kalibrasi Template Sertifikat
**Branch Git:** `ifant` (PR #6 sudah di-merge ke `main`, PR #7 berisi perbaikan kalibrasi)
**Status Tahap:** Fase 4 (template, font, background, QR, kalibrasi seminar), Fase 5 (bootcamp), dan integrasi renderer selesai
**Hasil Uji:** `php artisan test` → 145 tes, 477 assertions, semuanya lulus
**Terakhir Diperbarui:** 20 November 2026

---

## 1. Ringkasan

Modul ini mengubah data sertifikat yang sudah tervalidasi (dari `IssueService` milik Bima) menjadi PDF A4 mendatar. Seluruh koordinat, font, ukuran, dan teks tetap disimpan sebagai konfigurasi PHP di `config/layout/`, bukan di dalam kode renderer, supaya kalibrasi bisa dilakukan tanpa menyentuh logika program.

Dua hal yang membedakan modul ini dari generator PDF biasa:

1. **Semua angka layout bersatuan milimeter** dan diukur dari sudut kiri atas halaman, sehingga bisa dicocokkan langsung dengan contoh sertifikat asli.
2. **Setiap versi layout diarsipkan.** Sertifikat menyimpan `certificates.versi_layout`, jadi unduh ulang memakai salinan beku versi saat terbit, bukan layout terbaru.

---

## 2. Alur Render

```
Certificate (model Bima)
        │
        ▼
App\Pdf\CertificateDataMapper   → data_cetak + field tampilan
        │
        ▼
App\Services\PdfRenderer        → adapter tipis, kontrak milik Bima:
        │                          render(Certificate $certificate, string $mode = 'terbit'): string
        ▼
App\Pdf\CertificateRenderer     → TCPDF: background, elemen, watermark, QR
        │
        ▼
App\Services\LayoutConfig       → layout aktif atau arsip sesuai versi_layout
```

- `PdfRenderer::render()` dipanggil **apa adanya** oleh `IssueService` dan `HistoryService` milik Bima; modul ini tidak mengubah pemanggil tersebut.
- `CertificateDataMapper` menyiapkan nilai seperti `nama_peserta`, `nomor_lengkap`, `tanggal_kota`, `judul_cetak`/`ujian`+`bootcamp`, dan `pakai_qr`.
- Mode dokumen berasal dari `App\Enums\CertificateMode`: `terbit`, `pratinjau`, `dibatalkan`.

---

## 3. Peta Berkas Modul

| Berkas | Peran |
|---|---|
| `app/Pdf/CertificateRenderer.php` | Mesin render TCPDF: gambar elemen, garis, QR, watermark, garis bantu |
| `app/Pdf/CertificateDocument.php` | Subclass TCPDF (margin 0, tanpa header/footer, auto page break mati) |
| `app/Pdf/CertificateDataMapper.php` | Certificate → array data layout |
| `app/Services/PdfRenderer.php` | Adapter untuk `IssueService`/`HistoryService` |
| `app/Services/LayoutConfig.php` | Loader + validator layout dan arsip versi |
| `app/Services/TextFitter.php` | Pengecil ukuran font & pemecah baris untuk nama/judul panjang |
| `app/Services/TcpdfTextMeasurer.php` | Pengukur lebar teks nyata milik TCPDF untuk `TextFitter` |
| `app/Services/SampleCertificateData.php` | Data contoh untuk pratinjau & sampel |
| `app/Console/Commands/ImportFonts.php` | Impor `.ttf` menjadi definisi font TCPDF |
| `app/Console/Commands/MakeSampleCertificates.php` | Cetak PDF sampel untuk kalibrasi |
| `config/layout/seminar.php`, `config/layout/bootcamp.php` | Layout aktif |
| `config/layout/arsip/*.php` | Salinan beku layout per versi |
| `config/certificate.php` | Warna teks, isi QR, pengaturan watermark |
| `resources/views/pratinjau.blade.php` | Halaman uji kalibrasi di browser |

---

## 4. Peta `config/layout`

### A. Struktur Berkas

```php
return [
    'versi' => '1.2',                       // wajib, dipakai untuk arsip
    'background' => 'assets/templates/background.png',
    'font' => [
        'serif' => 'tinos',                 // wajib
        'serif_bold' => 'tinosb',           // wajib
        'script' => 'pinyonscript',         // opsional
        'sans' => 'poppins',                // opsional
        'fallback' => 'times',              // font inti TCPDF
    ],
    'page' => ['size' => 'A4', 'orientation' => 'L'],
    'elements' => [ /* ... */ ],
];
```

`LayoutConfig` memvalidasi seluruh kunci di atas saat aplikasi dijalankan. Berkas layout yang kehilangan kunci wajib akan menggagalkan request dengan `InvalidArgumentException`, bukan menghasilkan PDF cacat.

### B. Semantik Elemen

Semua `x`/`y` dalam **milimeter dari sudut kiri atas**, `size` dalam **point**.

| `type` | Kunci wajib | Arti koordinat | Kunci opsional penting |
|---|---|---|---|
| `text` | `text`/`field`/`template`, `x`, `y`, `align`, `size` | `y` = baseline teks; `x` = tepi kiri (`L`) atau titik tengah (`C`) | `font` (`serif`, `serif_bold`, `script`, `sans`), `line_h` |
| `fit` | sama seperti `text` + `min_size`, `max_w` | idem, tetapi ukuran font mengecil sampai muat | `max_lines` (bawaan 1), `line_h` (bawaan `size × 0,4`), `balance` |
| `image` | `path`, `x`, `y`, `w`, `h` | `x`/`y` = sudut kiri atas gambar | `optional` (boleh hilang tanpa error) |
| `line` | `x`, `y`, `w`, `tebal` | `y` = sumbu garis, `w` = panjang, `tebal` = ketebalan | `color` `[r,g,b]` |
| `qr` | `x`, `y`, `size` | `x`/`y` = sudut kiri atas, `size` = sisi persegi | — |

`align` hanya menerima `L` dan `C`. Elemen digambar berurutan sesuai urutan array, jadi gambar (logo, tanda tangan) diletakkan lebih dulu agar teks berada di atasnya.

### C. Peta Elemen Seminar (versi 1.2)

| Elemen | Isi | Posisi acuan (mm) |
|---|---|---|
| `judul_besar` | "SERTIFIKAT SEMINAR" | x 9,21 / y 36,30, 41 pt |
| `judul_kegiatan` | `judul_cetak` (auto-shrink) | x 9,21 / y 47,07, 18 pt, max 210 mm, 2 baris |
| `script_sertifikat` | "Sertifikat" (PinyonScript) | x 33,28 / y 109,14, 135,9 pt |
| `diberikan_kepada` | teks tetap | x 117,99 / y 122,70, 19,85 pt |
| `domain` | "xcode.or.id" | x 240,88 / y 71,13, 23 pt |
| `nomor` | `nomor_lengkap`, rata tengah | pusat x 258,9 / y 77,53, 11 pt |
| `qr` | QR verifikasi | x 240,3 / y 87,0, 36,5 mm |
| `url_verifikasi` | alamat teks di bawah QR | pusat x 258,9 / y 128,44, 9 pt |
| `nama` | `nama_peserta` (auto-shrink) | pusat x 120 / y 151,62, 28 pt, max 148 mm, 2 baris |
| `garis_nama` | garis bawah nama | x 45,96 / y 157,07, panjang 148,7 mm, tebal 1,12 mm |
| `tanggal` | `tanggal_kota`, rata tengah | pusat x 258,9 / y 159,61, 14 pt |
| `tanda_tangan`, `garis_tanda_tangan` | gambar & garis ttd | gambar x 234,12 / y 156,01, 50,55 × 38,38 mm; garis y 185,79 |
| `penanda_tangan`, `jabatan_penanda_tangan` | nama & jabatan | pusat x 258,9 / y 193,14 (15 pt bold) dan 198,75 (13 pt) |
| `logo` | pita logo kanan atas | x 210 / y 0, 75 × 62,5 mm |

### D. Peta Elemen Bootcamp (versi 1.4)

Struktur sama dengan seminar, perbedaannya:

| Elemen | Isi | Catatan |
|---|---|---|
| `judul_besar` | "SERTIFIKAT PENCAPAIAN" | x 9,21 / y 36,30, 41 pt |
| `subjudul_1` | templat `Ujian {ujian}` | x 9,21 / y 47,98, 22 pt, max 210 mm |
| `subjudul_2` | `{bootcamp}` | x 9,21 / y 55,92, 22 pt, max 212 mm |
| `nama` | `nama_peserta` | pusat x 120 / y 145,48, 28 pt, max 148 mm, 2 baris |
| `garis_nama` | garis bawah nama | x 45,96 / y 150,93, panjang 148,7 mm, tebal 1,12 mm |
| `paragraf` | templat `Peserta dinyatakan telah lulus {ujian} pada {bootcamp}.` | pusat x 120,4 / y 165,46, 15 pt, max 137 mm, 2 baris, `balance: false` |
| `penanda_tangan` | "Cantika" | pusat x 258,9 / y 193,14, **serif bold** sejak versi 1.4 (sampel asli memakai Times bold) |

Bootcamp **tidak** mencetak nomor, URL verifikasi, dan QR — sama dengan sampel asli `Taufik Hidayat.pdf` dan aturan PRD PD-08.

---

## 5. Versi & Arsip Layout

### A. Aturan

1. Setiap perubahan yang mengubah hasil PDF (koordinat, ukuran, font, teks tetap) **wajib menaikkan `versi`**.
2. Berkas lama **disalin apa adanya** ke `config/layout/arsip/{jenis}-{versi}.php`, lalu bagian atasnya diberi komentar bahwa berkas itu arsip beku.
3. Berkas arsip **tidak boleh diubah**; kalau perlu perbaikan, buat versi baru lagi.
4. Nama berkas boleh memakai titik (`seminar-1.2.php`). Arsip dibaca langsung dari disk, bukan lewat `config()`, karena Laravel memecah nama bertitik menjadi kunci bersarang.

### B. Cara Kerja Pemilihan Versi

- `LayoutConfig::get($jenis)` → layout aktif.
- `LayoutConfig::get($jenis, $versi)` → arsip `{jenis}-{versi}` bila ada; bila tidak ada, kembali ke layout aktif.
- `PdfRenderer` mengirim `$certificate->versi_layout`, jadi sertifikat lama tetap tercetak dengan koordinat saat terbit. Sertifikat yang terbit **sebelum** arsip dibuat akan memakai layout aktif (`versi_layout` lama seperti `1.0` tidak punya arsip) — ini kompromi yang disengaja dan sudah didokumentasikan di tes.

### C. Status Versi Saat Ini

| Jenis | Versi aktif | Arsip tersedia |
|---|---|---|
| `seminar` | 1.2 | 1.2 |
| `bootcamp` | 1.4 | 1.3 |

---

## 6. Prosedur Kalibrasi

Kalibrasi dilakukan dengan membandingkan PDF hasil generator terhadap contoh sertifikat asli (ekspor Canva), bukan hanya dengan mata.

### Langkah 1 — Cetak sampel

```bash
php artisan certificates:make-samples --output=storage/app/kalibrasi --mode=terbit
php artisan certificates:make-samples --output=storage/app/kalibrasi/preview --mode=pratinjau
```

Opsi lain: `--jenis=seminar|bootcamp`, `--guides` (grid 10 mm + penanda koordinat tiap elemen), `--mode=dibatalkan`.

### Langkah 2 — Cek kasar di browser

```bash
php artisan serve
# http://127.0.0.1:8000/pratinjau?jenis=seminar&guides=1
# http://127.0.0.1:8000/pratinjau?jenis=bootcamp&mode=pratinjau
```

Halaman pratinjau menerima parameter `jenis`, `sampel` (nama berkas contoh), `mode` (`terbit|pratinjau|dibatalkan`), `guides` (`1`), dan `latar` (`0` untuk mematikan background saat mengukur teks).

### Langkah 3 — Ukur, jangan menebak

Contoh asli menyimpan teks asli, jadi posisinya bisa diekstrak. Bandingkan **baseline** (`span.origin`), bukan bounding box, karena bbox ikut terpengaruh ascender/descender font:

```python
import pymupdf
p = pymupdf.open("contoh.pdf")[0]
for b in p.get_text("dict")["blocks"]:
    for l in b.get("lines", []):
        s = l["spans"][0]
        print(f'{s["origin"][1]:7.2f}', s["font"], round(s["size"], 2), "".join(x["text"] for x in l["spans"]))
```

Untuk elemen non-teks, pakai `page.get_drawings()` (garis) dan `page.get_image_info()` (logo, tanda tangan, QR).

### Langkah 4 — Ubah `config/layout`

Ubah angka pada elemen terkait saja. Konversi: **1 pt = 0,352778 mm**. Contoh: teks 3 pt terlalu rendah → kurangi `y` sebesar 1,06 mm.

### Langkah 5 — Naikkan versi & arsipkan

```bash
cp config/layout/seminar.php config/layout/arsip/seminar-1.2.php   # simpan versi lama
# ubah 'versi' => '1.3' di config/layout/seminar.php, lalu edit koordinat
```

### Langkah 6 — Verifikasi ulang

```bash
php artisan test --filter='LayoutConfigTest|CertificateRendererTest'
php artisan certificates:make-samples --output=storage/app/kalibrasi --mode=terbit
```

### Hasil Kalibrasi 20 November 2026

Seminar (`Ridla Dratistiwa.pdf`, `Syafrizal Devan Pradana.pdf`) sudah tepat, selisih baseline ≤ 0,02 pt:

| Elemen | Contoh | Generator |
|---|---|---|
| SERTIFIKAT SEMINAR | 102,88 | 102,90 |
| Sertifikat (script) | 309,36 | 309,37 |
| diberikan kepada | 347,79 | 347,81 |
| xcode.or.id | 201,62 | 201,63 |
| nomor | 219,77 | 219,77 |
| nama peserta | 429,78 | 429,79 |
| tanggal | 452,44 | 452,44 |
| nama tanda tangan | 547,48 | 547,48 |

Garis bawah nama (x 130,22–551,62 pt, tebal 3,18 pt) dan garis tanda tangan (y 526,69 pt, tebal 1,50 pt) juga identik. Bootcamp sama presisi; satu-satunya perbaikan yang lahir dari pengukuran adalah `penanda_tangan` bootcamp yang tadinya `poppins` dan kini `serif_bold` (versi 1.4).

Sisa perbedaan yang **bukan** cacat layout: contoh Ridla memakai subjudul 16 pt sedangkan contoh Syafrizal 18 pt (Canva menyesuaikan per sertifikat), dan logo mengikuti penerbit kegiatan (`siberexpert.com` vs `xcode.or.id`).

### Checklist Sebelum Commit Perubahan Layout

- [ ] Angka diubah hanya pada elemen yang terbukti bergeser dari hasil ukur
- [ ] `versi` dinaikkan
- [ ] Berkas lama tersalin ke `config/layout/arsip/{jenis}-{versi-lama}.php`
- [ ] `php artisan test --filter='LayoutConfigTest|CertificateRendererTest'` lulus
- [ ] Sampel `terbit` dan `pratinjau` sudah dilihat ulang
- [ ] Nomor versi di tes (`LayoutConfigTest`) diperbarui bila berubah

---

## 7. Langkah Font Setelah Clone Baru

Definisi font TCPDF **tidak masuk git** (berada di `storage/app/fonts`), sedangkan `.ttf` sumber juga tidak dibundel. Tanpa langkah ini PDF tetap jadi, tetapi memakai font inti `times`: font biasa dan khusus mencatat `Log::warning`, sedangkan `serif_bold` diam-diam jatuh ke `times` gaya `B`.

```bash
composer install
php artisan certificates:import-fonts --download   # unduh Tinos, PinyonScript, Poppins (OFL) ke assets/fonts
php artisan certificates:import-fonts              # impor menjadi definisi TCPDF di storage/app/fonts
```

| Berkas `.ttf` | Nama keluarga di layout |
|---|---|
| `Tinos-Regular.ttf` | `tinos` |
| `Tinos-Bold.ttf` | `tinosb` |
| `Tinos-Italic.ttf` | `tinosi` |
| `Tinos-BoldItalic.ttf` | `tinosbi` |
| `PinyonScript-Regular.ttf` | `pinyonscript` |
| `Poppins-Regular.ttf` | `poppins` |

Catatan:

- Font asli sertifikat (Times New Roman MT Pro, Poppins Canva, Pinyon Script Canva) tidak boleh dibundel; Tinos dipakai karena metriknya kompatibel dengan Times New Roman, sehingga posisi teks hasil ukur tetap berlaku.
- `--source=` untuk memakai folder `.ttf` lain, `--output=` untuk folder definisi lain, `--force` untuk membangun ulang.
- Watermark tidak memakai font impor: sengaja memakai font inti `times`/`B` agar teksnya tetap terbaca mesin di dalam stream PDF.
- Bila `vendor/` hilang, cukup `composer install`; TCPDF dikunci di `^6.9` (lihat README) karena 7.x menghapus `addTTFfont`.

---

## 8. Perintah & Pengaturan Lain

| Perintah | Fungsi |
|---|---|
| `php artisan certificates:make-samples [--output=] [--mode=] [--jenis=] [--guides]` | Cetak 3 sampel seminar + 1 bootcamp dengan data contoh tanggal 24 Oktober 2026 |
| `php artisan certificates:import-fonts [--download] [--source=] [--output=] [--force]` | Impor font ke `storage/app/fonts` |

Pengaturan di `config/certificate.php`:

- `text_color` — warna teks utama `[17,17,17]`.
- `qr.url` — satu alamat untuk semua sertifikat: `https://xcode.co.id/cekkodesertifikat`.
- Tampil atau tidaknya QR diatur lewat data event `pakai_qr` (`CertificateDataMapper`), bukan dengan mengubah alamat; pemanggil juga boleh mengirim `pakai_qr => false` untuk melewati elemen QR tanpa menyunting layout.
- `watermark.pratinjau` / `watermark.dibatalkan` — teks, warna, alpha, ukuran (90 pt), sudut (45°). Nilainya masih perkiraan dan nanti akan menjadi input teks bebas, sehingga watermark tidak lagi terbatas pada dua label itu.

Metadata PDF: `SetTitle('Sertifikat {jenis} - {nama peserta} ({mode})')` memenuhi PD-12, dan `SetSubject('Status dokumen: TERBIT|PRATINJAU|DIBATALKAN')` disimpan tanpa kompresi agar status bisa dicek mesin tanpa membongkar stream. TCPDF menulis metadata sebagai UTF-16BE ber-BOM, jadi pembaca otomatis perlu mendekodenya.

---

## 9. Kontrak Integrasi

**Ke Bima (penerbitan & riwayat):** jangan panggil `CertificateRenderer` langsung. Gunakan `App\Services\PdfRenderer::render(Certificate $certificate, string $mode = 'terbit')`. Renderer memilih layout dari `versi_layout` sertifikat, jadi unduh ulang otomatis mengikuti versi saat terbit.

**Ke Giska (frontend):** pratinjau di browser memakai `GET /pratinjau` (halaman) dan `GET /pratinjau/pdf?jenis=&sampel=&mode=&guides=` (PDF inline). Keduanya berada di dalam grup `auth` + `active` seperti halaman lain, dan halaman memakai shell `layouts.app` (sidebar, komponen `x-page-header`, `card`, `select.input`, `switch`, `badge`, `btn`). Tombol pratinjau pada form penerbitan tetap memakai `POST /pratinjau` milik Bima (`issue.preview`) karena memakai data form, bukan data contoh.

**Batasan kontrak data:** event seminar dengan `bernomor = false` akan gagal render karena elemen `nomor` wajib ada; keputusan apakah seminar boleh tanpa nomor masih menunggu konfirmasi coach.

---

## 10. Pengujian

| Berkas | Cakupan |
|---|---|
| `tests/Feature/CertificateRendererTest.php` | Teks & elemen seminar/bootcamp, QR, garis bantu, mode watermark, layout arsip, file output, metadata judul, nama 70 karakter & judul panjang tetap satu halaman |
| `tests/Unit/Services/LayoutConfigTest.php` | Validasi layout, pemilihan versi arsip, fallback, font penanda tangan, versi arsip |
| `tests/Unit/Pdf/CertificateDataMapperTest.php` | Pemetaan Certificate → data layout |
| `tests/Unit/Services/TextFitterTest.php` | Pengecilan font & pemecahan baris nama panjang |
| `tests/Feature/CertificatePreviewTest.php` | Halaman & endpoint PDF pratinjau |
| `tests/Feature/MakeSampleCertificatesCommandTest.php` | Perintah sampel |
| `tests/Feature/ImportFontsCommandTest.php` | Perintah impor font |

Perintah: `php artisan test --filter='CertificateRendererTest|LayoutConfigTest'`.

---

## 11. Batasan & Pertanyaan Terbuka

1. **Seminar tanpa nomor** — `RenderException` saat `bernomor = false`; perlu keputusan coach.
2. **Tujuan QR** — sudah diputuskan satu alamat untuk semua sertifikat, `https://xcode.co.id/cekkodesertifikat`; mode per nomor dihapus dari konfigurasi.
3. **Watermark** — ukuran/warna/alpha masih perkiraan; akan menjadi input teks bebas yang bisa diganti-ganti, jadi jangan dikunci ke dua label `PRATINJAU`/`DIBATALKAN`.
4. **Contoh asli tidak masuk repo** — empat PDF contoh ada di root dan belum di-commit, sehingga kalibrasi belum bisa diuji otomatis di CI.
5. **Halaman pratinjau wajib login** — sudah memakai shell `layouts.app`, sehingga tidak bisa dibuka tanpa sesi. Bila nanti perlu diakses publik, buat layout tamu terpisah, bukan melonggarkan grup `auth`.
6. **Font sumber** — `.ttf` tidak dibundel; setiap mesin/runner baru wajib menjalankan `certificates:import-fonts --download` sebelum render final.
