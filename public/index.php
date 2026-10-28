<?php

declare(strict_types=1);

// Server bawaan PHP (composer serve): sajikan file statis apa adanya
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

(new App\Core\App(BASE_PATH))->run();
