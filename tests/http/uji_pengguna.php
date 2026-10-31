<?php
// Uji menu Pengguna (manajemen user admin) + keabsahan sesi. Pemakaian: php tests/http/uji_pengguna.php http://localhost:8002
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://localhost:8002', '/');
$proyek = dirname(__DIR__, 2);
$tmp = dirname(__DIR__) . '/tmp';
@mkdir($tmp);
$pdo = new PDO('mysql:host=127.0.0.1;dbname=baznas_basel;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$hapusRl = function () use ($proyek): void { foreach (glob("$proyek/storage/cache/rl-*.json") ?: [] as $f) unlink($f); };
$hapusRl();

$ADMIN = 'uji_pg_admin'; $PASS = 'Uji-Pengguna-' . bin2hex(random_bytes(3));
$OP = 'uji_pg_operator'; $PASS_OP = 'Operator-' . bin2hex(random_bytes(3)); $PASS_OP2 = $PASS_OP . '-baru';
$bersihkan = fn () => $pdo->exec("DELETE FROM user WHERE username LIKE 'uji\\_pg\\_%'");
$bersihkan();
$jumlahAwal = (int) $pdo->query('SELECT COUNT(*) FROM user')->fetchColumn();
$pdo->prepare("INSERT INTO user (username, password, f_name, l_name, level, status) VALUES (?, ?, 'Admin', 'Uji', 1, '1')")->execute([$ADMIN, password_hash($PASS, PASSWORD_BCRYPT)]);

function req(string $jar, string $method, string $url, array $data = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]);
    if ($method === 'POST') { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); }
    $raw = (string) curl_exec($ch);
    $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $r = ['kode' => curl_getinfo($ch, CURLINFO_RESPONSE_CODE), 'body' => substr($raw, $hs), 'head' => substr($raw, 0, $hs)];
    preg_match('/^location:\s*(\S+)/mi', $r['head'], $m);
    $r['lokasi'] = $m[1] ?? '';
    return $r;
}
function token(string $html): string { preg_match('/name="_token" value="([a-f0-9]{64})"/', $html, $m); return $m[1] ?? ''; }
function masuk(string $base, string $jar, string $u, string $p): array
{
    @unlink($jar);
    $f = req($jar, 'GET', "$base/admin/masuk");
    return req($jar, 'POST', "$base/admin/masuk", ['_token' => token($f['body']), 'username' => $u, 'password' => $p]);
}
$gagal = 0;
$cek = function (string $label, bool $ok, string $info = '') use (&$gagal): void {
    if (!$ok) $gagal++;
    printf("%s %-72s %s\n", $ok ? 'OK  ' : 'GAGAL', $label, $info);
};
$baris = fn (string $u) => $pdo->query('SELECT * FROM user WHERE username = ' . $pdo->quote($u))->fetch() ?: null;
$pesan = fn (string $html) => html_entity_decode(strip_tags($html));
$jA = "$tmp/pg-admin.txt"; $jO = "$tmp/pg-op.txt"; $jA2 = "$tmp/pg-admin2.txt";

