<?php
// Uji end-to-end panel admin (Fase 7). Pemakaian: php uji_admin.php http://localhost:8002
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://localhost:8002', '/');
$proyek = dirname(__DIR__, 2);
$tmp = dirname(__DIR__) . '/tmp';
@mkdir($tmp);
$pdo = new PDO('mysql:host=127.0.0.1;dbname=baznas_basel;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
require __DIR__ . '/zakat_kosong.inc.php';
$hapusRl = function () use ($proyek): void { foreach (glob("$proyek/storage/cache/rl-*.json") ?: [] as $f) unlink($f); };
$hapusRl();

// Akun uji sementara
$USER = 'uji_admin_f7'; $PASS = 'Uji-Admin-Fase7-' . bin2hex(random_bytes(3)); $PASS2 = $PASS . '-baru';
$pdo->prepare('DELETE FROM user WHERE username = ?')->execute([$USER]);
$pdo->prepare("INSERT INTO user (username, password, f_name, l_name, level, status) VALUES (?, ?, 'Admin', 'Uji', 1, '1')")->execute([$USER, password_hash($PASS, PASSWORD_BCRYPT)]);
$settingAwal = $pdo->query("SELECT kunci, nilai FROM setting")->fetchAll(PDO::FETCH_KEY_PAIR);
$awal = [
    'banner' => (int) $pdo->query('SELECT COUNT(*) FROM banner')->fetchColumn(),
    'post' => (int) $pdo->query('SELECT COUNT(*) FROM post')->fetchColumn(),
    'video' => (int) $pdo->query('SELECT COUNT(*) FROM video')->fetchColumn(),
    'redirect' => (int) $pdo->query('SELECT COUNT(*) FROM redirect')->fetchColumn(),
    'pembayaran' => (int) $pdo->query('SELECT COUNT(*) FROM pembayaran')->fetchColumn(),
    'konfirmasi' => (int) $pdo->query('SELECT COUNT(*) FROM konfirmasi_pembayaran')->fetchColumn(),
];

function req(string $jar, string $method, string $url, array $data = [], bool $multipart = false): array
{
    $ch = curl_init($url);
    $opt = [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60];
    if ($method === 'POST') { $opt[CURLOPT_POST] = true; $opt[CURLOPT_POSTFIELDS] = $multipart ? $data : http_build_query($data); }
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
function sesi(string $jar): string { preg_match('/baznas_sid\s+(\S+)/', (string) @file_get_contents($jar), $m); return $m[1] ?? ''; }

$gagal = 0;
$cek = function (string $label, bool $ok, string $info = '') use (&$gagal): void {
    if (!$ok) $gagal++;
    printf("%s %-66s %s\n", $ok ? 'OK  ' : 'GAGAL', $label, $info);
};
$jar = "$tmp/admin.txt"; @unlink($jar);
$tok = fn () => token(req($jar, 'GET', "$base/admin/setting")['body']);

echo "== Login\n";
$r = req($jar, 'GET', "$base/admin");
$cek('/admin tanpa login → 303 ke /admin/masuk?ke=/admin', $r['kode'] === 303 && str_contains($r['lokasi'], '/admin/masuk?ke=%2Fadmin'));
$r = req($jar, 'GET', "$base/admin/post");
$cek('/admin/post tanpa login → 303 login', $r['kode'] === 303);
$f = req($jar, 'GET', "$base/admin/masuk");
$cek('halaman masuk: 200, noindex, no-store', $f['kode'] === 200 && str_contains($f['body'], 'noindex') && preg_match('/cache-control:\s*no-store/i', $f['head']) === 1);
$sidAwal = sesi($jar);
$r = req($jar, 'POST', "$base/admin/masuk", ['_token' => token($f['body']), 'username' => $USER, 'password' => 'salah']);
$g = req($jar, 'GET', "$base/admin/masuk");
$cek('password salah → pesan umum', str_contains($g['body'], 'Username atau password salah'));
$r = req($jar, 'POST', "$base/admin/masuk", ['_token' => token($g['body']), 'username' => 'admin', 'password' => 'admin']);
$g = req($jar, 'GET', "$base/admin/masuk");
$cek('akun admin: login MD5 lama ditolak ATAU sudah direset ke bcrypt', str_contains($g['body'], 'format lama') || str_starts_with((string) $pdo->query("SELECT password FROM user WHERE username = 'admin'")->fetchColumn(), '$2y$'));
$r = req($jar, 'POST', "$base/admin/masuk", ['_token' => token($g['body']), 'username' => 'tidak_ada', 'password' => 'x']);
$g = req($jar, 'GET', "$base/admin/masuk");
$cek('username tidak ada → pesan umum yang sama', str_contains($g['body'], 'Username atau password salah'));
$r = req($jar, 'POST', "$base/admin/masuk", ['_token' => token($g['body']), 'username' => $USER, 'password' => $PASS, 'ke' => 'https://evil.example/x']);
$cek('login benar → 303; ke= eksternal diabaikan (ke /admin)', $r['kode'] === 303 && str_ends_with($r['lokasi'], '/admin'), $r['lokasi']);
$cek('ID sesi diganti saat login (anti session fixation)', $sidAwal !== '' && sesi($jar) !== $sidAwal);
$d = req($jar, 'GET', "$base/admin");
$cek('dasbor: 200, ringkasan & peringatan setting zakat', $d['kode'] === 200 && str_contains($d['body'], 'Pembayaran menunggu') && str_contains($d['body'], 'Pengaturan zakat belum lengkap'));
$cek('dasbor: X-Robots-Tag noindex + no-store', preg_match('/x-robots-tag:\s*noindex/i', $d['head']) === 1 && preg_match('/cache-control:\s*no-store/i', $d['head']) === 1);

echo "\n== Semua halaman admin\n";
$res = array_keys(require "$proyek/config/admin.php")['resource'] ?? [];
$def = (require "$proyek/config/admin.php");
$okSemua = true; $catatan = [];
foreach ($def['resource'] as $k => $rd) {
    $a = req($jar, 'GET', "$base/admin/$k");
    $b = in_array('tambah', $rd['aksi'] ?? ['tambah'], true) ? req($jar, 'GET', "$base/admin/$k/baru") : ['kode' => 200, 'body' => ''];
    $err = preg_match('/(Fatal error|Warning:|Uncaught)/', $a['body'] . $b['body']);
    if ($a['kode'] !== 200 || $b['kode'] !== 200 || $err) { $okSemua = false; $catatan[] = "$k:{$a['kode']}/{$b['kode']}"; }
}
$cek('16 daftar & form tambah → 200 tanpa error PHP', $okSemua, implode(' ', $catatan));
foreach (['/admin/pembayaran', '/admin/setting', '/admin/akun', '/admin/pilar/1/ubah'] as $p) {
    $cek("GET $p → 200", req($jar, 'GET', $base . $p)['kode'] === 200);
}
$cek('pilar tidak bisa ditambah (aksi dibatasi) → 404', req($jar, 'GET', "$base/admin/pilar/baru")['kode'] === 404);
$cek('resource tak dikenal → 404', req($jar, 'GET', "$base/admin/tidakada")['kode'] === 404);
$cek('POST admin tanpa token CSRF → 419', req($jar, 'POST', "$base/admin/banner", ['judul' => 'x'])['kode'] === 419);

echo "\n== Banner (unggah gambar → varian WebP, cache beranda)\n";
$jpg = "$tmp/banner.jpg"; $im = imagecreatetruecolor(1600, 700); imagefill($im, 0, 0, imagecolorallocate($im, 11, 107, 58)); imagejpeg($im, $jpg, 85);
$jpg2 = "$tmp/banner2.png"; $im = imagecreatetruecolor(1200, 900); imagefill($im, 0, 0, imagecolorallocate($im, 242, 183, 5)); imagepng($im, $jpg2);
$shell = "$tmp/shell.jpg"; file_put_contents($shell, '<?php echo 1; ?>');
req($jar, 'GET', "$base/"); // isi cache beranda
$r = req($jar, 'POST', "$base/admin/banner", ['_token' => $tok(), 'judul' => 'Banner Uji F7', 'link' => '/bayar-zakat', 'urutan' => '0', 'tgl' => date('Y-m-d'), 'publish' => '1', 'foto' => new CURLFile($shell, 'image/jpeg')], true);
$g = req($jar, 'GET', $r['lokasi'] ?: "$base/admin/banner/baru");
$cek('unggah PHP berkedok JPG ditolak', $r['kode'] === 303 && str_contains($g['body'], 'Format gambar harus'));
$r = req($jar, 'POST', "$base/admin/banner", ['_token' => $tok(), 'judul' => 'Banner Uji F7', 'link' => '/bayar-zakat', 'urutan' => '0', 'tgl' => date('Y-m-d'), 'publish' => '1', 'foto' => new CURLFile($jpg, 'image/jpeg')], true);
$b = $pdo->query("SELECT * FROM banner WHERE judul = 'Banner Uji F7'")->fetch();
$cek('banner tersimpan → 303 ke daftar', $r['kode'] === 303 && str_ends_with($r['lokasi'], '/admin/banner') && $b !== false);
$dir = "$proyek/public/uploads/banner/";
$nama = pathinfo((string) ($b['foto'] ?? ''), PATHINFO_FILENAME);
$cek('file utama acak + 4 varian WebP dibuat', $b && preg_match('/^[a-f0-9]{32}\.jpg$/', $b['foto']) && is_file($dir . $b['foto']) && count(glob("$dir$nama-*.webp")) === 4, $b['foto'] ?? '');
$h = req($jar, 'GET', "$base/");
$cek('beranda langsung menampilkan banner baru (cache home dihapus)', str_contains($h['body'], $nama));
$fotoLama = $b['foto'];
$r = req($jar, 'POST', "$base/admin/banner/{$b['id_banner']}", ['_token' => $tok(), 'judul' => 'Banner Uji F7', 'link' => '', 'urutan' => '5', 'tgl' => date('Y-m-d'), 'foto' => new CURLFile($jpg2, 'image/png')], true);
$b2 = $pdo->query("SELECT * FROM banner WHERE id_banner = {$b['id_banner']}")->fetch();
$cek('ganti gambar: file baru .png, file lama + varian terhapus', $b2['foto'] !== $fotoLama && str_ends_with($b2['foto'], '.png') && !is_file($dir . $fotoLama) && count(glob("$dir$nama-*.webp")) === 0);
$cek('checkbox publish tidak dicentang → N; link kosong → NULL; urutan 5', $b2['publish'] === 'N' && $b2['link'] === null && (int) $b2['urutan'] === 5);
$nama2 = pathinfo($b2['foto'], PATHINFO_FILENAME);
$r = req($jar, 'POST', "$base/admin/banner/{$b['id_banner']}/hapus", ['_token' => $tok()]);
$cek('hapus banner: baris & semua file hilang', $pdo->query("SELECT COUNT(*) FROM banner WHERE id_banner = {$b['id_banner']}")->fetchColumn() == 0 && !is_file($dir . $b2['foto']) && count(glob("$dir$nama2-*.webp")) === 0);

echo "\n== Kabar (sanitasi, slug, ringkasan, redirect)\n";
$r = req($jar, 'POST', "$base/admin/post", ['_token' => $tok(), 'judul' => '', 'jenis' => 'berita', 'konten' => '', 'tgl' => date('Y-m-d')]);
$g = req($jar, 'GET', "$base/admin/post/baru");
$cek('judul & isi kosong → kembali ke form dengan pesan', str_contains($g['body'], 'Judul wajib diisi') && str_contains($g['body'], 'Isi wajib diisi'));
$r = req($jar, 'POST', "$base/admin/post", ['_token' => $tok(), 'judul' => 'Kabar Uji Fase Tujuh Admin', 'jenis' => 'jenis-palsu', 'konten' => '<p>x</p>', 'tgl' => date('Y-m-d')]);
$g = req($jar, 'GET', "$base/admin/post/baru");
$cek('pilihan jenis palsu ditolak', str_contains($g['body'], 'Pilihan Jenis tidak valid'));
$konten = '<h1>Judul</h1><p style="color:red" onclick="x()">Penyaluran zakat <script>alert(1)</script>kepada mustahik di Toboali berjalan lancar dan tertib.</p><p><a href="javascript:alert(1)">x</a></p>';
$r = req($jar, 'POST', "$base/admin/post", ['_token' => $tok(), 'judul' => 'Kabar Uji Fase Tujuh Admin', 'jenis' => 'berita', 'id_sub_program' => '7', 'konten' => $konten, 'ringkasan' => '', 'slug' => '', 'penulis' => 'Tim Uji', 'tgl' => date('Y-m-d'), 'publish' => '1', 'is_featured' => '1']);
$p = $pdo->query("SELECT * FROM post WHERE judul = 'Kabar Uji Fase Tujuh Admin'")->fetch();
$cek('kabar tersimpan, slug otomatis', $p && $p['slug'] === 'kabar-uji-fase-tujuh-admin', $p['slug'] ?? '');
$cek('konten disanitasi (tanpa script/style/onclick/javascript:; h1→h2)', $p && !preg_match('/script|style=|onclick|javascript:/i', $p['konten']) && str_contains($p['konten'], '<h2>'), $p['konten'] ?? '');
$cek('ringkasan otomatis dari isi', $p && str_starts_with((string) $p['ringkasan'], 'Judul Penyaluran zakat'), (string) ($p['ringkasan'] ?? ''));
$pub = req($jar, 'GET', "$base/berita/kabar-uji-fase-tujuh-admin");
$cek('tampil di situs /berita/{slug} + pilar Basel Sehat', $pub['kode'] === 200 && str_contains($pub['body'], 'Basel Sehat'));
$cek('muncul di ticker beranda (is_featured)', str_contains(req($jar, 'GET', "$base/")['body'], 'Kabar Uji Fase Tujuh Admin'));
$r = req($jar, 'POST', "$base/admin/post/{$p['id']}", ['_token' => $tok(), 'judul' => 'Kabar Uji Fase Tujuh Admin', 'jenis' => 'berita', 'konten' => $p['konten'], 'ringkasan' => $p['ringkasan'], 'slug' => 'kabar-uji-f7-baru', 'tgl' => date('Y-m-d'), 'publish' => '1']);
$lama = req($jar, 'GET', "$base/berita/kabar-uji-fase-tujuh-admin");
$cek('slug diubah → alamat lama 301 ke alamat baru', $lama['kode'] === 301 && str_ends_with($lama['lokasi'], '/berita/kabar-uji-f7-baru'));
$r2 = req($jar, 'POST', "$base/admin/post", ['_token' => $tok(), 'judul' => 'Kabar Uji F7 Baru', 'jenis' => 'artikel', 'konten' => '<p>Isi kedua untuk menguji slug unik.</p>', 'slug' => 'kabar-uji-f7-baru', 'tgl' => date('Y-m-d')]);
$p2 = $pdo->query("SELECT slug, publish FROM post WHERE judul = 'Kabar Uji F7 Baru'")->fetch();
$cek('slug bentrok → otomatis "-2"; publish tak dicentang → N', $p2 && $p2['slug'] === 'kabar-uji-f7-baru-2' && $p2['publish'] === 'N', json_encode($p2));
$cek('kabar draf tidak tampil di situs (404)', req($jar, 'GET', "$base/berita/kabar-uji-f7-baru-2")['kode'] === 404);

echo "\n== Video & setting\n";
$r = req($jar, 'POST', "$base/admin/video", ['_token' => $tok(), 'judul' => 'Video Uji F7', 'link' => 'https://vimeo.com/123', 'tgl' => date('Y-m-d')]);
$g = req($jar, 'GET', "$base/admin/video/baru");
$cek('tautan bukan YouTube ditolak', str_contains($g['body'], 'Tautan YouTube tidak dikenali'));
$r = req($jar, 'POST', "$base/admin/video", ['_token' => $tok(), 'judul' => 'Video Uji F7', 'link' => 'https://youtu.be/rg6aeHegGSA?si=abc', 'tgl' => date('Y-m-d')]);
$v = $pdo->query("SELECT youtube_id FROM video WHERE judul = 'Video Uji F7'")->fetchColumn();
$cek('video valid → youtube_id diisi otomatis', $v === 'rg6aeHegGSA', (string) $v);

$isian = ['_token' => $tok()];
foreach ($settingAwal as $k => $n) { $isian[$k] = (string) $n; }
$isian = array_merge($isian, ['site_name' => $settingAwal['site_name'], 'nilai_fitrah' => '45.000', 'harga_emas_per_gram' => '2.000.000', 'whatsapp' => '0821-8021-8241', 'instagram' => 'bukan-url']);
$r = req($jar, 'POST', "$base/admin/setting", $isian);
$g = req($jar, 'GET', "$base/admin/setting");
$cek('setting: URL tidak valid ditolak', str_contains($g['body'], 'Format Instagram tidak valid'));
$isian['instagram'] = (string) $settingAwal['instagram']; $isian['_token'] = token($g['body']);
$r = req($jar, 'POST', "$base/admin/setting", $isian);
$s = $pdo->query("SELECT kunci, nilai FROM setting WHERE kunci IN ('nilai_fitrah','harga_emas_per_gram','whatsapp')")->fetchAll(PDO::FETCH_KEY_PAIR);
$cek('setting tersimpan: 45000, 2000000, WA 6282180218241', $s['nilai_fitrah'] === '45000' && $s['harga_emas_per_gram'] === '2000000' && $s['whatsapp'] === '6282180218241', json_encode($s));
$k = req($jar, 'GET', "$base/kalkulator-zakat");
$cek('kalkulator langsung memakai nilai baru (tanpa isian harga emas)', str_contains($k['body'], 'data-harga-emas="2000000"') && !str_contains($k['body'], 'k-penghasilan-harga-emas'));

echo "\n== Pembayaran & bukti\n";
$pub = "$tmp/publik.txt"; @unlink($pub);
$bf = req($pub, 'GET', "$base/bayar-zakat");
$r = req($pub, 'POST', "$base/bayar-zakat", ['_token' => token($bf['body']), 'jenis' => 1, 'jiwa' => '2', 'nominal' => '', 'sapaan' => 'BAPAK', 'nama_lengkap' => 'Uji Admin F7', 'no_hp' => '081234567890']);
preg_match('#/bayar-zakat/([A-Z2-9]{8})$#', $r['lokasi'], $m); $KODE = $m[1] ?? '';
$pb = $pdo->query("SELECT * FROM pembayaran WHERE kode_pembayaran = " . $pdo->quote($KODE))->fetch();
$cek('fitrah kini dihitung server dari setting: 2 × 45.000', $pb && (int) $pb['nominal'] === 90000, (string) ($pb['nominal'] ?? ''));
$bukti = "$tmp/bukti.jpg"; $im = imagecreatetruecolor(800, 600); imagefill($im, 0, 0, imagecolorallocate($im, 200, 220, 255)); imagejpeg($im, $bukti);
$rek = (int) $pdo->query('SELECT id_rekening FROM rekening LIMIT 1')->fetchColumn();
$kf = req($pub, 'GET', "$base/konfirmasi?kode=$KODE");
req($pub, 'POST', "$base/konfirmasi", ['_token' => token($kf['body']), 'kode' => $KODE, 'bank_pengirim' => 'BSI', 'rekening' => $rek, 'tgl_transfer' => date('Y-m-d'), 'nominal_transfer' => '90.000', 'bukti' => new CURLFile($bukti, 'image/jpeg')], true);
$kr = $pdo->query("SELECT * FROM konfirmasi_pembayaran WHERE kode_pembayaran = " . $pdo->quote($KODE))->fetch();
$l = req($jar, 'GET', "$base/admin/pembayaran?konfirmasi=baru");
$cek('daftar admin: filter "konfirmasi baru" memuat kode', str_contains($l['body'], $KODE));
$dt = req($jar, 'GET', "$base/admin/pembayaran/{$pb['id_pembayaran']}");
$cek('detail: data muzaki + gambar bukti via /admin/bukti/{id}', $dt['kode'] === 200 && str_contains($dt['body'], 'Uji Admin F7') && str_contains($dt['body'], "/admin/bukti/{$kr['id_konfirmasi_pembayaran']}"));
$bk = req($jar, 'GET', "$base/admin/bukti/{$kr['id_konfirmasi_pembayaran']}");
$cek('bukti tersaji untuk admin (image/webp, nosniff, no-store)', $bk['kode'] === 200 && preg_match('/content-type:\s*image\/webp/i', $bk['head']) === 1 && preg_match('/nosniff/i', $bk['head']) === 1 && preg_match('/no-store/i', $bk['head']) === 1);
$cek('bukti TIDAK tersaji tanpa login (→ login)', req($pub, 'GET', "$base/admin/bukti/{$kr['id_konfirmasi_pembayaran']}")['kode'] === 303);
$r = req($jar, 'POST', "$base/admin/konfirmasi/{$kr['id_konfirmasi_pembayaran']}/status", ['_token' => token($dt['body']), 'status' => 'diverifikasi']);
$st = $pdo->query("SELECT p.status, k.status AS ks FROM pembayaran p JOIN konfirmasi_pembayaran k ON k.id_pembayaran = p.id_pembayaran WHERE p.kode_pembayaran = " . $pdo->quote($KODE))->fetch();
$cek('verifikasi → konfirmasi diverifikasi & pembayaran selesai', $st['ks'] === 'diverifikasi' && $st['status'] === '1');
$cek('halaman muzaki kini "Pembayaran Terverifikasi"', str_contains(req($pub, 'GET', "$base/bayar-zakat/$KODE")['body'], 'Pembayaran Terverifikasi'));
$r = req($jar, 'POST', "$base/admin/pembayaran/{$pb['id_pembayaran']}/status", ['_token' => $tok(), 'status' => '9']);
$cek('status pembayaran tidak valid → 422', $r['kode'] === 422);

echo "\n== Akun, sesi diam, keluar\n";
$r = req($jar, 'POST', "$base/admin/akun", ['_token' => $tok(), 'password_lama' => 'salah', 'password_baru' => $PASS2, 'password_ulang' => $PASS2]);
$cek('ganti password dengan password lama salah ditolak', str_contains(req($jar, 'GET', "$base/admin/akun")['body'], 'Password saat ini salah'));
$r = req($jar, 'POST', "$base/admin/akun", ['_token' => $tok(), 'password_lama' => $PASS, 'password_baru' => 'pendek', 'password_ulang' => 'pendek']);
$cek('password baru < 8 karakter ditolak', str_contains(req($jar, 'GET', "$base/admin/akun")['body'], 'minimal 8 karakter'));
$r = req($jar, 'POST', "$base/admin/akun", ['_token' => $tok(), 'password_lama' => $PASS, 'password_baru' => $PASS2, 'password_ulang' => $PASS2]);
$cek('ganti password berhasil (bcrypt)', password_verify($PASS2, (string) $pdo->query("SELECT password FROM user WHERE username = '$USER'")->fetchColumn()));

// Sesi diam > 30 menit: mundurkan admin_aktif di file sesi
$sid = sesi($jar);
$fileSesi = rtrim(ini_get('session.save_path') ?: sys_get_temp_dir(), '/') . "/sess_$sid";
$isiSesi = (string) @file_get_contents($fileSesi);
if ($isiSesi !== '') {
    file_put_contents($fileSesi, preg_replace('/admin_aktif\|i:\d+;/', 'admin_aktif|i:' . (time() - 3600) . ';', $isiSesi));
    $r = req($jar, 'GET', "$base/admin");
    $g = req($jar, 'GET', $r['lokasi']);
    $cek('diam > 30 menit → keluar otomatis + pesan', $r['kode'] === 303 && str_contains($g['body'], 'tidak aktif selama 30 menit'));
} else {
    $cek('diam > 30 menit (file sesi tidak ditemukan untuk diuji)', false, $fileSesi);
}
$f = req($jar, 'GET', "$base/admin/masuk");
req($jar, 'POST', "$base/admin/masuk", ['_token' => token($f['body']), 'username' => $USER, 'password' => $PASS2]);
$r = req($jar, 'POST', "$base/admin/keluar", ['_token' => $tok()]);
$cek('keluar → /admin kembali meminta login', $r['kode'] === 303 && req($jar, 'GET', "$base/admin")['kode'] === 303);

echo "\n== Rate limit login\n";
$hapusRl();
$jar2 = "$tmp/rl.txt"; @unlink($jar2);
for ($n = 1; $n <= 6; $n++) {
    $f = req($jar2, 'GET', "$base/admin/masuk");
    req($jar2, 'POST', "$base/admin/masuk", ['_token' => token($f['body']), 'username' => $USER, 'password' => 'salah-' . $n]);
}
$f = req($jar2, 'GET', "$base/admin/masuk");
$cek('6× salah → "Terlalu banyak percobaan" (5×/15 menit)', str_contains($f['body'], 'Terlalu banyak percobaan'));
req($jar2, 'POST', "$base/admin/masuk", ['_token' => token($f['body']), 'username' => $USER, 'password' => $PASS2]);
$cek('password benar pun ditolak selama terkunci', req($jar2, 'GET', "$base/admin")['kode'] === 303);

echo "\n== Bersihkan\n";
$hapusRl();
foreach ($pdo->query("SELECT bukti_pembayaran FROM konfirmasi_pembayaran WHERE kode_pembayaran = " . $pdo->quote($KODE))->fetchAll(PDO::FETCH_COLUMN) as $bf) { @unlink("$proyek/storage/bukti/$bf"); }
$pdo->exec("DELETE FROM konfirmasi_pembayaran WHERE kode_pembayaran = " . $pdo->quote($KODE));
$pdo->exec("DELETE FROM pembayaran WHERE kode_pembayaran = " . $pdo->quote($KODE));
$pdo->exec("DELETE FROM post WHERE judul IN ('Kabar Uji Fase Tujuh Admin', 'Kabar Uji F7 Baru')");
$pdo->exec("DELETE FROM redirect WHERE dari LIKE '/berita/kabar-uji-%' OR ke LIKE '/berita/kabar-uji-%'");
$pdo->exec("DELETE FROM video WHERE judul = 'Video Uji F7'");
$pdo->exec("DELETE FROM banner WHERE judul = 'Banner Uji F7'");
$pdo->prepare('DELETE FROM user WHERE username = ?')->execute([$USER]);
$up = $pdo->prepare('UPDATE setting SET nilai = ? WHERE kunci = ?');
foreach ($settingAwal as $kk => $nn) { $up->execute([$nn, $kk]); }
foreach (glob("$proyek/storage/cache/*.cache") ?: [] as $c) unlink($c);
foreach (array_reverse(glob("$proyek/storage/bukti/*/*") ?: []) as $d) @rmdir($d);
foreach (glob("$proyek/storage/bukti/*") ?: [] as $d) @rmdir($d);
array_map('unlink', glob("$tmp/*") ?: []);
$akhir = [
    'banner' => (int) $pdo->query('SELECT COUNT(*) FROM banner')->fetchColumn(),
    'post' => (int) $pdo->query('SELECT COUNT(*) FROM post')->fetchColumn(),
    'video' => (int) $pdo->query('SELECT COUNT(*) FROM video')->fetchColumn(),
    'redirect' => (int) $pdo->query('SELECT COUNT(*) FROM redirect')->fetchColumn(),
    'pembayaran' => (int) $pdo->query('SELECT COUNT(*) FROM pembayaran')->fetchColumn(),
    'konfirmasi' => (int) $pdo->query('SELECT COUNT(*) FROM konfirmasi_pembayaran')->fetchColumn(),
];
$settingAkhir = $pdo->query("SELECT kunci, nilai FROM setting")->fetchAll(PDO::FETCH_KEY_PAIR);
$cek('data kembali seperti semula (banner, post, video, redirect, pembayaran, konfirmasi, setting, user uji)', $akhir === $awal && $settingAkhir === $settingAwal && !$pdo->query("SELECT 1 FROM user WHERE username = '$USER'")->fetchColumn(), json_encode($akhir));

echo $gagal === 0 ? "\nSEMUA LULUS\n" : "\n$gagal GAGAL\n";
