<?php

declare(strict_types=1);

/*
 * Layout sertifikat seminar/webinar.
 * Versi 1.3: pita mengikuti penerbit (XCODE/SiberExpert), nama lebih besar,
 * garis nama lebih tipis, tanda tangan dinaikkan, nomor/tanggal diperbesar.
 * Versi 1.2: pita logo, garis bawah nama, dan garis tanda tangan diambil dari
 * sampel asli "Syafrizal Devan Pradana.pdf". Versi 1.1: koordinat diukur dari
 * sampel itu (halaman 297,13 x 210,08 mm).
 * QR digambar dari elemen 'qr'; tampil atau tidaknya ditentukan data event `pakai_qr`,
 * bukan dengan menghapus elemen, agar posisinya tetap satu sumber.
 * Setiap perubahan koordinat wajib menaikkan "versi" dan mengarsipkan berkas lama.
 */
return [
    'versi' => '1.3',
    'background' => 'assets/templates/background.png',
    'font' => [
        'serif' => 'tinos',
        'serif_bold' => 'tinosb',
        // Font script & sans berasal dari `certificates:import-fonts`; bila belum diimpor
        // di mesin baru, renderer mundur ke 'fallback' dan mencatat peringatan.
        'script' => 'pinyonscript',
        'sans' => 'poppins',
        'fallback' => 'times',
    ],
    'page' => ['size' => 'A4', 'orientation' => 'L'],
    'elements' => [
        // Elemen gambar digambar lebih dulu agar teks tetap di atasnya.
        'tanda_tangan' => [
            'type' => 'image',
            'path' => 'assets/templates/signature.png',
            'x' => 234.12,
            'y' => 156.01,
            'w' => 50.55,
            'h' => 38.38,
            'optional' => true,
        ],
        // Pita mengikuti penerbit; padding putih di kiri pita dibuat transparan
        // agar ornamen abu-abu latar menyambung tanpa celah.
        'logo' => [
            'type' => 'image',
            'brand' => 'logo',
            'path' => 'assets/templates/logo-xcode.png',
            'x' => 210,
            'y' => 0,
            'w' => 75,
            'h' => 62.5,
            'optional' => true,
        ],
        'garis_nama' => [
            'type' => 'line',
            'x' => 45.96,
            'y' => 157.07,
            'w' => 148.7,
            'tebal' => 0.42,
        ],
        'garis_tanda_tangan' => [
            'type' => 'line',
            'x' => 230.25,
            'y' => 189.2,
            'w' => 57.11,
            'tebal' => 0.53,
        ],
        'judul_besar' => [
            'type' => 'text',
            'text' => 'SERTIFIKAT SEMINAR',
            'x' => 9.21,
            'y' => 36.30,
            'align' => 'L',
            'size' => 41,
        ],
        'judul_kegiatan' => [
            'type' => 'fit',
            'field' => 'judul_cetak',
            'x' => 9.21,
            'y' => 47.07,
            'align' => 'L',
            'size' => 18,
            'min_size' => 12,
            'max_w' => 210,
            'max_lines' => 2,
            'line_h' => 7.5,
        ],
        'domain' => [
            'type' => 'fit',
            'brand' => 'domain',
            'text' => 'xcode.or.id',
            'x' => 240.88,
            'y' => 71.13,
            'align' => 'L',
            'size' => 23,
            'min_size' => 14,
            'max_w' => 54,
        ],
        'nomor' => [
            'type' => 'fit',
            'field' => 'nomor_lengkap',
            'x' => 258.9,
            'y' => 77.53,
            'align' => 'C',
            'size' => 13,
            'min_size' => 9,
            'max_w' => 58,
        ],
        'script_sertifikat' => [
            'type' => 'fit',
            'text' => 'Sertifikat',
            'x' => 33.28,
            'y' => 109.14,
            'align' => 'L',
            'font' => 'script',
            'size' => 135.9,
            'min_size' => 60,
            'max_w' => 156,
        ],
        'diberikan_kepada' => [
            'type' => 'text',
            'text' => 'diberikan kepada',
            'x' => 117.99,
            'y' => 122.70,
            'align' => 'L',
            'font' => 'sans',
            'size' => 19.85,
        ],
        'url_verifikasi' => [
            'type' => 'text',
            'text' => 'xcode.co.id/cekkodesertifikat',
            'x' => 258.9,
            'y' => 128.44,
            'align' => 'C',
            'size' => 9,
        ],
        'qr' => [
            'type' => 'qr',
            'x' => 240.3,
            'y' => 87.0,
            'size' => 36.5,
        ],
        'nama' => [
            'type' => 'fit',
            'field' => 'nama_peserta',
            'x' => 120,
            'y' => 151.62,
            'align' => 'C',
            'size' => 35,
            'min_size' => 20,
            'max_w' => 148,
            'max_lines' => 2,
            'line_h' => 10,
        ],
        'tanggal' => [
            'type' => 'text',
            'field' => 'tanggal_kota',
            'x' => 258.9,
            'y' => 159.61,
            'align' => 'C',
            'size' => 16,
        ],
        // Ganti nama dan jabatan di sini bila penanggung jawab berganti.
        'penanda_tangan' => [
            'type' => 'text',
            'text' => 'Cynthia',
            'x' => 258.9,
            'y' => 194.8,
            'align' => 'C',
            'font' => 'serif_bold',
            'size' => 15,
        ],
        'jabatan_penanda_tangan' => [
            'type' => 'text',
            'text' => 'Direktur',
            'x' => 258.9,
            'y' => 200.0,
            'align' => 'C',
            'size' => 13,
        ],
    ],
];
