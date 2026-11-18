# Generator Sertifikat XCODE

**Konsep Ide**

Latar masalah, ide solusi, temuan dari sertifikat asli, keputusan desain awal, dan hal yang belum pasti

**Produk:** Generator Sertifikat XCODE, aplikasi web berbasis Laravel 12 untuk menerbitkan sertifikat seminar/webinar dan bootcamp/magang dalam bentuk PDF

**Pemberi tugas:** Coach divisi programmer XCODE

**Penyusun:** [isi nama peserta magang]

**Tech stack:** Laravel 12, PHP 8.2+, MySQL 8 / MariaDB 10.6+, Blade, Vite, TCPDF

**Versi:** Draf 1.1, 18 November 2026

**Dokumen terkait:** PRD, Business Flow, Design System

---

## 1. Latar masalah

Sertifikat bootcamp dan webinar XCODE saat ini dibuat oleh staf tertentu. Bila staf itu berhalangan, penerbitan sertifikat tertunda karena orang lain tidak tahu format nomor, letak teks, dan templat yang benar. Kesalahan kecil pada nomor atau nama membuat sertifikat resmi tidak bisa dipakai dan harus diterbitkan ulang.

## 2. Ide solusi

Satu aplikasi web internal. Operator mengisi form, sistem membuat nomor sertifikat sesuai aturan XCODE, mencetak data ke templat resmi, lalu langsung mengunduh PDF. Semua sertifikat yang terbit tercatat sebagai riwayat.

Aplikasi ini berfungsi sebagai **sistem cadangan**: siapa pun yang diberi akses bisa menerbitkan sertifikat yang sama persis dengan buatan staf.

## 3. Pengguna dan nilai

| Pengguna | Kebutuhan | Nilai yang diterima |
|---|---|---|
| Operator | Menerbitkan sertifikat cepat tanpa tahu aturan nomor | Form sederhana, nomor otomatis, PDF siap unduh |
| Admin | Menjaga daftar kegiatan dan kode tetap benar | Data master terpusat, riwayat, pembatalan tercatat |
| Peserta | Menerima sertifikat yang rapi dan benar | Nama tercetak tepat, nomor sah |
| Coach dan perusahaan | Penerbitan tidak bergantung pada satu orang | Proses terdokumentasi dan dapat diaudit |

## 4. Temuan dari sertifikat asli

Dianalisis dari sertifikat terbitan XCODE yang dikirim (A4 mendatar), dokumen aturan kode XCODE, dan tiga sertifikat seminar dari event yang sama.

| Temuan | Dampak pada desain |
|---|---|
| Seminar punya nomor dan QR, bootcamp tidak punya keduanya | Penomoran dan QR dibuat opsional per kegiatan |
| Nomor seminar berformat `35/001/003/Xcodetraining/X/2026` | Format dipecah menjadi enam bagian dan dibangun di satu fungsi |
| Tiga sampel dari event yang sama hanya berbeda di angka ketiga (003, 009, 015) | Angka ketiga adalah nomor urut peserta, bisa diubah operator |
| Angka pertama adalah kode kegiatan yang ditetapkan manual (contoh 35 untuk Koding Laravel Hermes AI) | Kode disimpan di tabel kegiatan, bukan dihitung |
| Ada kode varian (255, 335, 522, 588) dan kode yang dipakai bersama dua kegiatan (39, 59, 72, 74) | Kode disimpan sebagai teks dan boleh sama antar kegiatan |
| Bagian keempat bervariasi: `Xcodetraining`, `CSS`, `Bootcamp`, `SiberExpert` | Penerbit menjadi kolom di tabel kegiatan |
| Bulan romawi dan tahun di nomor mengikuti tanggal terbit | Dihitung dari tanggal terbit, bukan diketik |
| Desain dasar sama untuk kedua jenis | Satu gambar latar, dua konfigurasi tata letak |
| Contoh nama: `Mu'ammal Hamidy` (apostrof) dan `Akrom Masani Iksanul Amri` (panjang) | Validasi nama dan pengecilan huruf otomatis wajib ada |
| QR di tiga sampel tampak identik walau nomor berbeda | Diasumsikan QR menaut ke satu alamat tetap |

## 5. Keputusan desain awal

1. **Kegiatan adalah data master.** Operator memilih kegiatan dari daftar, kode dan penerbit terisi otomatis.
2. **Nomor urut disarankan sistem, bisa diubah operator,** karena nomor asli mengikuti daftar peserta, bukan urutan penerbitan.
3. **Keunikan dijaga database,** bukan hanya kode PHP.
4. **Satu tombol, satu sertifikat.** Penerbitan massal tidak masuk versi pertama, tetapi struktur kode disiapkan untuk itu.
5. **Pratinjau tidak memakai nomor.** Operator bisa memeriksa typo tanpa menghabiskan nomor.
6. **Nomor hanya terpakai bila PDF berhasil dibuat.**
7. **Sertifikat tidak diubah setelah terbit.** Koreksi dilakukan dengan pembatalan lalu penerbitan baru.
8. **Posisi teks di file konfigurasi,** bukan di dalam kode, supaya kalibrasi dan penambahan templat mudah.

## 6. Yang belum pasti

| # | Hal | Asumsi sementara |
|---|---|---|
| 1 | Arti angka kedua (`001`) pada nomor | Disebut `seri`, default 001, bisa diubah operator |
| 2 | Bootcamp sekarang tanpa nomor untuk semua jenis | Ya, diatur lewat kolom `bernomor` per kegiatan |
| 3 | Isi QR seminar | Alamat tetap `xcode.co.id/cekkodesertifikat` |
| 4 | Sistem verifikasi publik sudah ada dan perlu terhubung | Di luar lingkup versi 1 |
| 5 | Webinar Siber Expert Asia dan Cyber Sentinel Secure masuk lingkup | Belum masuk |
| 6 | Nomor sertifikat yang dibatalkan boleh dipakai ulang untuk koreksi | Tidak boleh dipakai ulang |

Daftar lengkap dan dampaknya ada di PRD bagian 9.

## 7. Arah produk

**Ketentuan teknologi:** Implementasi wajib menggunakan Laravel 12 sebagai framework utama. Detail arsitektur, struktur direktori, routing, autentikasi, database, dan PDF mengikuti dokumen Design System versi terbaru. Jangan membangun router, session handler, CSRF handler, atau lapisan PDO manual di luar mekanisme Laravel.

- **Versi 1.** Form, penomoran otomatis, PDF seminar dan bootcamp, riwayat, kelola kegiatan dan pengguna.
- **Setelah versi 1.** Penerbitan massal lewat CSV, halaman verifikasi publik dengan QR per nomor, pengiriman lewat email, templat untuk penerbit lain.

---

XCODE | Konsep Ide
