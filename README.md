# Generator Sertifikat XCODE

Aplikasi Laravel 12 untuk menerbitkan sertifikat seminar dan bootcamp XCODE Training, dengan keluaran PDF A4 mendatar.

## Dependensi PDF: TCPDF dikunci di `^6.9`

`composer.json` memakai `"tecnickcom/tcpdf": "^6.9"` (terpasang 6.11.4) dan **sengaja tidak dibiarkan naik ke 7.x**. Jangan longgarkan pin ini tanpa menyelesaikan tiga hal berikut:

1. **Aset font tidak lagi dibundel.** TCPDF 7 memindahkan seluruh font ke `tecnickcom/tc-lib-pdf-font`, yang harus dibangun lebih dulu dengan `make fonts`. Akibatnya render gagal dengan `Com\Tecnick\Pdf\Font\Exception: unable to read file: helvetica.json` pada instalasi bersih, dan mesin pengembangan Windows di proyek ini tidak punya `make`.
2. **`addTTFfont` dihapus.** Dokumen desain memakai `addTTFfont` untuk membenamkan Tinos/Liberation Serif sebagai pengganti sementara font asli sertifikat. Di 7.x font hanya bisa dimasukkan lewat importer `tc-lib-pdf-font`.
3. **Font `.php`/`.z` tidak didukung lagi.** `K_PATH_FONTS` dan definisi font gaya lama yang dirujuk Design System bagian 9.1/9.8 tidak berlaku di 7.x.

Bila `vendor/` hilang, cukup jalankan `composer install`; versi terkunci sudah tersimpan di `composer.lock`. Bila suatu saat memang harus pindah ke TCPDF 7, siapkan `make` di semua mesin dan runner CI, jalankan `make -C vendor/tecnickcom/tc-lib-pdf-font deps fonts`, lalu ganti mekanisme pemuatan font sebelum memperbarui pin.

## Font sertifikat

Font asli sertifikat dimasukkan lewat TCPDF `addTTFfont`, bukan dibaca langsung sebagai TTF. Letakkan berkas `.ttf` di `assets/fonts`, lalu jalankan:

```bash
php artisan certificates:import-fonts --download   # unduh Tinos, PinyonScript, Poppins (OFL)
php artisan certificates:import-fonts              # impor dari assets/fonts
```

Hasilnya berupa definisi `.php`/`.z` di `storage/app/fonts` (tidak masuk git, jadi jalankan ulang setelah clone baru). Nama keluarga mengikuti aturan TCPDF: `Tinos-Regular.ttf` menjadi `tinos`, `Tinos-Bold.ttf` menjadi `tinosb`, `PinyonScript-Regular.ttf` menjadi `pinyonscript`, `Poppins-Regular.ttf` menjadi `poppins`. Nama itulah yang dirujuk `config/layout/*.php`. Selama berkasnya belum ada, renderer kembali ke `times` dan mencatat peringatan.

## Aset desain

Tampilan mengikuti sampel `Syafrizal Devan Pradana.pdf`. Berkas yang dipakai ada di `assets/templates`:

- `background.png` — latar geometris A4 mendatar (1400x990), digambar penuh halaman.
- `logo.png` — pita merah + lingkaran logo XCODE, latar transparan, diletakkan mulai `x 210`, `y 0` dengan ukuran 75 x 62,5 mm.
- `signature.png` — tanda tangan dengan latar transparan; ganti berkas ini bila penanda tangan berganti.

Logo dan tanda tangan diekstrak dari sampel sehingga posisinya sudah sama. Selain itu ada elemen `type: line` untuk garis dekoratif: `garis_nama` (bawah nama peserta) dan `garis_tanda_tangan`, dengan `w` (panjang) dan `tebal` dalam mm. Koordinat `y` teks adalah garis dasar (baseline) hasil kalibrasi terhadap sampel.

## Garis bantu kalibrasi

Untuk mencocokkan latar asli dengan teks, cetak sampel dengan garis bantu 10 mm dan penanda koordinat setiap elemen:

```bash
php artisan certificates:make-samples --guides --output=storage/app/samples
```

Setiap elemen diberi tanda silang biru di titik jangkar (`x`, `y`), label nama dan koordinatnya, serta kotak hijau untuk elemen gambar.

## Pratinjau di browser

Jalankan server lalu buka `http://127.0.0.1:8000/pratinjau`:

```bash
php artisan serve
```

Halaman ini menampilkan PDF langsung di dalam iframe dengan data contoh. Pilihan yang tersedia: jenis layout (`seminar`/`bootcamp`), peserta contoh, mode (`terbit`/`pratinjau`/`dibatalkan`), dan garis bantu kalibrasi.

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
