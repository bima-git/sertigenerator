<?php

declare(strict_types=1);

/*
 * Pengaturan sertifikat yang tidak termasuk koordinat layout.
 * Warna dan ukuran tanda air masih perkiraan [KALIBRASI]; teksnya bisa diganti bebas.
 */
return [
    'text_color' => [17, 17, 17],

    // Tanda air tile "XCODE" di belakang konten, berlaku untuk semua mode.
    'brand_watermark' => [
        'text' => 'XCODE',
        'color' => [120, 120, 120],
        'alpha' => 0.06,
        'size' => 30,
        'angle' => 30,
        'gap_x' => 58,
        'gap_y' => 40,
    ],

    'qr' => [
        // Satu alamat untuk semua sertifikat. Tampil atau tidaknya QR diatur lewat
        // data event (pakai_qr), bukan dengan mengubah alamat ini.
        'url' => 'https://xcode.co.id/cekkodesertifikat',
    ],

    // 'text' adalah teks bawaan per mode dan boleh diganti kalimat apa pun. Teks dari
    // form dikirim lewat parameter render (PdfRenderer::render(..., $watermark)) dan
    // menimpa nilai ini. Mode terbit tanpa teks bebas tidak diberi tanda air; bila teks
    // bebas diisi pada mode terbit, gayanya mengikuti blok 'pratinjau'.
    'watermark' => [
        'pratinjau' => [
            'text' => 'PRATINJAU',
            'color' => [120, 120, 120],
            'alpha' => 0.25,
            'size' => 90,
            'angle' => 45,
        ],
        'dibatalkan' => [
            'text' => 'DIBATALKAN',
            'color' => [190, 30, 30],
            'alpha' => 0.25,
            'size' => 90,
            'angle' => 45,
        ],
    ],
];
