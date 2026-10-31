<?php
// Uji warna/transparansi kotak ikon 5 pilar + Ikon Navigasi bawah (admin → situs). Pemakaian: php tests/http/uji_tampilan.php http://localhost:8002
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://localhost:8002', '/');
$proyek = dirname(__DIR__, 2);
$tmp = dirname(__DIR__) . '/tmp';
@mkdir($tmp);
$pdo = new PDO('mysql:host=127.0.0.1;dbname=baznas_basel;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$hapusCache = function () use ($proyek): void { foreach ([...glob("$proyek/storage/cache/*.cache") ?: [], ...glob("$proyek/storage/cache/rl-*.json") ?: []] as $f) unlink($f); };

// Simpan keadaan awal (dipulihkan di akhir)
$pilarAwal = $pdo->query('SELECT * FROM kategori_program WHERE id_kategori_program = 1')->fetch();
$navAwal = $pdo->query('SELECT * FROM ikon_nav')->fetchAll();
$PASS = 'Uji-Tampilan-' . bin2hex(random_bytes(3));
$pdo->exec("DELETE FROM user WHERE username = 'uji_tp_admin'");
$pdo->prepare("INSERT INTO user (username, password, f_name, l_name, level, status) VALUES ('uji_tp_admin', ?, 'Admin', 'Uji', 1, '1')")->execute([password_hash($PASS, PASSWORD_BCRYPT)]);
$hapusCache();

function req(string $jar, string $method, string $url, array $data = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]);
    if ($method === 'POST') { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $data); }
    $raw = (string) curl_exec($ch);
    $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $r = ['kode' => curl_getinfo($ch, CURLINFO_RESPONSE_CODE), 'head' => substr($raw, 0, $hs), 'body' => substr($raw, $hs)];
    preg_match('/^location:\s*(\S+)/mi', $r['head'], $m);
    $r['lokasi'] = $m[1] ?? '';
    return $r;
}
function token(string $html): string { preg_match('/name="_token" value="([a-f0-9]{64})"/', $html, $m); return $m[1] ?? ''; }
$gagal = 0;
$cek = function (string $label, bool $ok, string $info = '') use (&$gagal): void {
    if (!$ok) $gagal++;
    printf("%s %-74s %s\n", $ok ? 'OK  ' : 'GAGAL', $label, $info);
};
$teks = fn (string $h) => preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($h)));
$jA = "$tmp/tp-admin.txt"; $jP = "$tmp/tp-publik.txt"; @unlink($jA); @unlink($jP);
$isianPilar = fn (array $tambah) => $tambah + ['label' => $pilarAwal['label'], 'kategori_program' => $pilarAwal['kategori_program'], 'deskripsi' => (string) $pilarAwal['deskripsi'], 'urutan' => (string) $pilarAwal['urutan']];

