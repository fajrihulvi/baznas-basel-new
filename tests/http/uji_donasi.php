<?php
// Uji banner donasi + halaman donasi + formulir donatur + menu Donasi Bantuan. Pemakaian: php tests/http/uji_donasi.php http://localhost:8002
// Banner uji berjudul "UJI-DONASI …"; semua data & file uji dihapus di akhir.
declare(strict_types=1);
date_default_timezone_set('Asia/Jakarta');

$base = rtrim($argv[1] ?? 'http://localhost:8002', '/');
$proyek = dirname(__DIR__, 2);
$tmp = dirname(__DIR__) . '/tmp';
@mkdir($tmp);
$pdo = new PDO('mysql:host=127.0.0.1;dbname=baznas_basel;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$hapusCache = function () use ($proyek): void { foreach ([...(glob("$proyek/storage/cache/rl-*.json") ?: []), ...(glob("$proyek/storage/cache/*.cache") ?: [])] as $f) unlink($f); };
$hapusCache();
$PASS = 'Uji-Donasi-' . bin2hex(random_bytes(3));
$bersihkan = function () use ($pdo, $proyek): void {
    foreach ($pdo->query("SELECT foto FROM banner WHERE judul LIKE 'UJI-DONASI%'")->fetchAll(PDO::FETCH_COLUMN) as $f) {
        foreach (glob("$proyek/public/uploads/banner/" . pathinfo((string) $f, PATHINFO_FILENAME) . '*') ?: [] as $x) unlink($x);
    }
    $pdo->exec("DELETE d FROM donasi d JOIN banner b ON b.id_banner = d.id_banner WHERE b.judul LIKE 'UJI-DONASI%'");
    $pdo->exec("DELETE FROM banner WHERE judul LIKE 'UJI-DONASI%'");
    $pdo->exec("DELETE FROM user WHERE username LIKE 'uji\\_dn\\_%'");
};
$bersihkan();
$awal = ['banner' => (int) $pdo->query('SELECT COUNT(*) FROM banner')->fetchColumn(), 'donasi' => (int) $pdo->query('SELECT COUNT(*) FROM donasi')->fetchColumn()];
$ins = $pdo->prepare("INSERT INTO user (username, password, f_name, l_name, level, status) VALUES (?, ?, ?, 'Uji', ?, '1')");
foreach (['admin' => 1, 'operator' => 2, 'kepala' => 3] as $n => $lv) $ins->execute(["uji_dn_$n", password_hash($PASS, PASSWORD_BCRYPT), 'Petugas ' . $n, $lv]);

function req(string $jar, string $method, string $url, array $data = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]);
    if ($method === 'POST') { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, array_filter($data, fn ($x) => $x instanceof CURLFile) ? $data : http_build_query($data)); }
    $raw = (string) curl_exec($ch);
    $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $r = ['kode' => curl_getinfo($ch, CURLINFO_RESPONSE_CODE), 'body' => substr($raw, $hs), 'head' => substr($raw, 0, $hs)];
    preg_match('/^location:\s*(\S+)/mi', $r['head'], $m);
    $r['lokasi'] = $m[1] ?? '';
    return $r;
}
function token(string $html): string { preg_match('/name="_token" value="([a-f0-9]{64})"/', $html, $m); return $m[1] ?? ''; }
function masuk(string $base, string $jar, string $u, string $p): void
{
    @unlink($jar);
    $f = req($jar, 'GET', "$base/admin/masuk");
    req($jar, 'POST', "$base/admin/masuk", ['_token' => token($f['body']), 'username' => $u, 'password' => $p]);
}
$gagal = 0;
$cek = function (string $label, bool $ok, string $info = '') use (&$gagal): void {
    if (!$ok) $gagal++;
    printf("%s %-84s %s\n", $ok ? 'OK  ' : 'GAGAL', $label, $info);
};

