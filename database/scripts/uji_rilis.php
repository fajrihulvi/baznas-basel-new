<?php

/**
 * Uji asap setelah deploy — jalankan dari komputer mana pun (tidak butuh database):
 *   php database/scripts/uji_rilis.php https://baznasbasel.id
 *
 * Memeriksa halaman utama, header keamanan, kompresi & cache, file rahasia tidak bisa diunduh,
 * pengalihan http→https & URL lama, sitemap/robots, halaman admin. Kode keluar 1 bila ada yang gagal.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$base = rtrim((string) ($argv[1] ?? ''), '/');
if (!preg_match('#^https?://#', $base)) {
    fwrite(STDERR, "Pemakaian: php database/scripts/uji_rilis.php https://baznasbasel.id\n");
    exit(2);
}
$https = str_starts_with($base, 'https://');

/** @return array{kode: int, head: string, body: string, lokasi: string, waktu: float} */
function ambil(string $url, string $metode = 'GET'): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 30, CURLOPT_ENCODING => '',
        CURLOPT_CUSTOMREQUEST => $metode, CURLOPT_NOBODY => $metode === 'HEAD',
        CURLOPT_HTTPHEADER => ['Accept-Encoding: gzip', 'User-Agent: baznas-uji-rilis'],
    ]);
    $raw = (string) curl_exec($ch);
    $hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $r = [
        'kode'  => (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
        'head'  => substr($raw, 0, $hs),
        'body'  => substr($raw, $hs),
        'waktu' => (float) curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME),
    ];
    $r['lokasi'] = preg_match('/^location:\s*(\S+)/mi', $r['head'], $m) ? $m[1] : '';

    return $r;
}

function header_ada(array $r, string $nama, string $pola = ''): bool
{
    return preg_match('~^' . preg_quote($nama, '~') . ':\s*' . $pola . '~mi', $r['head']) === 1;
}

$gagal = 0;
$peringatan = 0;
$cek = static function (string $label, bool $ok, string $info = '', bool $wajib = true) use (&$gagal, &$peringatan): void {
    if (!$ok) {
        $wajib ? $gagal++ : $peringatan++;
    }
    printf("%s %-62s %s\n", $ok ? 'OK   ' : ($wajib ? 'GAGAL' : 'CEK  '), $label, $info);
};

echo "== Halaman utama\n";
$home = ambil($base . '/');
$cek('GET / → 200', $home['kode'] === 200, (string) $home['kode']);
$cek('waktu respons awal (TTFB) < 0,8 detik', $home['waktu'] < 0.8, number_format($home['waktu'], 2, ',', '') . ' dtk', false);
$cek('HTML dikompres (gzip/br)', header_ada($home, 'Content-Encoding', '(gzip|br)'));
$cek('tanpa pesan error PHP', !preg_match('/(Fatal error|Warning:|Notice:|Deprecated:|Stack trace)/', $home['body']));
$cek('canonical memakai alamat ini', str_contains($home['body'], 'rel="canonical" href="' . $base . '/"'), '', false);
foreach (['X-Content-Type-Options' => 'nosniff', 'X-Frame-Options' => 'SAMEORIGIN', 'Referrer-Policy' => '', 'Content-Security-Policy' => "default-src 'self'", 'Permissions-Policy' => ''] as $h => $pola) {
    $cek("header {$h}", header_ada($home, $h, preg_quote($pola, '~')));
}
$cek('tanpa X-Powered-By (versi PHP tidak diumumkan)', !header_ada($home, 'X-Powered-By'));
if ($https) {
    $cek('Strict-Transport-Security (HSTS)', header_ada($home, 'Strict-Transport-Security', 'max-age=\d{7,}'), '', false);
    $http = ambil('http://' . substr($base, 8) . '/zakat?uji=1');
    $cek('http:// → 301 ke https://', in_array($http['kode'], [301, 308], true) && str_starts_with($http['lokasi'], $base . '/zakat'), $http['kode'] . ' ' . $http['lokasi']);
}
$cek('beranda tanpa cookie sesi (bisa di-cache)', !header_ada($home, 'Set-Cookie'));

