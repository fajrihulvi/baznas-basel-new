<?php
// Uji semua route §6 + semua URL lama di tabel redirect. Pemakaian: php uji_route.php http://localhost:8002
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://localhost:8002', '/');
$pdo = new PDO('mysql:host=127.0.0.1;dbname=baznas_basel;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$slugPost = $pdo->query("SELECT slug FROM post WHERE publish='Y' ORDER BY tgl DESC LIMIT 1")->fetchColumn();
$fotoLama = $pdo->query("SELECT foto FROM berita LIMIT 1")->fetchColumn();

function ambil(string $url, bool $ikuti = false): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => $ikuti, CURLOPT_MAXREDIRS => 5, CURLOPT_TIMEOUT => 20, CURLOPT_HEADER => false]);
    $body = (string) curl_exec($ch);
    $info = [curl_getinfo($ch, CURLINFO_RESPONSE_CODE), (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL), (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL), $body, curl_getinfo($ch, CURLINFO_REDIRECT_COUNT)];
    curl_close($ch);
    return $info;
}

$gagal = 0;
$cek = function (string $label, bool $ok, string $info = '') use (&$gagal): void {
    if (!$ok) { $gagal++; }
    printf("%s %-58s %s\n", $ok ? 'OK  ' : 'GAGAL', $label, $info);
};

echo "== Route §6 (Fase 5)\n";
$halaman = [
    '/' => 200, '/berita' => 200, '/berita?jenis=berita' => 200, '/berita?jenis=artikel&hal=2' => 200, '/berita?jenis=xss<script>' => 200,
    "/berita/$slugPost" => 200, '/berita/tidak-ada-slug' => 404,
    '/program' => 200, '/program/basel-sehat' => 200, '/program/basel-peduli?sub=tanggap-bencana' => 200, '/program/tidak-ada' => 404,
    '/zakat' => 200, '/zakat/zakat-fitrah' => 200, '/zakat/tidak-ada' => 404,
    '/rekening' => 200, '/regulasi' => 200, '/regulasi?kategori=2' => 200, '/regulasi?kategori=8' => 200, '/regulasi?kategori=abc' => 200,
    '/laporan' => 200, '/video' => 200, '/galeri' => 200, '/galeri?jenis=poster' => 200,
    '/tentang' => 302, '/tentang/profil' => 200, '/tentang/visi-misi' => 200, '/halaman/kurban' => 200,
    '/halaman/profil' => 301, '/tentang/kurban' => 301, '/halaman/faq' => 404,
    '/amil' => 200, '/testimoni' => 200, '/cari' => 200, '/cari?q=zakat' => 200, '/cari?q=ab' => 200, '/cari?q=%25%25' => 200,
    '/sitemap.xml' => 200, '/robots.txt' => 200, '/tidak-ada' => 404, '/berita?hal=999' => 200,
];
foreach ($halaman as $path => $harap) {
    [$kode, $ke, , $body] = ambil($base . $path);
    $err = preg_match('/(Fatal error|Warning:|Notice:|Deprecated:|Uncaught)/', $body, $m) ? ' PHP: ' . $m[1] : '';
    $cek($path, $kode === $harap && $err === '', "$kode" . ($ke ? " → " . str_replace($base, '', $ke) : '') . $err);
}

echo "\n== URL lama (tabel redirect) → tujuan akhir\n";
$fase6 = ['/bayar-zakat', '/kalkulator-zakat', '/konfirmasi'];
$hitung = ['ok' => 0, 'fase6' => 0];
$lama = $pdo->query('SELECT dari, ke FROM redirect ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$lama[] = ['dari' => '/home/pembayaran/keterangan?kode=AB12CD', 'ke' => '/bayar-zakat/AB12CD'];
$lama[] = ['dari' => '/home/pembayaran/zakat?nominal=250000', 'ke' => '/bayar-zakat?nominal=250000'];
$lama[] = ['dari' => "/file/berita/$fotoLama", 'ke' => "/uploads/post/$fotoLama"];
foreach ($lama as $r) {
    [$kode1, $ke1] = ambil($base . $r['dari']);
    [$kodeAkhir, , $akhir] = ambil($base . $r['dari'], true);
    $pathAkhir = str_replace($base, '', $akhir);
    $tujuanCocok = $ke1 === '' ? false : str_replace($base, '', $ke1) === $r['ke'];
    $isFase6 = in_array(strtok($r['ke'], '?#'), $fase6, true) || str_starts_with($r['ke'], '/bayar-zakat/');
    if ($kode1 === 301 && $tujuanCocok && $kodeAkhir === 200) {
        $hitung['ok']++;
    } elseif ($kode1 === 301 && $tujuanCocok && $isFase6 && $kodeAkhir === 404) {
        $hitung['fase6']++;
    } else {
        $cek($r['dari'], false, "$kode1 → " . str_replace($base, '', $ke1) . " (harap {$r['ke']}) → akhir $kodeAkhir");
    }
}
printf("OK   %d URL lama → 301 → halaman 200\n", $hitung['ok']);
printf("INFO %d URL lama → 301 → route Fase 6 (bayar/kalkulator/konfirmasi), belum dibuat\n", $hitung['fase6']);

echo "\n== Isi penting\n";
[, , , $sm] = ambil("$base/sitemap.xml");
$cek('sitemap.xml valid & berisi post', simplexml_load_string($sm) !== false && substr_count($sm, '<url>') > 60, substr_count($sm, '<url>') . ' url');
[, , , $rb] = ambil("$base/robots.txt");
$cek('robots.txt (non-produksi → Disallow: /)', str_contains($rb, 'Disallow: /'), trim(str_replace("\n", ' | ', $rb)));
[, , , $d] = ambil("$base/berita/$slugPost");
$cek('detail: JSON-LD NewsArticle', str_contains($d, '"@type":"NewsArticle"'));
$cek('detail: og:type article + canonical', str_contains($d, 'og:type" content="article"') && str_contains($d, 'rel="canonical" href="' . $base . '/berita/' . $slugPost . '"'));
$cek('detail: BreadcrumbList', str_contains($d, '"@type":"BreadcrumbList"'));
[, , , $x] = ambil("$base/berita?jenis=" . rawurlencode('"><script>alert(1)</script>'));
$cek('filter tidak memantulkan input mentah', !str_contains($x, '<script>alert(1)'));
[, , , $c] = ambil("$base/cari?q=" . rawurlencode('<img src=x onerror=alert(1)>'));
$cek('pencarian meng-escape kata kunci', !str_contains($c, '<img src=x onerror') && str_contains($c, '&lt;img'));
$cek('pencarian noindex', str_contains($c, 'noindex'));
[, , , $z] = ambil("$base/zakat/zakat-fitrah");
$cek('zakat: konten tersanitasi (tanpa style=)', !preg_match('/<(p|span|div|td)[^>]* style=/i', $z));
$cek('zakat: tautan bayar membawa jenis', str_contains($z, '/bayar-zakat?jenis=1'));
[, , , $p] = ambil("$base/program/basel-sehat");
$cek('pilar Sehat berisi kegiatan', substr_count($p, '<article') >= 1, substr_count($p, '<article') . ' kartu');
$jumlahHit = (int) $pdo->query('SELECT SUM(hit) FROM redirect')->fetchColumn();
$cek('hit redirect tercatat', $jumlahHit > 0, "$jumlahHit hit");

echo $gagal === 0 ? "\nSEMUA LULUS\n" : "\n$gagal GAGAL\n";
