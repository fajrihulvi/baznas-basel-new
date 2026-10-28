<?php

declare(strict_types=1);

return [
    'name'     => env('APP_NAME', 'BAZNAS Kabupaten Bangka Selatan'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => (bool) env('APP_DEBUG', false),
    'url'      => rtrim((string) env('APP_URL', 'http://localhost'), '/'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
    'locale'   => 'id',

    // Produksi: alihkan http:// & host lain (mis. www.) ke APP_URL — hanya bila APP_URL https://
    'force_https' => (bool) env('APP_FORCE_HTTPS', false),
    // Strict-Transport-Security max-age (detik); 0 = mati. Nyalakan setelah HTTPS stabil, mis. 31536000.
    'hsts'        => (int) env('APP_HSTS', 0),

    'session' => [
        'name' => env('SESSION_NAME', 'baznas_sid'),
    ],
];