echo "\n== Aset statis\n";
if (preg_match('#href="([^"]+/assets/css/app\.css\?v=\d+)"#', $home['body'], $m)) {
    $css = ambil(html_entity_decode($m[1]));
    $cek('app.css → 200, gzip, cache 1 tahun', $css['kode'] === 200 && header_ada($css, 'Content-Encoding', '(gzip|br)') && header_ada($css, 'Cache-Control', '.*max-age=31536000'));
} else {
    $cek('tautan app.css ditemukan di beranda', false);
}
// Semua aset yang dipakai beranda harus terunggah: asset() hanya menambah ?v= bila berkasnya ada di server,
// jadi aset tanpa ?v= = berkas belum diunggah (mis. JS baru yang lupa ikut → form gagal dengan 419).
preg_match_all('#(?:src|href)="([^"]*/assets/[^"]+)"#', $home['body'], $semua);
$asetHilang = [];
foreach (array_unique(array_map('html_entity_decode', $semua[1])) as $aset) {
    if (!str_contains($aset, '?v=') || ambil($aset, 'HEAD')['kode'] !== 200) {
        $asetHilang[] = basename((string) parse_url($aset, PHP_URL_PATH));
    }
}
$cek('semua CSS/JS/gambar /assets di beranda ada di server (' . count(array_unique($semua[1])) . ' berkas)', $asetHilang === [], implode(', ', $asetHilang));
if (preg_match('#src="([^"]+/uploads/[^"]+\.webp)"#', $home['body'], $m)) {
    $img = ambil(html_entity_decode($m[1]), 'HEAD');
    $cek('gambar unggahan → 200, image/webp, cache 1 tahun', $img['kode'] === 200 && header_ada($img, 'Content-Type', 'image/webp') && header_ada($img, 'Cache-Control', '.*max-age=31536000'));
}

echo "\n== File yang tidak boleh bisa dibuka\n";
foreach (['/.env', '/.htaccess', '/.env.production.example', '/composer.json', '/integritas.json', '/app/Core/App.php', '/config/app.php',
          '/storage/logs/app-' . date('Y-m-d') . '.log', '/storage/cache/.gitkeep', '/vendor/autoload.php', '/database/scripts/migrate.php', '/database/migrations/005_user_password.sql',
          '/uploads/.htaccess', '/uploads/post/uji.php', '/index.php/../.env', '/public/.env'] as $p) {
    $r = ambil($base . $p);
    $cek("{$p} → 403/404", in_array($r['kode'], [400, 403, 404], true), (string) $r['kode']);
}

echo "\n== Halaman & pengalihan\n";
foreach (['/zakat', '/berita', '/bayar-zakat', '/kalkulator-zakat', '/rekening', '/laporan'] as $p) {
    $r = ambil($base . $p);
    $cek("GET {$p} → 200", $r['kode'] === 200, (string) $r['kode']);
}
$r = ambil($base . '/halaman-uji-tidak-ada-' . bin2hex(random_bytes(3)));
$cek('alamat tidak ada → 404 (bukan 200/500)', $r['kode'] === 404, (string) $r['kode']);
$r = ambil($base . '/home/pembayaran/keterangan?kode=UJI123');
$cek('URL lama /home/pembayaran/keterangan → 301', $r['kode'] === 301 && str_ends_with($r['lokasi'], '/bayar-zakat/UJI123'), $r['kode'] . ' ' . $r['lokasi']);
$r = ambil($base . '/sitemap.xml');
$cek('sitemap.xml → 200 XML, memakai alamat ini', $r['kode'] === 200 && str_contains($r['body'], '<urlset') && str_contains($r['body'], '<loc>' . $base));
$r = ambil($base . '/robots.txt');
$cek('robots.txt → 200, Disallow /admin, ada Sitemap', $r['kode'] === 200 && str_contains($r['body'], 'Disallow: /admin') && str_contains($r['body'], 'Sitemap:'),
    preg_match('~^Disallow: /\s*$~m', $r['body']) ? 'seluruh situs diblokir — APP_ENV belum production?' : '');
$r = ambil($base . '/admin/masuk');
$cek('/admin/masuk → 200, noindex, no-store', $r['kode'] === 200 && header_ada($r, 'X-Robots-Tag', 'noindex') && header_ada($r, 'Cache-Control', '.*no-store'));
if ($https) {
    $cek('cookie sesi admin: Secure; HttpOnly', header_ada($r, 'Set-Cookie', '.*;\s*secure.*httponly'));
}
$r = ambil($base . '/admin');
$cek('/admin tanpa login → dialihkan ke halaman masuk', in_array($r['kode'], [302, 303], true) && str_contains($r['lokasi'], '/admin/masuk'), $r['kode'] . '');

printf("\n%s — %d gagal, %d perlu dicek\n", $gagal === 0 ? 'LULUS' : 'ADA YANG GAGAL', $gagal, $peringatan);
exit($gagal === 0 ? 0 : 1);