try {
    $jar = "$tmp/dn-admin.txt"; masuk($base, $jar, 'uji_dn_admin', $PASS);
    $foto = glob("$proyek/public/uploads/post/*.jpg")[0];
    $hariIni = new DateTimeImmutable('today');
    $isiBanner = fn (array $x = []): array => $x + ['judul' => 'UJI-DONASI Banjir', 'tgl' => $hariIni->format('Y-m-d'), 'publish' => '1', 'urutan' => '0',
        'donasi' => '1', 'donasi_nama' => 'Bantuan Uji Donasi Banjir', 'donasi_mulai' => $hariIni->modify('-1 day')->format('Y-m-d'),
        'donasi_selesai' => $hariIni->modify('+30 day')->format('Y-m-d'), 'donasi_target' => '1.000.000', 'donasi_minimal' => '10.000',
        'donasi_deskripsi' => '<p>Deskripsi <strong>uji</strong> donasi.</p><script>alert(1)</script>', 'foto' => new CURLFile($foto)];

    echo "== Admin banner donasi\n";
    $f = req($jar, 'GET', "$base/admin/banner/baru")['body'];
    $cek('form banner: centang donasi + grup detail (tampil_jika)', str_contains($f, 'name="donasi"') && str_contains($f, 'data-tampil-jika="donasi"') && str_contains($f, 'name="donasi_target"'));
    $r = req($jar, 'POST', "$base/admin/banner", ['_token' => token($f), 'donasi_nama' => '', 'donasi_target' => ''] + $isiBanner(['donasi_nama' => '', 'donasi_target' => '']));
    $g = req($jar, 'GET', "$base/admin/banner/baru")['body'];
    $cek('banner donasi tanpa nama & target ditolak', str_contains($g, 'Nama program donasi wajib diisi') && str_contains($g, 'Target terkumpul wajib diisi'));
    $r = req($jar, 'POST', "$base/admin/banner", ['_token' => token($g)] + $isiBanner(['donasi_selesai' => $hariIni->modify('-5 day')->format('Y-m-d')]));
    $g = req($jar, 'GET', "$base/admin/banner/baru")['body'];
    $cek('tanggal ditutup sebelum dibuka ditolak', str_contains($g, 'tidak boleh sebelum tanggal dibuka'));
    $r = req($jar, 'POST', "$base/admin/banner", ['_token' => token($g)] + $isiBanner());
    $b = $pdo->query("SELECT * FROM banner WHERE judul = 'UJI-DONASI Banjir'")->fetch();
    $cek('banner donasi tersimpan: slug otomatis, deskripsi disanitasi', $r['kode'] === 303 && $b && $b['donasi_slug'] === 'bantuan-uji-donasi-banjir'
        && (int) $b['donasi_target'] === 1000000 && !str_contains((string) $b['donasi_deskripsi'], '<script'));
    $slug = $b['donasi_slug']; $id = (int) $b['id_banner'];
    $daftar = req($jar, 'GET', "$base/admin/banner")['body'];
    $cek('daftar banner: kolom Donasi + tautan Lihat ke /donasi/{slug} hanya utk banner donasi', str_contains($daftar, "/donasi/$slug\"") && substr_count($daftar, '/donasi/') === 1);
    $hapusCache();

    echo "== Halaman publik\n";
    $jp = "$tmp/dn-publik.txt"; @unlink($jp);
    $home = req($jp, 'GET', "$base/")['body'];
    $cek('beranda: banner hero menuju /donasi/{slug} & bagian Program Donasi setelah Kabar BAZNAS', str_contains($home, "href=\"$base/donasi/$slug\"")
        && strpos($home, 'id="judul-donasi"') > strpos($home, 'id="judul-kabar"') && str_contains($home, 'Bantuan Uji Donasi Banjir'));
    $cek('/donasi: daftar program', str_contains(req($jp, 'GET', "$base/donasi")['body'], 'Bantuan Uji Donasi Banjir'));
    $det = req($jp, 'GET', "$base/donasi/$slug");
    $cek('detail: terkumpul Rp0 dari 0 donatur, target, nominal cepat 20rb–80rb, tanpa cookie', $det['kode'] === 200 && str_contains($det['body'], 'Rp0</strong> terkumpul dari <strong>0</strong> donatur')
        && str_contains($det['body'], 'Rp1.000.000') && str_contains($det['body'], 'value="20000"') && str_contains($det['body'], 'value="80000"') && !str_contains(strtolower($det['head']), 'set-cookie'));
    $form = req($jp, 'GET', "$base/donasi/$slug/bayar?nominal=50.000");
    preg_match('#<form method="post".*?</form>#s', $form['body'], $isiForm);
    $cek('formulir: nominal terisi, rekening infaq saja, Hamba Allah, setuju, no-store', str_contains($form['body'], 'value="50.000"') && str_contains($form['body'], 'name="anonim"')
        && str_contains($form['body'], 'name="setuju"') && str_contains($isiForm[0] ?? '', 'INFAQ') && !str_contains($isiForm[0] ?? '', '(ZAKAT)') && str_contains($form['head'], 'no-store'));
    $rekInfaq = (int) $pdo->query("SELECT id_rekening FROM rekening WHERE nama_bank LIKE '%INFAQ%' ORDER BY id_rekening LIMIT 1")->fetchColumn();
    $rekZakat = (int) $pdo->query("SELECT id_rekening FROM rekening WHERE nama_bank LIKE '%ZAKAT%' ORDER BY id_rekening LIMIT 1")->fetchColumn();
    $kirim = function (array $x) use ($base, $jp, $slug, $rekInfaq): array {
        $f = req($jp, 'GET', "$base/donasi/$slug/bayar");
        $r = req($jp, 'POST', "$base/donasi/$slug/bayar", $x + ['_token' => token($f['body']), 'nominal' => '50.000', 'nama' => 'Donatur Uji Satu', 'no_hp' => '0812 3456 7890',
            'email' => 'uji@contoh.id', 'rekening' => (string) $rekInfaq, 'setuju' => '1', 'doa' => 'Semoga lekas pulih UJI-DOA', 'anonim' => '1']);
        $r['akhir'] = $r['lokasi'] && !str_contains($r['lokasi'], '/transfer/') ? req($jp, 'GET', $r['lokasi'])['body'] : '';
        return $r;
    };
    $cek('nominal di bawah minimal ditolak', str_contains($kirim(['nominal' => '5.000'])['akhir'], 'Minimal donasi Rp10.000'));
    $cek('tanpa persetujuan ditolak', str_contains($kirim(['setuju' => ''])['akhir'], 'Centang persetujuan'));
    $cek('email wajib & valid', str_contains($kirim(['email' => 'bukan-email'])['akhir'], 'Format Email tidak valid'));
    $cek('rekening zakat (bukan untuk donasi) ditolak', str_contains($kirim(['rekening' => (string) $rekZakat])['akhir'], 'Pilih rekening tujuan'));
    $cek('honeypot ditolak', str_contains($kirim(['website' => 'http://spam'])['akhir'], 'tidak dapat diproses'));
    $r = $kirim([]);
    preg_match('#/donasi/transfer/([A-Z0-9]{8})#', $r['lokasi'], $m);
    $kode = $m[1] ?? '';
    $row = $pdo->query("SELECT * FROM donasi WHERE kode = '$kode'")->fetch();
    $cek('donasi tersimpan (menunggu, HP dinormalisasi, rekening disalin) → instruksi', $r['kode'] === 303 && $row && $row['status'] === 'menunggu' && $row['no_hp'] === '081234567890'
        && (int) $row['nominal'] === 50000 && (int) $row['anonim'] === 1 && str_contains((string) $row['bank_tujuan'], 'INFAQ'));
    $ins2 = req($jp, 'GET', "$base/donasi/transfer/$kode");
    $cek('instruksi: nominal, kode, rekening, tombol WhatsApp berisi kode, noindex & no-store', $ins2['kode'] === 200 && str_contains($ins2['body'], 'Rp50.000') && str_contains($ins2['body'], 'https://wa.me/' . preg_replace('/\D/', '', (string) $pdo->query("SELECT nilai FROM setting WHERE kunci='whatsapp'")->fetchColumn()))
        && str_contains($ins2['body'], rawurlencode($kode)) && str_contains($ins2['head'], 'no-store') && str_contains($ins2['body'], 'noindex'));
    $cek('kode tidak dikenal → 404', req($jp, 'GET', "$base/donasi/transfer/ZZZZZZZZ")['kode'] === 404 && req($jp, 'GET', "$base/donasi/transfer/abc'")['kode'] === 404);
    $hapusCache();
    $cek('belum terverifikasi → belum dihitung', str_contains(req($jp, 'GET', "$base/donasi/$slug")['body'], 'Rp0</strong> terkumpul'));

    echo "== Admin Donasi Bantuan\n";
    $dl = req($jar, 'GET', "$base/admin/donasi")['body'];
    $cek('menu Donasi Bantuan: daftar donatur', str_contains($dl, $kode) && str_contains($dl, 'Donatur Uji Satu') && str_contains($dl, 'Menunggu verifikasi'));
    $cek('cari menurut nama program', str_contains(req($jar, 'GET', "$base/admin/donasi?q=" . rawurlencode('Uji Donasi Banjir'))['body'], $kode));
    $e = req($jar, 'GET', "$base/admin/donasi/{$row['id']}/ubah")['body'];
    req($jar, 'POST', "$base/admin/donasi/{$row['id']}", ['_token' => token($e), 'status' => 'terverifikasi', 'id_banner' => (string) $id, 'nominal' => '50.000', 'nama' => 'Donatur Uji Satu',
        'anonim' => '1', 'no_hp' => '081234567890', 'email' => 'uji@contoh.id', 'id_rekening' => (string) $rekInfaq, 'doa' => 'Semoga lekas pulih UJI-DOA', 'doa_tampil' => '1']);
    $v = $pdo->query("SELECT status, diverifikasi, petugas FROM donasi WHERE id = {$row['id']}")->fetch();
    $cek('verifikasi: status, waktu & petugas tercatat', $v['status'] === 'terverifikasi' && $v['diverifikasi'] !== null && $v['petugas'] === 'Petugas admin Uji');
    $det = req($jp, 'GET', "$base/donasi/$slug")['body'];
    $cek('terverifikasi → terkumpul Rp50.000 dari 1 donatur, 5%', str_contains($det, 'Rp50.000</strong> terkumpul dari <strong>1</strong> donatur') && str_contains($det, '5,0% dari target'));
    $cek('doa tampil sebagai Hamba Allah tanpa nominal/kontak', str_contains($det, 'Hamba Allah') && str_contains($det, 'UJI-DOA') && !str_contains($det, 'Donatur Uji Satu') && !str_contains($det, '081234567890') && !str_contains($det, 'uji@contoh.id'));
    $cek('instruksi ikut menampilkan Terverifikasi', str_contains(req($jp, 'GET', "$base/donasi/transfer/$kode")['body'], 'Donasi Terverifikasi'));
    $pdo->exec("UPDATE banner SET donasi_terkumpul_luar = 150000 WHERE id_banner = $id"); $hapusCache();
    $cek('dana di luar formulir ikut terkumpul (Rp200.000)', str_contains(req($jp, 'GET', "$base/donasi/$slug")['body'], 'Rp200.000</strong> terkumpul'));
    $pdo->exec("UPDATE donasi SET doa_tampil = 0 WHERE id = {$row['id']}");
    $cek('doa disembunyikan admin', !str_contains(req($jp, 'GET', "$base/donasi/$slug")['body'], 'UJI-DOA'));
    $f = req($jar, 'GET', "$base/admin/donasi/baru")['body'];
    req($jar, 'POST', "$base/admin/donasi", ['_token' => token($f), 'status' => 'terverifikasi', 'id_banner' => (string) $id, 'nominal' => '100.000', 'nama' => 'Donatur Tunai', 'no_hp' => '+62 811 2222 3333']);
    $tunai = $pdo->query("SELECT kode, no_hp FROM donasi WHERE nama = 'Donatur Tunai'")->fetch();
    $cek('admin menambah donatur manual: kode otomatis, HP dinormalisasi', $tunai && preg_match('/^A[0-9A-F]{7}$/', $tunai['kode']) && $tunai['no_hp'] === '081122223333');

    echo "== Status program & akses\n";
    $pdo->exec("UPDATE banner SET donasi_selesai = '" . $hariIni->modify('-1 day')->format('Y-m-d') . "' WHERE id_banner = $id"); $hapusCache();
    $det = req($jp, 'GET', "$base/donasi/$slug")['body'];
    $cek('program selesai: tanpa form, pesan ditutup, tidak di beranda', str_contains($det, 'telah ditutup') && !str_contains($det, 'data-donasi-nominal') && !str_contains(req($jp, 'GET', "$base/")['body'], 'id="judul-donasi"'));
    $cek('program selesai: formulir dialihkan, kiriman ditolak', req($jp, 'GET', "$base/donasi/$slug/bayar")['kode'] === 303);
    $pdo->exec("UPDATE banner SET donasi_mulai = '" . $hariIni->modify('+3 day')->format('Y-m-d') . "', donasi_selesai = '" . $hariIni->modify('+9 day')->format('Y-m-d') . "' WHERE id_banner = $id"); $hapusCache();
    $cek('program belum dibuka: pesan tanggal dibuka', str_contains(req($jp, 'GET', "$base/donasi/$slug")['body'], 'belum dibuka'));
    $pdo->exec("UPDATE banner SET publish = 'N' WHERE id_banner = $id"); $hapusCache();
    $cek('banner disembunyikan → halaman donasi 404', req($jp, 'GET', "$base/donasi/$slug")['kode'] === 404);
    $pdo->exec("UPDATE banner SET publish = 'Y' WHERE id_banner = $id");
    $jk = "$tmp/dn-kepala.txt"; masuk($base, $jk, 'uji_dn_kepala', $PASS);
    $cek('Kepala: menu Donasi Bantuan ditolak (403)', req($jk, 'GET', "$base/admin/donasi")['kode'] === 403);
    $jo = "$tmp/dn-operator.txt"; masuk($base, $jo, 'uji_dn_operator', $PASS);
    $cek('Operator: bisa membuka Donasi Bantuan', req($jo, 'GET', "$base/admin/donasi")['kode'] === 200);
    $t = token(req($jar, 'GET', "$base/admin/banner")['body']);
    req($jar, 'POST', "$base/admin/banner/$id/hapus", ['_token' => $t]);
    $cek('banner yang punya donatur tidak bisa dihapus (pesan ramah)', (bool) $pdo->query("SELECT 1 FROM banner WHERE id_banner = $id")->fetchColumn()
        && str_contains(req($jar, 'GET', "$base/admin/banner")['body'], 'masih dipakai data lain'));
} finally {
    $bersihkan();
    $hapusCache();
}
$cek('data kembali seperti semula', (int) $pdo->query('SELECT COUNT(*) FROM banner')->fetchColumn() === $awal['banner'] && (int) $pdo->query('SELECT COUNT(*) FROM donasi')->fetchColumn() === $awal['donasi']);
echo $gagal === 0 ? "\nSEMUA LULUS — 0 gagal\n" : "\nADA YANG GAGAL — $gagal gagal\n";
exit($gagal === 0 ? 0 : 1);
