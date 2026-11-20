<?php

declare(strict_types=1);

/*
 * Pengaturan sertifikat yang tidak termasuk koordinat layout.
 * Warna dan ukuran tanda air masih perkiraan [KALIBRASI]; nanti teksnya menjadi input bebas.
 */
return [
    'text_color' => [17, 17, 17],

    'qr' => [
        // Satu alamat untuk semua sertifikat. Tampil atau tidaknya QR diatur lewat
        // data event (pakai_qr), bukan dengan mengubah alamat ini.
        'url' => 'https://xcode.co.id/cekkodesertifikat',
    ],

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
