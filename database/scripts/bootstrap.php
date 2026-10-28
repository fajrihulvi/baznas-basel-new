<?php

/**
 * Bootstrap untuk skrip CLI di database/scripts: autoload, .env, config, zona waktu.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__, 2));

require BASE_PATH . '/vendor/autoload.php';

App\Core\Env::load(BASE_PATH . '/.env');
App\Core\Config::load(BASE_PATH . '/config');
date_default_timezone_set((string) config('app.timezone', 'Asia/Jakarta'));

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

/** Nilai opsi --nama=nilai atau true untuk --nama. */
function opsi(string $nama, mixed $default = null): mixed
{
    foreach ($GLOBALS['argv'] ?? [] as $arg) {
        if ($arg === '--' . $nama) {
            return true;
        }
        if (str_starts_with($arg, '--' . $nama . '=')) {
            return substr($arg, strlen($nama) + 3);
        }
    }

    return $default;
}
