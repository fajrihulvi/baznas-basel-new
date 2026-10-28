<?php
// Router php -S untuk pengujian: /__uji/* dari folder tests/http; meniru Apache produksi:
// halaman PHP + CSS/JS/SVG/JSON dikompres gzip (mod_deflate), aset diberi cache 1 tahun.
$path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/__uji/')) {
    $file = __DIR__ . '/' . basename($path);
    if (!is_file($file)) {
        http_response_code(404);
        return true;
    }
    header('Content-Type: ' . (str_ends_with($file, '.js') ? 'text/javascript' : 'text/html; charset=utf-8'));
    readfile($file);
    return true;
}

$public = dirname(__DIR__, 2) . '/public';
if ($path !== '/' && is_file($public . $path)) {
    $tipe = [
        'css' => 'text/css; charset=UTF-8', 'js' => 'text/javascript; charset=UTF-8',
        'svg' => 'image/svg+xml', 'webmanifest' => 'application/manifest+json',
    ][strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;
    if ($tipe === null) {
        header('Cache-Control: public, max-age=31536000, immutable');
        return false; // gambar dll. disajikan server bawaan
    }
    header('Content-Type: ' . $tipe);
    header('Cache-Control: public, max-age=31536000, immutable');
    ini_set('zlib.output_compression', '1');
    readfile($public . $path);
    return true;
}

ini_set('zlib.output_compression', '1');

return require $public . '/index.php';
