<?php

declare(strict_types=1);

/*
 * Pengaturan sertifikat yang tidak termasuk koordinat layout.
 * Warna dan ukuran tanda air masih perkiraan [KALIBRASI] sampai sampel asli diterima.
 */
return [
    'text_color' => [17, 17, 17],

    'qr' => [
        // static: alamat tetap. per_nomor: alamat ditambah ?kode={nomor terenkode URL}.
        'mode' => env('CERTIFICATE_QR_MODE', 'static'),
        'static_url' => 'https://xcode.co.id/cekkodesertifikat',
        'per_nomor_url' => 'https://xcode.co.id/cekkodesertifikat?kode={nomor}',
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
