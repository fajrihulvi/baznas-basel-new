<?php
// Uji penghitung "dilihat" berita. Pemakaian: php tests/http/uji_dilihat.php http://localhost:8002
// Memakai berita uji bersalug "uji-dilihat-…" yang dihapus di akhir; data berita asli tidak disentuh.
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://localhost:8002', '/');
$proyek = dirname(__DIR__, 2);
$tmp = dirname(__DIR__) . '/tmp';
@mkdir($tmp);
$host = (string) parse_url($base, PHP_URL_HOST);
$asal = "http://$host:" . parse_url($base, PHP_URL_PORT);
$pdo = new PDO('mysql:host=127.0.0.1;dbname=baznas_basel;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$hapusRl = function () use ($proyek): void { foreach (glob("$proyek/storage/cache/rl-*.json") ?: [] as $f) unlink($f); };
$PASS = 'Uji-Dilihat-' . bin2hex(random_bytes(3));
$bersihkan = function () use ($pdo): void {
    $pdo->exec("DELETE FROM post WHERE slug LIKE 'uji-dilihat-%'"); // post_kunjungan ikut terhapus (CASCADE)
    $pdo->exec("DELETE FROM user WHERE username = 'uji_dl_admin'");
};
$bersihkan();
$hapusRl();
$kunjunganAwal = (int) $pdo->query('SELECT COUNT(*) FROM post_kunjungan')->fetchColumn();

$ins = $pdo->prepare("INSERT INTO post (jenis, judul, slug, konten, tgl, publish, views, updated_at) VALUES ('berita', ?, ?, '<p>Isi uji.</p>', '2026-01-15', ?, ?, '2026-01-20 10:00:00')");
$ins->execute(['Berita uji dilihat', 'uji-dilihat-a', 'Y', 5]);
$idA = (int) $pdo->lastInsertId();
$ins->execute(['Berita uji baru', 'uji-dilihat-nol', 'Y', 0]);
$idNol = (int) $pdo->lastInsertId();
$ins->execute(['Berita uji draf', 'uji-dilihat-draf', 'N', 3]);
$idDraf = (int) $pdo->lastInsertId();
$pdo->prepare("INSERT INTO user (username, password, f_name, l_name, level, status) VALUES ('uji_dl_admin', ?, 'Admin', 'Uji', 1, '1')")->execute([password_hash($PASS, PASSWORD_BCRYPT)]);

const CHROME = 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36';

function req(string $method, string $url, array $header = [], ?string $jar = null, array $data = []): array
{
    $ch = curl_init($url);
    $opt = [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => $header, CURLOPT_USERAGENT => ''];
    if ($jar !== null) { $opt[CURLOPT_COOKIEJAR] = $jar; $opt[CURLOPT_COOKIEFILE] = $jar; }
    if ($method === 'POST') { $opt[CURLOPT_POST] = true; $opt[CURLOPT_POSTFIELDS] = $data === [] ? '' : http_build_query($data); }
    if ($method === 'HEAD') { $opt[CURLOPT_NOBODY] = true; }
    curl_setopt_array($ch, $opt);
    $raw = (string) curl_exec($ch);
    $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    return ['kode' => curl_getinfo($ch, CURLINFO_RESPONSE_CODE), 'head' => substr($raw, 0, $hs), 'body' => substr($raw, $hs)];
}
$gagal = 0;
$cek = function (string $label, bool $ok, string $info = '') use (&$gagal): void {
    if (!$ok) $gagal++;
    printf("%s %-74s %s\n", $ok ? 'OK  ' : 'GAGAL', $label, $info);
};
$views = fn (int $id) => (int) $pdo->query("SELECT views FROM post WHERE id = $id")->fetchColumn();
$ubah = fn (int $id) => (string) $pdo->query("SELECT updated_at FROM post WHERE id = $id")->fetchColumn();
$sinyal = fn (string $slug, string $ua = CHROME, array $tambah = [], ?string $jar = null) => req('POST', "$base/berita/$slug/dilihat", ["Origin: $asal", "User-Agent: $ua", 'Sec-Fetch-Site: same-origin', ...$tambah], $jar);
$jA = "$tmp/dl-admin.txt"; @unlink($jA);

try {
    echo "== Membuka halaman tidak menghitung\n";
    $h = req('GET', "$base/berita/uji-dilihat-a", ['User-Agent: ' . CHROME]);
    $t = preg_replace('/\s+/u', ' ', strip_tags($h['body']));
    $cek('angka dari server tampil: "Dilihat 5 orang"', $h['kode'] === 200 && str_contains($t, 'Dilihat 5 orang'));
    $cek('halaman memuat dilihat.js & alamat sinyal', str_contains($h['body'], 'js/dilihat.js') && str_contains($h['body'], 'data-url="' . $base . '/berita/uji-dilihat-a/dilihat"'));
    $cek('halaman berita tanpa cookie sesi', stripos($h['head'], 'set-cookie') === false);
    req('GET', "$base/berita/uji-dilihat-a", ['User-Agent: ' . CHROME]);
    req('HEAD', "$base/berita/uji-dilihat-a", ['User-Agent: ' . CHROME]);
    req('GET', "$base/berita/uji-dilihat-a", ['User-Agent: Googlebot/2.1']);
    $cek('GET berulang, HEAD, & Googlebot tidak menambah', $views($idA) === 5);
    $cek('berita belum pernah dilihat: penghitung disembunyikan (hidden)', (bool) preg_match('/<span data-dilihat[^>]*hidden>/', req('GET', "$base/berita/uji-dilihat-nol")['body']));
    $n = req('GET', "$base/berita/uji-dilihat-a");
    $cek('berita sudah dilihat: penghitung tidak disembunyikan', !preg_match('/<span data-dilihat[^>]*hidden>/', $n['body']));

    echo "\n== Asal permintaan\n";
    $r = req('POST', "$base/berita/uji-dilihat-a/dilihat", ['User-Agent: ' . CHROME]);
    $cek('tanpa Origin → 403', $r['kode'] === 403, (string) $r['kode']);
    $r = req('POST', "$base/berita/uji-dilihat-a/dilihat", ['Origin: https://situs-lain.test', 'User-Agent: ' . CHROME]);
    $cek('Origin situs lain → 403', $r['kode'] === 403, (string) $r['kode']);
    $r = req('POST', "$base/berita/uji-dilihat-a/dilihat", ["Origin: $asal", 'User-Agent: ' . CHROME, 'Sec-Fetch-Site: cross-site']);
    $cek('Sec-Fetch-Site: cross-site → 403', $r['kode'] === 403, (string) $r['kode']);
    $cek('semua ditolak: views tetap', $views($idA) === 5);

    echo "\n== Menghitung\n";
    $r = $sinyal('uji-dilihat-a');
    $cek('sinyal sah → 200 {"dilihat":6}', $r['kode'] === 200 && json_decode($r['body'], true) === ['dilihat' => 6], $r['body']);
    $cek('sinyal tanpa cookie sesi & tanpa token CSRF', stripos($r['head'], 'set-cookie') === false);
    $cek('updated_at tidak berubah (dateModified & sitemap tetap benar)', $ubah($idA) === '2026-01-20 10:00:00', $ubah($idA));
    $r = $sinyal('uji-dilihat-a');
    $cek('orang yang sama hari ini → tidak bertambah', $r['kode'] === 200 && json_decode($r['body'], true) === ['dilihat' => 6] && $views($idA) === 6, $r['body']);
    $r = $sinyal('uji-dilihat-a', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Safari/604.1');
    $cek('perangkat lain → bertambah', json_decode($r['body'], true) === ['dilihat' => 7] && $views($idA) === 7, $r['body']);
    $r = $sinyal('uji-dilihat-nol');
    $cek('sidik terpisah per berita: orang yang sama di berita lain dihitung', json_decode($r['body'], true) === ['dilihat' => 1], $r['body']);

    $baris = $pdo->query("SELECT * FROM post_kunjungan WHERE id_post = $idA")->fetchAll();
    $cek('tersimpan hanya sidik hex 32 karakter & tanggal (tanpa IP/browser)', count($baris) === 2 && array_keys($baris[0]) === ['id_post', 'sidik', 'tgl'] && preg_match('/^[a-f0-9]{32}$/', $baris[0]['sidik']) === 1);

    echo "\n== Bot & data tidak valid\n";
    foreach (['Googlebot' => 'Mozilla/5.0 (compatible; Googlebot/2.1)', 'Chrome headless' => 'Mozilla/5.0 HeadlessChrome/130.0', 'Lighthouse' => 'Mozilla/5.0 Chrome-Lighthouse', 'curl' => 'curl/8.0'] as $nama => $ua) {
        $r = $sinyal('uji-dilihat-a', $ua);
        $cek("$nama → 204, tidak dihitung", $r['kode'] === 204 && $views($idA) === 7, (string) $r['kode']);
    }
    $r = req('POST', "$base/berita/uji-dilihat-a/dilihat", ["Origin: $asal"]);
    $cek('tanpa User-Agent → 204, tidak dihitung', $r['kode'] === 204 && $views($idA) === 7, (string) $r['kode']);
    $cek('berita draf → 404, tidak dihitung', $sinyal('uji-dilihat-draf')['kode'] === 404 && $views($idDraf) === 3);
    $cek('berita tidak ada → 404', $sinyal('uji-dilihat-tidak-ada')['kode'] === 404);
    $cek('GET alamat sinyal → 405 (tidak bisa dipicu tautan/crawler)', req('GET', "$base/berita/uji-dilihat-a/dilihat")['kode'] === 405);

    echo "\n== Petugas & laju\n";
    $f = req('GET', "$base/admin/masuk", [], $jA);
    preg_match('/name="_token" value="([a-f0-9]{64})"/', $f['body'], $m);
    req('POST', "$base/admin/masuk", [], $jA, ['_token' => $m[1] ?? '', 'username' => 'uji_dl_admin', 'password' => $PASS]);
    $r = $sinyal('uji-dilihat-a', 'Mozilla/5.0 (Windows NT 10.0) Chrome/130.0 petugas', [], $jA);
    $cek('petugas yang masuk admin tidak dihitung', $r['kode'] === 200 && $views($idA) === 7, $r['body']);
    $l = req('GET', "$base/admin/post?q=" . rawurlencode('Berita uji dilihat'), [], $jA);
    $cek('daftar admin Kabar: kolom "Dilihat" berisi angka', str_contains($l['body'], '>Dilihat<') && (bool) preg_match('#tabular-nums">7</span>#', $l['body']));

    $hapusRl();
    $kode = [];
    for ($i = 1; $i <= 121; $i++) {
        $kode[] = $sinyal('uji-dilihat-nol', "Mozilla/5.0 Chrome/130.0 uji-$i")['kode'];
    }
    $cek('lebih dari 120 sinyal per jam dari satu IP → 429', $kode[119] === 200 && $kode[120] === 429, $kode[119] . ' / ' . $kode[120]);
    $hapusRl();

    echo "\n== Pembersihan\n";
    $pdo->prepare('INSERT INTO post_kunjungan (id_post, sidik, tgl) VALUES (?, ?, ?)')->execute([$idA, str_repeat('a', 32), date('Y-m-d', strtotime('-3 days'))]);
    $hasil = (string) shell_exec('php ' . escapeshellarg("$proyek/database/scripts/rawat.php") . ' --coba 2>&1');
    $cek('rawat.php mengenali sidik hari sebelumnya', str_contains($hasil, 'sidik kunjungan hari sebelumnya'), trim($hasil) === '' ? 'kosong' : '');
    $cek('rawat.php --coba tidak menghapus', (int) $pdo->query("SELECT COUNT(*) FROM post_kunjungan WHERE tgl < CURDATE() AND id_post = $idA")->fetchColumn() === 1);
} finally {
    $bersihkan();
    $hapusRl();
    @unlink($jA);
}
$cek('data kembali seperti semula', (int) $pdo->query('SELECT COUNT(*) FROM post_kunjungan')->fetchColumn() === $kunjunganAwal);
printf("\n%s — %d gagal\n", $gagal === 0 ? 'SEMUA LULUS' : 'ADA YANG GAGAL', $gagal);
exit($gagal === 0 ? 0 : 1);
