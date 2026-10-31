<?php

declare(strict_types=1);

// Server bawaan PHP (composer serve): sajikan file statis apa adanya
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

// Dua susunan yang didukung:
//   1. Standar  : proyek/public/index.php        → kode di proyek/
//   2. Terpisah : /home/USER/public_html/index.php → kode di /home/USER/baznas-basel/ (di luar jangkauan web)
define('PUBLIC_PATH', __DIR__);
define('BASE_PATH', is_file(dirname(__DIR__) . '/vendor/autoload.php') ? dirname(__DIR__) : dirname(__DIR__) . '/baznas-basel');

require BASE_PATH . '/vendor/autoload.php';

(new App\Core\App(BASE_PATH))->run();
