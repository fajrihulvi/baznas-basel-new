<?php
// Uji Fase 8: tautan rusak, pengalihan, status sistem, HTTPS/HSTS.
// php uji_fase8.php http://localhost:8002 http://localhost:8003   (8003 = mode produksi, APP_URL=https://baznas.test)
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://localhost:8002', '/');
$prod = rtrim($argv[2] ?? 'http://localhost:8003', '/');
$proyek = dirname(__DIR__, 2);
$tmp = dirname(__DIR__) . '/tmp';
@mkdir($tmp);
$pdo = new PDO('mysql:host=127.0.0.1;dbname=baznas_basel;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
foreach (glob("$proyek/storage/cache/rl-*.json") ?: [] as $f) unlink($f);

$USER = 'uji_admin_f8'; $PASS = 'Uji-Admin-Fase8-' . bin2hex(random_bytes(3));
$pdo->prepare('DELETE FROM user WHERE username = ?')->execute([$USER]);
$pdo->prepare("INSERT INTO user (username, password, f_name, l_name, level, status) VALUES (?, ?, 'Admin', 'Uji', 1, '1')")->execute([$USER, password_hash($PASS, PASSWORD_BCRYPT)]);
$jmlRedirect = (int) $pdo->query('SELECT COUNT(*) FROM redirect')->fetchColumn();
$P = '/uji-f8-tidak-ada-' . bin2hex(random_bytes(3));

function req(string $jar, string $method, string $url, array $data = [], array $header = []): array
{
    $ch = curl_init($url);
    $opt = [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => $header];
    if ($method === 'POST') { $opt[CURLOPT_POST] = true; $opt[CURLOPT_POSTFIELDS] = http_build_query($data); }
    curl_setopt_array($ch, $opt);
    $raw = (string) curl_exec($ch);
    $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $r = ['kode' => curl_getinfo($ch, CURLINFO_RESPONSE_CODE), 'head' => substr($raw, 0, $hs), 'body' => substr($raw, $hs)];
    curl_close($ch);
    preg_match('/^location:\s*(\S+)/mi', $r['head'], $m);
    $r['lokasi'] = $m[1] ?? '';
    return $r;
}
function token(string $html): string { preg_match('/name="_token" value="([a-f0-9]{64})"/', $html, $m); return $m[1] ?? ''; }
$gagal = 0;
$cek = function (string $label, bool $ok, string $info = '') use (&$gagal): void {
    if (!$ok) $gagal++;
    printf("%s %-70s %s\n", $ok ? 'OK  ' : 'GAGAL', $label, $info);
};
$baris = fn (string $p) => $pdo->query('SELECT * FROM tautan_rusak WHERE path = ' . $pdo->quote($p))->fetch() ?: null;
$jar = "$tmp/f8.txt"; @unlink($jar);
$anon = "$tmp/f8-anon.txt"; @unlink($anon);

echo "== Pencatatan 404\n";
$r = req($anon, 'GET', $base . $P);
$cek("GET $P → 404", $r['kode'] === 404);
$b = $baris($P);
$cek('tercatat di tautan_rusak (hit 1, referer kosong)', $b !== null && (int) $b['hit'] === 1 && $b['referer'] === null);
req($anon, 'GET', $base . $P . '?utm=x', [], ['Referer: https://www.facebook.com/post/1']);
$b = $baris($P);
$cek('dibuka lagi (dengan query) → hit 2, referer tersimpan', $b !== null && (int) $b['hit'] === 2 && $b['referer'] === 'https://www.facebook.com/post/1');
req($anon, 'GET', $base . '/berita/uji-f8-slug-tidak-ada');
$cek('404 dari controller (/berita/slug-salah) juga tercatat', $baris('/berita/uji-f8-slug-tidak-ada') !== null);
foreach (['/wp-login.php', '/.env', '/uji-f8/.git/config', '/phpmyadmin/index', '/admin/uji-f8-x/1/ubah'] as $p) {
    req($anon, 'GET', $base . $p);
}
$cek('probe pemindai & /admin tidak dicatat', (int) $pdo->query("SELECT COUNT(*) FROM tautan_rusak WHERE path IN ('/wp-login.php','/.env','/uji-f8/.git/config','/phpmyadmin/index','/admin/uji-f8-x/1/ubah')")->fetchColumn() === 0);
req($anon, 'POST', $base . '/uji-f8-post', ['a' => 1]);
$cek('POST tidak dicatat', $baris('/uji-f8-post') === null);
$cek('halaman yang ada (/) tidak dicatat', $baris('/') === null && req($anon, 'GET', $base . '/')['kode'] === 200);

echo "\n== Admin: login\n";
$f = req($jar, 'GET', "$base/admin/masuk");
$r = req($jar, 'POST', "$base/admin/masuk", ['_token' => token($f['body']), 'username' => $USER, 'password' => $PASS]);
$cek('login uji → 303', $r['kode'] === 303);

echo "\n== Admin: Tautan Rusak\n";
$d = req($jar, 'GET', "$base/admin/tautan-rusak");
$cek('daftar 200, ada path & tombol "Buat pengalihan"', $d['kode'] === 200 && str_contains($d['body'], e8($P)) && str_contains($d['body'], 'redirect/baru?dari=' . rawurlencode($P)));
$cek('tidak ada tombol Tambah/Ubah (aksi hapus saja)', !str_contains($d['body'], 'Tambah catatan') && !str_contains($d['body'], '/tautan-rusak/' . $b['id'] . '/ubah'));
$cek('form tambah tautan-rusak → 404', req($jar, 'GET', "$base/admin/tautan-rusak/baru")['kode'] === 404);
$cek('menu memuat Pengalihan URL; Tautan Rusak & Status Sistem disembunyikan', str_contains($d['body'], 'Pengalihan URL')
    && !str_contains($d['body'], 'href="' . $base . '/admin/tautan-rusak"') && !str_contains($d['body'], 'href="' . $base . '/admin/status"'));

echo "\n== Admin: Pengalihan URL\n";
$form = req($jar, 'GET', "$base/admin/redirect/baru?dari=" . rawurlencode($P));
$cek('form baru terisi dari ?dari=', $form['kode'] === 200 && str_contains($form['body'], 'value="' . e8($P) . '"'));
$t = token($form['body']);
$r = req($jar, 'POST', "$base/admin/redirect", ['_token' => $t, 'dari' => '/uji-f8-x', 'ke' => 'javascript:alert(1)']);
$g = req($jar, 'GET', "$base/admin/redirect/baru");
$cek('ke=javascript: ditolak', $r['kode'] === 303 && str_contains($r['lokasi'], '/redirect/baru') && str_contains($g['body'], 'Format Dialihkan ke tidak valid'));
$r = req($jar, 'POST', "$base/admin/redirect", ['_token' => token($g['body']), 'dari' => '/admin/setting', 'ke' => '/']);
$g = req($jar, 'GET', "$base/admin/redirect/baru");
$cek('dari=/admin/... ditolak', str_contains($g['body'], 'tidak bisa dialihkan'));
$r = req($jar, 'POST', "$base/admin/redirect", ['_token' => token($g['body']), 'dari' => '/uji-f8-sama', 'ke' => '/uji-f8-sama/']);
$g = req($jar, 'GET', "$base/admin/redirect/baru");
$cek('tujuan = alamat lama ditolak', str_contains($g['body'], 'tidak boleh sama'));
$lama = $pdo->query("SELECT dari FROM redirect ORDER BY id LIMIT 1")->fetchColumn();
$r = req($jar, 'POST', "$base/admin/redirect", ['_token' => token($g['body']), 'dari' => $lama, 'ke' => '/']);
$g = req($jar, 'GET', "$base/admin/redirect/baru");
$cek('alamat lama duplikat ditolak', str_contains($g['body'], 'sudah punya pengalihan'));
$r = req($jar, 'POST', "$base/admin/redirect", ['_token' => token($g['body']), 'dari' => 'https://baznasbasel.id' . $P . '/?utm=1', 'ke' => '/bayar-zakat']);
$baru = $pdo->query('SELECT * FROM redirect WHERE dari = ' . $pdo->quote($P))->fetch();
$cek('URL lengkap dinormalkan → dari = path, tersimpan', $r['kode'] === 303 && $baru !== false && $baru['ke'] === '/bayar-zakat', $r['lokasi']);
$cek('catatan tautan rusak ikut terhapus', $baris($P) === null);
$r = req($anon, 'GET', $base . $P . '?nominal=50000');
$cek('alamat lama → 301 ke /bayar-zakat (query dibawa)', $r['kode'] === 301 && str_ends_with($r['lokasi'], '/bayar-zakat?nominal=50000'), $r['lokasi']);
$cek('setelah dialihkan, tidak tercatat lagi sebagai 404', $baris($P) === null);
$u = req($jar, 'GET', "$base/admin/redirect/{$baru['id']}/ubah");
$r = req($jar, 'POST', "$base/admin/redirect/{$baru['id']}", ['_token' => token($u['body']), 'dari' => $P, 'ke' => '/zakat']);
$cek('ubah pengalihan sendiri (dari sama) tidak dianggap duplikat', $r['kode'] === 303 && $pdo->query('SELECT ke FROM redirect WHERE id = ' . (int) $baru['id'])->fetchColumn() === '/zakat', $r['lokasi']);
$daftar = req($jar, 'GET', "$base/admin/redirect?q=" . rawurlencode($P));
$cek('daftar pengalihan bisa dicari', $daftar['kode'] === 200 && str_contains($daftar['body'], '1 data'));
$r = req($jar, 'POST', "$base/admin/redirect/{$baru['id']}/hapus", ['_token' => token($daftar['body'])]);
$cek('hapus pengalihan', $r['kode'] === 303 && (int) $pdo->query('SELECT COUNT(*) FROM redirect')->fetchColumn() === $jmlRedirect);
$d = req($jar, 'GET', "$base/admin/tautan-rusak?q=uji-f8-slug");
preg_match('#/admin/tautan-rusak/(\d+)/hapus#', $d['body'], $m);
$r = req($jar, 'POST', "$base/admin/tautan-rusak/" . ($m[1] ?? 0) . '/hapus', ['_token' => token($d['body'])]);
$cek('hapus catatan tautan rusak', $r['kode'] === 303 && $baris('/berita/uji-f8-slug-tidak-ada') === null);

echo "\n== Admin: Status Sistem\n";
$s = req($jar, 'GET', "$base/admin/status");
$cek('200, tanpa error PHP', $s['kode'] === 200 && !preg_match('/(Fatal error|Warning:|Deprecated:|Uncaught)/', $s['body']));
$cek('memuat versi PHP, OPcache, migrasi, folder', str_contains($s['body'], PHP_VERSION) && str_contains($s['body'], 'OPcache') && str_contains($s['body'], 'Semua sudah dijalankan') && str_contains($s['body'], 'storage/bukti'));
$cek('lokal: APP_ENV local → "Perlu dicek"', str_contains($s['body'], 'Perlu dicek'));
$cek('status tanpa login → 303 login', req($anon, 'GET', "$base/admin/status")['kode'] === 303);

echo "\n== Mode produksi (APP_URL https, FORCE_HTTPS, HSTS)\n";
$p0 = "$tmp/f8-prod.txt"; @unlink($p0);
$r = req($p0, 'GET', "$prod/berita?hal=2", [], ['Host: baznas.test']);
$cek('http:// → 301 ke https://baznas.test/berita?hal=2', $r['kode'] === 301 && $r['lokasi'] === 'https://baznas.test/berita?hal=2', $r['lokasi']);
$r = req($p0, 'GET', "$prod/", [], ['Host: www.baznas.test', 'X-Forwarded-Proto: https']);
$cek('host www. (https) → 301 ke host kanonik', $r['kode'] === 301 && $r['lokasi'] === 'https://baznas.test/', $r['lokasi']);
$r = req($p0, 'POST', "$prod/kalkulator-zakat", ['a' => 1], ['Host: baznas.test']);
$cek('POST lewat http → 403 (tidak dialihkan)', $r['kode'] === 403);
$r = req($p0, 'GET', "$prod/", [], ['Host: baznas.test', 'X-Forwarded-Proto: https']);
$cek('https → 200 + Strict-Transport-Security max-age=31536000', $r['kode'] === 200 && preg_match('/strict-transport-security:\s*max-age=31536000/i', $r['head']) === 1);
$cek('http tidak mengirim HSTS (hanya lewat https)', preg_match('/strict-transport-security/i', req($p0, 'GET', "$prod/", [], ['Host: baznas.test'])['head']) === 0);
$r = req($p0, 'GET', "$prod/admin/masuk", [], ['Host: baznas.test', 'X-Forwarded-Proto: https']);
$cek('cookie sesi: Secure; HttpOnly; SameSite=Lax', preg_match('/set-cookie:[^\n]*secure[^\n]*httponly[^\n]*samesite=lax/i', $r['head']) === 1);
$r = req($p0, 'GET', "$prod/uji-f8-prod-404", [], ['Host: baznas.test', 'X-Forwarded-Proto: https']);
$cek('404 produksi: tanpa detail debug', $r['kode'] === 404 && !preg_match('#(Stack trace|Exception|/app/|xamppfiles)#', $r['body']));
$pdo->exec("DELETE FROM tautan_rusak WHERE path LIKE '/uji-f8%'");

// Bersihkan
$pdo->prepare('DELETE FROM user WHERE username = ?')->execute([$USER]);
$pdo->exec("DELETE FROM tautan_rusak WHERE path LIKE '/uji-f8%' OR path LIKE '/berita/uji-f8%'");
foreach (glob("$proyek/storage/cache/rl-*.json") ?: [] as $f) unlink($f);
printf("\n%s — %d gagal\n", $gagal === 0 ? 'SEMUA LULUS' : 'ADA YANG GAGAL', $gagal);
exit($gagal === 0 ? 0 : 1);

function e8(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