try {
    echo "== Administrator\n";
    $r = masuk($base, $jA, $ADMIN, $PASS);
    $cek('login administrator uji', $r['kode'] === 303);
    $d = req($jA, 'GET', "$base/admin");
    $cek('menu memuat "Pengguna"', str_contains($d['body'], 'href="' . $base . '/admin/pengguna"'));
    $l = req($jA, 'GET', "$base/admin/pengguna");
    $cek('daftar pengguna 200; akun sendiri bertanda "Anda", tanpa tombol hapus', $l['kode'] === 200 && str_contains($l['body'], $ADMIN) && str_contains($l['body'], '>Anda<')
        && !str_contains($l['body'], '/admin/pengguna/' . $baris($ADMIN)['id_user'] . '/hapus'));
    $cek('hash password tidak pernah tampil', !str_contains($l['body'], '$2y$'));

    echo "\n== Tambah pengguna: validasi\n";
    $form = fn () => token(req($jA, 'GET', "$base/admin/pengguna/baru")['body']);
    $isian = ['username' => $OP, 'f_name' => 'Operator', 'l_name' => 'Uji', 'email' => 'operator@contoh.id', 'no_hp' => '0812 3456 7890', 'level' => '2', 'aktif' => '1', 'password' => $PASS_OP, 'password_ulang' => $PASS_OP];
    $tolak = function (string $label, array $ubah, string $pesanDicari) use ($jA, $base, $form, $isian, $cek, $pesan): void {
        $r = req($jA, 'POST', "$base/admin/pengguna", ['_token' => $form()] + $ubah + $isian);
        $g = req($jA, 'GET', "$base/admin/pengguna/baru");
        $cek($label, $r['kode'] === 303 && str_ends_with($r['lokasi'], '/admin/pengguna/baru') && str_contains($pesan($g['body']), $pesanDicari), $r['kode'] . ' ' . $r['lokasi']);
    };
    $tolak('username sudah dipakai (admin) ditolak', ['username' => 'admin'], 'Username sudah dipakai');
    $tolak('username dengan spasi/simbol ditolak', ['username' => 'op uji!'], 'Format Username tidak valid');
    $tolak('password < 8 karakter ditolak', ['password' => 'pendek', 'password_ulang' => 'pendek'], 'Password minimal 8 karakter');
    $tolak('ulangan password tidak sama ditolak', ['password_ulang' => 'beda-sekali-123'], 'Ulangan password tidak sama');
    $tolak('password = username ditolak', ['password' => $OP, 'password_ulang' => $OP], 'tidak boleh sama dengan username');
    $tolak('peran tidak dikenal ditolak', ['level' => '9'], 'Pilihan Peran tidak valid');
    $tolak('email tidak valid ditolak', ['email' => 'bukan-email'], 'Format Email tidak valid');
    $g = req($jA, 'GET', "$base/admin/pengguna/baru");
    $r = req($jA, 'POST', "$base/admin/pengguna", ['_token' => token($g['body'])] + ['username' => 'x'] + $isian);
    $g = req($jA, 'GET', "$base/admin/pengguna/baru");
    $cek('isian lama kembali setelah gagal, password tidak', str_contains($g['body'], 'value="operator@contoh.id"') && !str_contains($g['body'], $PASS_OP));
    $r = req($jA, 'POST', "$base/admin/pengguna", $isian);
    $cek('tanpa token CSRF → 419', $r['kode'] === 419);

    echo "\n== Tambah pengguna: berhasil\n";
    $r = req($jA, 'POST', "$base/admin/pengguna", ['_token' => $form()] + ['username' => strtoupper($OP)] + $isian);
    $op = $baris($OP);
    $cek('operator tersimpan (username jadi huruf kecil), level 2, aktif', $r['kode'] === 303 && $op !== null && (int) $op['level'] === 2 && $op['status'] === '1', $r['lokasi']);
    $cek('password di-hash bcrypt, created_user & created_IP terisi', $op !== null && str_starts_with($op['password'], '$2y$12$') && password_verify($PASS_OP, $op['password']) && $op['created_user'] !== null && $op['created_IP'] !== null);
    $cek('email & no HP tersimpan', $op !== null && $op['email'] === 'operator@contoh.id' && $op['no_hp'] === '0812 3456 7890');

    echo "\n== Operator\n";
    $r = masuk($base, $jO, $OP, $PASS_OP);
    $cek('operator bisa login', $r['kode'] === 303);
    $d = req($jO, 'GET', "$base/admin");
    $cek('menu operator TIDAK memuat Pengguna', $d['kode'] === 200 && !str_contains($d['body'], '/admin/pengguna"'));
    $cek('operator → /admin/pengguna 403', req($jO, 'GET', "$base/admin/pengguna")['kode'] === 403);
    $cek('operator → /admin/pengguna/baru 403', req($jO, 'GET', "$base/admin/pengguna/baru")['kode'] === 403);
    $t = token($d['body']);
    $r = req($jO, 'POST', "$base/admin/pengguna", ['_token' => $t] + ['username' => 'uji_pg_susup'] + $isian);
    $cek('operator POST tambah pengguna → 403, tidak tersimpan', $r['kode'] === 403 && $baris('uji_pg_susup') === null);
    $r = req($jO, 'POST', "$base/admin/pengguna/" . $baris($ADMIN)['id_user'] . '/hapus', ['_token' => $t]);
    $cek('operator hapus administrator → 403', $r['kode'] === 403 && $baris($ADMIN) !== null);
    $cek('operator tetap bisa membuka menu lain (kabar, pembayaran)', req($jO, 'GET', "$base/admin/post")['kode'] === 200 && req($jO, 'GET', "$base/admin/pembayaran")['kode'] === 200);

    echo "\n== Administrator mengubah operator\n";
    $u = req($jA, 'GET', "$base/admin/pengguna/{$op['id_user']}/ubah");
    $cek('form ubah: ada peran & status, password kosong', $u['kode'] === 200 && str_contains($u['body'], 'name="level"') && str_contains($u['body'], 'name="aktif"') && !str_contains($u['body'], '$2y$'));
    $ubah = ['_token' => token($u['body']), 'username' => $OP, 'f_name' => 'Operator Baru', 'l_name' => '', 'email' => '', 'no_hp' => '', 'level' => '2', 'aktif' => '1', 'password' => '', 'password_ulang' => ''];
    $r = req($jA, 'POST', "$base/admin/pengguna/{$op['id_user']}", $ubah);
    $op2 = $baris($OP);
    $cek('ubah nama tanpa ganti password: hash tetap, kolom kosong → NULL', $r['kode'] === 303 && $op2['f_name'] === 'Operator Baru' && $op2['password'] === $op['password'] && $op2['email'] === null && $op2['l_name'] === null);
    $d = req($jO, 'GET', "$base/admin");
    $cek('sesi operator tetap sah, nama di sesi ikut diperbarui', $d['kode'] === 200 && str_contains($d['body'], 'Operator Baru'));

    $r = req($jA, 'POST', "$base/admin/pengguna/{$op['id_user']}", ['_token' => token(req($jA, 'GET', "$base/admin/pengguna/{$op['id_user']}/ubah")['body'])] + ['password' => $PASS_OP2, 'password_ulang' => $PASS_OP2] + $ubah);
    $cek('administrator mengganti password operator', $r['kode'] === 303 && password_verify($PASS_OP2, $baris($OP)['password']));
    $d = req($jO, 'GET', "$base/admin");
    $m = req($jO, 'GET', "$base/admin/masuk");
    $cek('→ sesi operator langsung berakhir + pesan', $d['kode'] === 303 && str_contains($d['lokasi'], '/admin/masuk') && str_contains($pesan($m['body']), 'diubah atau dinonaktifkan'));
    $cek('operator login dengan password lama gagal, password baru berhasil', masuk($base, $jO, $OP, $PASS_OP)['lokasi'] !== $base . '/admin' && masuk($base, $jO, $OP, $PASS_OP2)['kode'] === 303 && req($jO, 'GET', "$base/admin")['kode'] === 200);

    $ubah['password'] = $ubah['password_ulang'] = '';
    $tanpaAktif = $ubah; unset($tanpaAktif['aktif']);
    $r = req($jA, 'POST', "$base/admin/pengguna/{$op['id_user']}", ['_token' => token(req($jA, 'GET', "$base/admin/pengguna/{$op['id_user']}/ubah")['body'])] + $tanpaAktif);
    $cek('administrator menonaktifkan operator', $r['kode'] === 303 && $baris($OP)['status'] === '0');
    $d = req($jO, 'GET', "$base/admin");
    $cek('→ sesi operator berakhir', $d['kode'] === 303 && str_contains($d['lokasi'], '/admin/masuk'));
    $r = masuk($base, $jO, $OP, $PASS_OP2);
    $m = req($jO, 'GET', "$base/admin/masuk");
    $cek('akun nonaktif tidak bisa login (pesan umum)', $r['kode'] === 303 && str_contains($r['lokasi'], '/admin/masuk') && str_contains($pesan($m['body']), 'Username atau password salah'));
    $l = req($jA, 'GET', "$base/admin/pengguna");
    $cek('daftar menandai operator "Nonaktif"', str_contains($l['body'], 'Nonaktif'));

    echo "\n== Melindungi akun sendiri\n";
    $idA = (int) $baris($ADMIN)['id_user'];
    $u = req($jA, 'GET', "$base/admin/pengguna/$idA/ubah");
    $cek('form akun sendiri: peran & status dikunci (tidak ada field-nya)', $u['kode'] === 200 && !str_contains($u['body'], 'name="level"') && !str_contains($u['body'], 'name="aktif"') && str_contains($u['body'], 'akun Anda sendiri'));
    $r = req($jA, 'POST', "$base/admin/pengguna/$idA", ['_token' => token($u['body']), 'username' => $ADMIN, 'f_name' => 'Admin', 'l_name' => 'Uji', 'level' => '2', 'password' => '', 'password_ulang' => '']);
    $a = $baris($ADMIN);
    $cek('POST paksa level=2 & tanpa aktif → tetap Administrator & aktif', $r['kode'] === 303 && (int) $a['level'] === 1 && $a['status'] === '1');
    $r = req($jA, 'POST', "$base/admin/pengguna/$idA/hapus", ['_token' => token(req($jA, 'GET', "$base/admin/pengguna")['body'])]);
    $cek('hapus akun sendiri ditolak', $r['kode'] === 303 && $baris($ADMIN) !== null);

    echo "\n== Ganti password sendiri\n";
    masuk($base, $jA2, $ADMIN, $PASS); // sesi kedua (perangkat lain)
    $PASS2 = $PASS . '-2';
    $u = req($jA, 'GET', "$base/admin/pengguna/$idA/ubah");
    $r = req($jA, 'POST', "$base/admin/pengguna/$idA", ['_token' => token($u['body']), 'username' => $ADMIN, 'f_name' => 'Admin', 'l_name' => 'Uji', 'password' => $PASS2, 'password_ulang' => $PASS2]);
    $cek('lewat menu Pengguna: sesi ini tetap masuk', $r['kode'] === 303 && req($jA, 'GET', "$base/admin")['kode'] === 200);
    $cek('sesi di perangkat lain berakhir', req($jA2, 'GET', "$base/admin")['kode'] === 303);
    masuk($base, $jA2, $ADMIN, $PASS2);
    $PASS3 = $PASS . '-3';
    $ak = req($jA, 'GET', "$base/admin/akun");
    $r = req($jA, 'POST', "$base/admin/akun", ['_token' => token($ak['body']), 'password_lama' => $PASS2, 'password_baru' => $PASS3, 'password_ulang' => $PASS3]);
    $cek('lewat Akun Saya: sesi ini tetap masuk', $r['kode'] === 303 && req($jA, 'GET', "$base/admin")['kode'] === 200);
    $cek('sesi di perangkat lain berakhir', req($jA2, 'GET', "$base/admin")['kode'] === 303);

    echo "\n== Kepala (hanya melihat dasbor)\n";
    $KP = 'uji_pg_kepala'; $PASS_KP = 'Kepala-' . bin2hex(random_bytes(3)); $jK = "$tmp/pg-kepala.txt";
    $r = req($jA, 'POST', "$base/admin/pengguna", ['_token' => $form(), 'username' => $KP, 'f_name' => 'Kepala', 'l_name' => 'BAZNAS', 'email' => '', 'no_hp' => '',
        'level' => '3', 'aktif' => '1', 'password' => $PASS_KP, 'password_ulang' => $PASS_KP]);
    $cek('administrator membuat akun peran Kepala (level 3)', $r['kode'] === 303 && (int) ($baris($KP)['level'] ?? 0) === 3);
    $cek('daftar pengguna menampilkan peran "Kepala"', str_contains(req($jA, 'GET', "$base/admin/pengguna")['body'], '>Kepala</span>'));
    $r = masuk($base, $jK, $KP, $PASS_KP);
    $d = req($jK, 'GET', "$base/admin");
    $cek('kepala login → dasbor 200 berisi statistik zakat masuk', $r['kode'] === 303 && $d['kode'] === 200 && str_contains($d['body'], 'id="statistik"'));
    $menuKepala = preg_match_all('#href="' . preg_quote($base, '#') . '/admin/([a-z-]+)"#', $d['body'], $m) ? array_values(array_unique($m[1])) : [];
    $cek('menu kepala hanya Dasbor & Akun Saya', $menuKepala === ['akun'], implode(',', $menuKepala));
    $cek('dasbor kepala tanpa antrean/pintasan/daftar pembayaran/tautan kelola', !str_contains($d['body'], 'Pintasan') && !str_contains($d['body'], 'Konfirmasi menunggu')
        && !str_contains($d['body'], 'Pembayaran terbaru') && !str_contains($d['body'], '/admin/pembayaran') && !str_contains($d['body'], '/admin/setting'));
    $cek('filter periode tetap bisa dipakai', req($jK, 'GET', "$base/admin?periode=semua")['kode'] === 200);
    $tolak403 = [];
    foreach (['/admin/post', '/admin/post/baru', '/admin/pembayaran', '/admin/pembayaran/1', '/admin/bukti/1', '/admin/setting', '/admin/pengguna',
              '/admin/status', '/admin/tautan-rusak', '/admin/redirect', '/admin/banner', '/admin/pilar/1/ubah'] as $p) {
        if (req($jK, 'GET', $base . $p)['kode'] !== 403) $tolak403[] = $p;
    }
    $cek('semua halaman kelola (GET) → 403', $tolak403 === [], implode(' ', $tolak403));
    $tk = token($d['body']);
    $jumlahPost = (int) $pdo->query('SELECT COUNT(*) FROM post')->fetchColumn();
    $postGagal = [];
    foreach (['/admin/post' => ['judul' => 'Susupan kepala', 'jenis' => 'berita', 'konten' => '<p>x</p>', 'tgl' => date('Y-m-d')], '/admin/setting' => ['nama_situs' => 'X'],
              '/admin/konfirmasi/1/status' => ['status' => 'ditolak'], '/admin/pembayaran/1/status' => ['status' => '2'], '/admin/pilar/1' => ['label' => 'X']] as $p => $isi) {
        if (req($jK, 'POST', $base . $p, ['_token' => $tk] + $isi)['kode'] !== 403) $postGagal[] = $p;
    }
    $cek('semua aksi ubah data (POST bertoken sah) → 403, data tidak berubah', $postGagal === [] && (int) $pdo->query('SELECT COUNT(*) FROM post')->fetchColumn() === $jumlahPost, implode(' ', $postGagal));
    $ak = req($jK, 'GET', "$base/admin/akun");
    $r = req($jK, 'POST', "$base/admin/akun", ['_token' => token($ak['body']), 'password_lama' => $PASS_KP, 'password_baru' => $PASS_KP . '-2', 'password_ulang' => $PASS_KP . '-2']);
    $cek('kepala bisa mengganti password sendiri (Akun Saya)', $ak['kode'] === 200 && $r['kode'] === 303 && password_verify($PASS_KP . '-2', $baris($KP)['password']));
    $r = req($jK, 'POST', "$base/admin/keluar", ['_token' => token(req($jK, 'GET', "$base/admin")['body'])]);
    $cek('kepala bisa keluar', $r['kode'] === 303 && req($jK, 'GET', "$base/admin")['kode'] === 303);
    @unlink($jK);

    echo "\n== Hapus pengguna\n";
    $l = req($jA, 'GET', "$base/admin/pengguna");
    $r = req($jA, 'POST', "$base/admin/pengguna/{$op['id_user']}/hapus", ['_token' => token($l['body'])]);
    $cek('hapus operator', $r['kode'] === 303 && $baris($OP) === null);
    $cek('pengguna tidak ada → 404', req($jA, 'GET', "$base/admin/pengguna/999999/ubah")['kode'] === 404 && req($jA, 'GET', "$base/admin/pengguna/abc/ubah")['kode'] === 404);
} finally {
    $bersihkan();
    $hapusRl();
    foreach ([$jA, $jO, $jA2] as $j) @unlink($j);
}
$cek('data kembali seperti semula', (int) $pdo->query('SELECT COUNT(*) FROM user')->fetchColumn() === $jumlahAwal);
printf("\n%s — %d gagal\n", $gagal === 0 ? 'SEMUA LULUS' : 'ADA YANG GAGAL', $gagal);
exit($gagal === 0 ? 0 : 1);
