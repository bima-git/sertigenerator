<?php

declare(strict_types=1);

/*
 * Layout sertifikat bootcamp.
 * Versi 1.5: pita XCODE di-auto-crop, nama lebih besar, garis nama lebih tipis,
 * garis tanda tangan diturunkan, dan tanggal diperbesar.
 * Versi 1.4: nama penanda tangan memakai serif bold seperti sampel "Taufik
 * Hidayat.pdf" (sebelumnya sans). Versi 1.3: pita logo, garis bawah nama, dan garis tanda tangan mengikuti
 * template seminar "Syafrizal Devan Pradana.pdf". Versi 1.2: paragraf memecah
 * baris secara greedy seperti sampel "Taufik Hidayat.pdf", tidak lagi
 * menyeimbangkan panjang baris. Versi 1.1: struktur isi dari sampel itu,
 * elemen tetap mengikuti template seminar.
 * Bootcamp tidak mencetak nomor, URL, dan QR (PRD PD-08); sampel asli juga tidak.
 * Setiap perubahan koordinat wajib menaikkan "versi" dan mengarsipkan berkas lama.
 */
return [
    'versi' => '1.5',
    'background' => 'assets/templates/background.png',
    'font' => [
        'serif' => 'tinos',
        'serif_bold' => 'tinosb',
        'script' => 'pinyonscript',
        'sans' => 'poppins',
        'fallback' => 'times',
    ],
    'page' => ['size' => 'A4', 'orientation' => 'L'],
    'elements' => [
        'tanda_tangan' => [
            'type' => 'image',
            'path' => 'assets/templates/signature.png',
            'x' => 234.12,
            'y' => 156.01,
            'w' => 50.55,
            'h' => 38.38,
            'optional' => true,
        ],
        // Pita XCODE sudah di-auto-crop (tanpa margin putih kiri/kanan).
        'logo' => [
            'type' => 'image',
            'path' => 'assets/templates/logo-xcode.png',
            'x' => 235.89,
            'y' => 0,
            'w' => 46.51,
            'h' => 62.5,
            'optional' => true,
        ],
        'garis_nama' => [
            'type' => 'line',
            'x' => 45.96,
            'y' => 150.93,
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
            'text' => 'SERTIFIKAT PENCAPAIAN',
            'x' => 9.21,
            'y' => 36.30,
            'align' => 'L',
            'size' => 41,
        ],
        'subjudul_1' => [
            'type' => 'fit',
            'template' => 'Ujian {ujian}',
            'x' => 9.21,
            'y' => 47.98,
            'align' => 'L',
            'size' => 22,
            'min_size' => 14,
            'max_w' => 210,
        ],
        'subjudul_2' => [
            'type' => 'fit',
            'field' => 'bootcamp',
            'x' => 9.21,
            'y' => 55.92,
            'align' => 'L',
            'size' => 22,
            'min_size' => 14,
            'max_w' => 212,
        ],
        'domain' => [
            'type' => 'text',
            'text' => 'xcode.or.id',
            'x' => 240.88,
            'y' => 71.13,
            'align' => 'L',
            'size' => 23,
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
        'nama' => [
            'type' => 'fit',
            'field' => 'nama_peserta',
            'x' => 120,
            'y' => 145.48,
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
        'paragraf' => [
            'type' => 'fit',
            'template' => 'Peserta dinyatakan telah lulus {ujian} pada {bootcamp}.',
            'x' => 120.4,
            'y' => 165.46,
            'align' => 'C',
            'size' => 15,
            'min_size' => 11,
            'max_w' => 137,
            'max_lines' => 2,
            'line_h' => 5.29,
            'balance' => false,
        ],
        'penanda_tangan' => [
            'type' => 'text',
            'text' => 'Cantika',
            'x' => 258.9,
            'y' => 194.8,
            'align' => 'C',
            'font' => 'serif_bold',
            'size' => 15,
        ],
        'jabatan_penanda_tangan' => [
            'type' => 'text',
            'text' => 'Direktur Operasional',
            'x' => 258.9,
            'y' => 200.0,
            'align' => 'C',
            'size' => 13,
        ],
    ],
];