try {
    $f = req($jA, 'GET', "$base/admin/masuk");
    req($jA, 'POST', "$base/admin/masuk", ['_token' => token($f['body']), 'username' => 'uji_tp_admin', 'password' => $PASS]);

    echo "== 5 Pilar: warna & transparansi kotak ikon\n";
    $u = req($jA, 'GET', "$base/admin/pilar/1/ubah");
    $cek('form pilar: pemilih warna, centang bawaan, penggeser 0–100, pratinjau', $u['kode'] === 200 && str_contains($u['body'], 'type="color" id="f-warna_latar"')
        && str_contains($u['body'], 'name="bawaan_warna_latar"') && preg_match('/type="range"[^>]*name="opasitas_latar"[^>]*min="0" max="100"/', $u['body']) === 1 && str_contains($u['body'], 'data-pratinjau-warna="warna_latar"'));
    $r = req($jA, 'POST', "$base/admin/pilar/1", $isianPilar(['_token' => token($u['body']), 'warna_latar' => '#FACC15', 'opasitas_latar' => '30']));
    $p = $pdo->query('SELECT warna_latar, opasitas_latar FROM kategori_program WHERE id_kategori_program = 1')->fetch();
    $cek('simpan warna #FACC15, 30% → tersimpan huruf kecil', $r['kode'] === 303 && $p['warna_latar'] === '#facc15' && (int) $p['opasitas_latar'] === 30, $r['lokasi']);
    $h = req($jP, 'GET', "$base/")['body'];
    $cek('beranda: kotak ikon pilar memakai rgb(250 204 21 / 30%)', str_contains($h, 'background-color: rgb(250 204 21 / 30%)'));
    $cek('/program juga memakai warna yang sama', str_contains(req($jP, 'GET', "$base/program")['body'], 'rgb(250 204 21 / 30%)'));
    $u = req($jA, 'GET', "$base/admin/pilar/1/ubah");
    $cek('form ubah menampilkan warna & 30% tersimpan, centang bawaan tidak tercentang', str_contains($u['body'], 'value="#facc15"') && str_contains($u['body'], '>30%</output>')
        && !preg_match('/name="bawaan_warna_latar" value="1"[^>]*checked/', $u['body']));
    $r = req($jA, 'POST', "$base/admin/pilar/1", $isianPilar(['_token' => token($u['body']), 'warna_latar' => 'merah', 'opasitas_latar' => '30']));
    $cek('warna tidak valid ditolak', str_contains($teks(req($jA, 'GET', "$base/admin/pilar/1/ubah")['body']), 'Format Warna kotak ikon tidak valid'));
    $r = req($jA, 'POST', "$base/admin/pilar/1", $isianPilar(['_token' => token(req($jA, 'GET', "$base/admin/pilar/1/ubah")['body']), 'warna_latar' => '#123456', 'opasitas_latar' => '150']));
    $cek('kepekatan > 100 ditolak', str_contains($teks(req($jA, 'GET', "$base/admin/pilar/1/ubah")['body']), 'harus antara 0 dan 100'));
    $u = req($jA, 'GET', "$base/admin/pilar/1/ubah");
    $r = req($jA, 'POST', "$base/admin/pilar/1", $isianPilar(['_token' => token($u['body']), 'warna_latar' => '#123456', 'bawaan_warna_latar' => '1', 'opasitas_latar' => '100']));
    $p = $pdo->query('SELECT warna_latar FROM kategori_program WHERE id_kategori_program = 1')->fetch();
    $cek('centang "Pakai warna bawaan" → warna NULL, situs kembali ke bawaan', $r['kode'] === 303 && $p['warna_latar'] === null && !str_contains(req($jP, 'GET', "$base/")['body'], 'rgb(18 52 86'));

    echo "\n== Ikon Navigasi bawah\n";
    $l = req($jA, 'GET', "$base/admin/ikon-navigasi");
    $cek('menu Tampilan → Ikon Navigasi: 5 item, tanpa tombol tambah/hapus', $l['kode'] === 200 && substr_count($l['body'], '/admin/ikon-navigasi/') >= 5
        && !str_contains($l['body'], '/admin/ikon-navigasi/baru') && !str_contains($l['body'], '/hapus"') && str_contains($l['body'], 'href="' . $base . '/admin/ikon-navigasi"'));
    $cek('/admin/ikon-navigasi/baru → 404 (tidak bisa menambah item)', req($jA, 'GET', "$base/admin/ikon-navigasi/baru")['kode'] === 404);
    $idProgram = (int) $pdo->query("SELECT id FROM ikon_nav WHERE kunci = 'program'")->fetchColumn();
    $g = imagecreatetruecolor(240, 240); imagesavealpha($g, true); imagefill($g, 0, 0, imagecolorallocatealpha($g, 0, 0, 0, 127));
    imagefilledellipse($g, 120, 120, 200, 200, imagecolorallocate($g, 235, 104, 52)); imagepng($g, "$tmp/ikon-uji.png");
    $u = req($jA, 'GET', "$base/admin/ikon-navigasi/$idProgram/ubah");
    $r = req($jA, 'POST', "$base/admin/ikon-navigasi/$idProgram", ['_token' => token($u['body']), 'label' => 'Program Kami', 'ikon' => new CURLFile("$tmp/ikon-uji.png", 'image/png', 'ikon.png')]);
    $ikon = (string) $pdo->query("SELECT ikon FROM ikon_nav WHERE id = $idProgram")->fetchColumn();
    $cek('unggah ikon + ganti label "Program Kami" → tersimpan di uploads/ikon-nav', $r['kode'] === 303 && $ikon !== '' && is_file("$proyek/public/uploads/ikon-nav/$ikon"), $r['lokasi']);
    $h = req($jP, 'GET', "$base/")['body'];
    preg_match('#<nav aria-label="Navigasi bawah".*?</nav>#s', $h, $nav);
    $nav = $nav[0] ?? '';
    $cek('bottom nav: label baru & gambar unggahan tampil (cache site langsung terhapus)', str_contains($nav, 'Program Kami') && str_contains($nav, 'uploads/ikon-nav/' . substr($ikon, 0, -4)));
    $cek('di beranda ikon Program tidak aktif → keabu-abuan (grayscale), berwarna saat hover', preg_match('#<img[^>]*ikon-nav[^>]*class="[^"]*grayscale[^"]*group-hover:grayscale-0#', $nav) === 1);
    preg_match('#<nav aria-label="Navigasi bawah".*?</nav>#s', req($jP, 'GET', "$base/program")['body'], $navProgram);
    $cek('di /program ikon Program aktif → berwarna penuh + animasi hover', preg_match('#<img[^>]*ikon-nav[^>]*class="(?![^"]*grayscale)[^"]*group-hover:scale-115#', $navProgram[0] ?? '') === 1);
    $cek('ikon lain (Beranda, Kabar, Menu) tetap SVG bawaan', substr_count($nav, '<svg class="') >= 4);
    $u = req($jA, 'GET', "$base/admin/ikon-navigasi/$idProgram/ubah");
    $r = req($jA, 'POST', "$base/admin/ikon-navigasi/$idProgram", ['_token' => token($u['body']), 'label' => 'Label Terlalu Panjang Sekali']);
    $cek('label > 14 huruf ditolak', str_contains($teks(req($jA, 'GET', "$base/admin/ikon-navigasi/$idProgram/ubah")['body']), 'Label maksimal 14 karakter'));
    $u = req($jA, 'GET', "$base/admin/ikon-navigasi/$idProgram/ubah");
    $r = req($jA, 'POST', "$base/admin/ikon-navigasi/$idProgram", ['_token' => token($u['body']), 'label' => 'Program', 'hapus_ikon' => '1']);
    $sisa = $pdo->query("SELECT ikon FROM ikon_nav WHERE id = $idProgram")->fetchColumn();
    preg_match('#<nav aria-label="Navigasi bawah".*?</nav>#s', req($jP, 'GET', "$base/")['body'], $nav);
    $cek('hapus gambar → kembali ke SVG bawaan, berkas & varian terhapus', $r['kode'] === 303 && $sisa === null && !str_contains($nav[0] ?? '', 'ikon-nav/')
        && glob("$proyek/public/uploads/ikon-nav/" . substr($ikon, 0, -4) . '*') === []);

    echo "\n== Akses\n";
    $pdo->exec("UPDATE user SET level = 3 WHERE username = 'uji_tp_admin'");
    @unlink($jA); $f = req($jA, 'GET', "$base/admin/masuk");
    req($jA, 'POST', "$base/admin/masuk", ['_token' => token($f['body']), 'username' => 'uji_tp_admin', 'password' => $PASS]);
    $cek('Kepala: /admin/ikon-navigasi → 403', req($jA, 'GET', "$base/admin/ikon-navigasi")['kode'] === 403);
} finally {
    $st = $pdo->prepare('UPDATE kategori_program SET warna_latar = ?, opasitas_latar = ?, label = ?, kategori_program = ?, deskripsi = ?, urutan = ? WHERE id_kategori_program = 1');
    $st->execute([$pilarAwal['warna_latar'], $pilarAwal['opasitas_latar'], $pilarAwal['label'], $pilarAwal['kategori_program'], $pilarAwal['deskripsi'], $pilarAwal['urutan']]);
    $st = $pdo->prepare('UPDATE ikon_nav SET label = ?, ikon = ? WHERE id = ?');
    foreach ($navAwal as $n) { $st->execute([$n['label'], $n['ikon'], $n['id']]); }
    $pdo->exec("DELETE FROM user WHERE username = 'uji_tp_admin'");
    $dipakai = array_filter(array_column($navAwal, 'ikon'));
    foreach (glob("$proyek/public/uploads/ikon-nav/*") ?: [] as $berkas) {
        if (!array_filter($dipakai, static fn ($x) => str_starts_with(basename($berkas), substr((string) $x, 0, -4)))) unlink($berkas);
    }
    $hapusCache();
    foreach ([$jA, $jP, "$tmp/ikon-uji.png"] as $x) @unlink($x);
}
$sekarang = $pdo->query('SELECT warna_latar, opasitas_latar FROM kategori_program WHERE id_kategori_program = 1')->fetch();
$cek('data pilar & ikon navigasi kembali seperti semula', $sekarang['warna_latar'] === $pilarAwal['warna_latar'] && $pdo->query('SELECT label, ikon FROM ikon_nav ORDER BY id')->fetchAll() == array_map(fn ($n) => ['label' => $n['label'], 'ikon' => $n['ikon']], $navAwal));
printf("\n%s — %d gagal\n", $gagal === 0 ? 'SEMUA LULUS' : 'ADA YANG GAGAL', $gagal);
exit($gagal === 0 ? 0 : 1);
