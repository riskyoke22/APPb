<?php

declare(strict_types=1);

return [
    'app_name' => getenv('APP_NAME') ?: 'Buku Tamu BDI Surabaya',
    'app_env' => getenv('APP_ENV') ?: 'production',
    'timezone' => getenv('TZ') ?: 'Asia/Jakarta',
    'trusted_proxies' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) (getenv('TRUSTED_PROXIES') ?: ''))
    ))),
    'db' => [
        'host' => getenv('DB_HOST') ?: 'db',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'buku_tamu_bdi',
        'user' => getenv('DB_USER') ?: 'bdi_user',
        'pass' => getenv('DB_PASS') ?: 'BdiSurabaya2026!',
        'charset' => 'utf8mb4',
    ],
    'upload_dir' => dirname(__DIR__, 2) . '/storage/uploads/photos',
    'tujuan_list' => [
        'Kepala Balai',
        'Bagian Tata Usaha',
        'Subbagian Umum & Kepegawaian',
        'Unit Diklat & Pelatihan',
        'Unit LSP / Sertifikasi Kompetensi',
        'Unit Inkubator Bisnis & Industri',
        'Kunjungan Kerja / Studi Banding',
        'Tamu Umum / Lainnya',
    ],
];
