<?php

declare(strict_types=1);

return [
    'host'     => env('DB_HOST', '127.0.0.1'),
    'port'     => (int) env('DB_PORT', 3306),
    'name'     => env('DB_NAME', 'baznasba_baznas'),
    'username' => env('DB_USER', 'root'),
    'password' => (string) env('DB_PASS', ''),
    'charset'  => 'utf8mb4',
    // Disamakan dengan APP_TIMEZONE agar NOW()/CURRENT_TIMESTAMP sesuai WIB
    'timezone' => '+07:00',
];
