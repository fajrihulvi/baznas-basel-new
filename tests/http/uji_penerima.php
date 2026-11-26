<?php
// Uji menu Penerima Manfaat + laporan di dasbor & /tentang/sistem-pelaporan-zakat. Pemakaian: php tests/http/uji_penerima.php http://localhost:8002
// Data uji memakai tahun 2016; halaman uji dibuat hanya bila belum ada. Semuanya dihapus di akhir.
declare(strict_types=1);
date_default_timezone_set('Asia/Jakarta');

$base = rtrim($argv[1] ?? 'http://localhost:8002', '/');
$proyek = dirname(__DIR__, 2);
$tmp = dirname(__DIR__) . '/tmp';
@mkdir($tmp);
$pdo = new PDO('mysql:host=127.0.0.1;dbname=baznas_basel;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$hapusCache = function () use ($proyek): void { foreach ([...(glob("$proyek/storage/cache/rl-*.json") ?: []), ...(glob("$proyek/storage/cache/*.cache") ?: [])] as $f) unlink($f); };
$hapusCache();

$PASS = 'Uji-Penerima-' . bin2hex(random_bytes(3));
$SLUG = 'sistem-pelaporan-zakat';
$bersihkan = function () use ($pdo): void {
    $pdo->exec("DELETE FROM penerima_manfaat WHERE YEAR(periode) = 2016");
    $pdo->exec("DELETE FROM user WHERE username LIKE 'uji\\_pm\\_%'");
    $pdo->exec("DELETE FROM halaman WHERE konten = '<p>Isi uji UJI-PM.</p>'");
};
$bersihkan();
$jumlahAwal = (int) $pdo->query('SELECT COUNT(*) FROM penerima_manfaat')->fetchColumn();
$ins = $pdo->prepare("INSERT INTO user (username, password, f_name, l_name, level, status) VALUES (?, ?, ?, 'Uji', ?, '1')");
foreach (['admin' => 1, 'operator' => 2, 'kepala' => 3] as $n => $lv) $ins->execute(["uji_pm_$n", password_hash($PASS, PASSWORD_BCRYPT), ucfirst($n), $lv]);
if (!$pdo->query("SELECT 1 FROM halaman WHERE slug = '$SLUG'")->fetchColumn()) {
    $pdo->exec("INSERT INTO halaman (grup, judul, slug, konten, urutan, publish) VALUES ('tentang', 'Sistem Pelaporan Zakat', '$SLUG', '<p>Isi uji UJI-PM.</p>', 99, 'Y')");
}

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
function masuk(string $base, string $jar, string $u, string $p): void
{
    @unlink($jar);
    $f = req($jar, 'GET', "$base/admin/masuk");
    req($jar, 'POST', "$base/admin/masuk", ['_token' => token($f['body']), 'username' => $u, 'password' => $p]);
}
$gagal = 0;
$cek = function (string $label, bool $ok, string $info = '') use (&$gagal): void {
    if (!$ok) $gagal++;
    printf("%s %-82s %s\n", $ok ? 'OK  ' : 'GAGAL', $label, $info);
};

try {
    $jar = "$tmp/pm-admin.txt"; $jp = "$tmp/pm-publik.txt"; @unlink($jp);
    masuk($base, $jar, 'uji_pm_admin', $PASS);
    $simpan = function (array $isi, string $ke = '') use ($base, $jar): array {
        $f = req($jar, 'GET', "$base/admin/penerima-manfaat" . ($ke === '' ? '/baru' : "/$ke/ubah"));
        $r = req($jar, 'POST', "$base/admin/penerima-manfaat" . ($ke === '' ? '' : "/$ke"), ['_token' => token($f['body'])] + $isi);
        $r['akhir'] = req($jar, 'GET', $r['lokasi'] ?: "$base/admin/penerima-manfaat")['body'];
        return $r;
    };
    $contoh = ['peduli' => '1590', 'sehat' => '143', 'makmur' => '177', 'cerdas' => '200', 'taqwa' => '35'];

    echo "== Form & validasi\n";
    $das = req($jar, 'GET', "$base/admin")['body'];
    $cek('menu Penyaluran memuat Penerima Manfaat', str_contains($das, '/admin/penerima-manfaat"') && str_contains($das, 'Penerima Manfaat'));
    $f = req($jar, 'GET', "$base/admin/penerima-manfaat/baru")['body'];
    $cek('form: 5 isian pilar + persen otomatis + total jiwa', substr_count($f, 'data-persen=') === 5 && str_contains($f, 'data-satuan="jiwa"') && str_contains($f, 'name="periode_bulan"'));
    $cek('semua kosong ditolak', str_contains($simpan(['periode_bulan' => '1', 'periode_tahun' => '2016'])['akhir'], 'minimal untuk satu pilar'));
    $cek('angka negatif ditolak', str_contains($simpan(['periode_bulan' => '1', 'periode_tahun' => '2016', 'peduli' => '-5', 'sehat' => '3'])['akhir'], 'harus berupa angka'));
    $depan = new DateTimeImmutable('first day of next month');
    $cek('bulan mendatang ditolak', str_contains($simpan(['periode_bulan' => $depan->format('n'), 'periode_tahun' => $depan->format('Y')] + $contoh)['akhir'], 'belum berjalan'));
    $cek('tanpa bulan ditolak', str_contains($simpan($contoh)['akhir'], 'wajib diisi'));

    echo "== Simpan, persentase, akumulasi\n";
    $r = $simpan(['periode_bulan' => '1', 'periode_tahun' => '2016', 'keterangan' => 'RAHASIA-PM', 'petugas' => 'PETUGAS-PM'] + $contoh);
    $b = $pdo->query("SELECT * FROM penerima_manfaat WHERE periode = '2016-01-01'")->fetch();
    $cek('5 pilar sekaligus tersimpan, total dihitung server (2145)', $r['kode'] === 303 && $b && (int) $b['total'] === 2145 && (int) $b['taqwa'] === 35 && $b['penghimpunan'] === null);
    $cek('daftar: jumlah + persentase per bulan', str_contains($r['akhir'], '74,13%') && str_contains($r['akhir'], '6,67%') && str_contains($r['akhir'], '8,25%') && str_contains($r['akhir'], '9,32%') && str_contains($r['akhir'], '1,63%'));
    $cek('bulan yang sama ditolak (satu data per bulan)', str_contains($simpan(['periode_bulan' => '1', 'periode_tahun' => '2016'] + $contoh)['akhir'], 'sudah ada'));
    $simpan(['periode_bulan' => '10', 'periode_tahun' => '2016', 'peduli' => '10', 'penghimpunan' => '1.000.000']);
    $cek('pilar kosong disimpan 0', (int) $pdo->query("SELECT sehat FROM penerima_manfaat WHERE periode = '2016-10-01'")->fetchColumn() === 0);

    $pub = req($jp, 'GET', "$base/tentang/$SLUG?pm_tahun=2016");
    $h = $pub['body'];
    $cek('publik: laporan di bawah isi halaman, periode & akumulasi (2.155 jiwa)', $pub['kode'] === 200 && strpos($h, 'id="penerima-manfaat"') > strpos($h, 'class="konten text-ink"')
        && str_contains($h, 'Januari – Oktober 2016') && str_contains($h, '2.155 Jiwa') && str_contains($h, '1.600'));
    $cek('publik: donat 5 irisan, tabel, rincian per bulan, grafik.js', substr_count($h, 'grafik-sentuh') === 5 && str_contains($h, 'Program Kemanusiaan') && str_contains($h, 'Rincian per bulan') && str_contains($h, 'js/grafik.js'));
    $cek('publik: total penghimpunan tidak tampil bila belum semua bulan diisi', !str_contains($h, 'Total Penghimpunan'));
    $cek('publik: keterangan & petugas internal tidak bocor', !str_contains($h, 'RAHASIA-PM') && !str_contains($h, 'PETUGAS-PM'));
    $cek('publik: tanpa cookie sesi', !str_contains(strtolower($pub['head']), 'set-cookie'));
    $satu = req($jp, 'GET', "$base/tentang/$SLUG?pm_tahun=2016&pm_bulan=1")['body'];
    $cek('publik: filter satu bulan (Januari: 2.145 jiwa, 74,13%)', str_contains($satu, '2.145 Jiwa') && str_contains($satu, '74,13%') && !str_contains($satu, 'Januari – Oktober'));
    $cek('publik: parameter ngawur tidak error', req($jp, 'GET', "$base/tentang/$SLUG?pm_tahun[]=x&pm_bulan=99'")['kode'] === 200);
    $cek('halaman Tentang lain tidak memuat laporan', !str_contains(req($jp, 'GET', "$base/tentang/profil")['body'], 'id="penerima-manfaat"'));
    $penuh = req($jp, 'GET', "$base/tentang/$SLUG?pm_tahun=2016&pm_bulan=10")['body'];
    $cek('publik: satu pilar 100% & penghimpunan lengkap tampil', str_contains($penuh, '100,00%') && str_contains($penuh, 'Total Penghimpunan') && str_contains($penuh, 'Rp1.000.000'));

    $simpan(['periode_bulan' => '1', 'periode_tahun' => '2016', 'penghimpunan' => '2.583.197.812'] + $contoh, (string) $b['id']);
    $cek('ubah data bulan sendiri boleh; penghimpunan akumulasi Rp2.584.197.812', str_contains(req($jp, 'GET', "$base/tentang/$SLUG?pm_tahun=2016")['body'], 'Rp2.584.197.812'));

    echo "== Dasbor & peran\n";
    $das = req($jar, 'GET', "$base/admin?pm_tahun=2016&pg_tahun=2016&periode=tahun_ini")['body'];
    $cek('dasbor admin memuat laporan', str_contains($das, 'id="dasbor-penerima-donat"') && str_contains($das, '2.155 Jiwa'));
    $cek('filter dasbor saling membawa parameter', substr_count($das, 'name="pm_tahun" value="2016"') === 2 && substr_count($das, 'name="pg_tahun" value="2016"') === 2);
    $jk = "$tmp/pm-kepala.txt"; masuk($base, $jk, 'uji_pm_kepala', $PASS);
    $dk = req($jk, 'GET', "$base/admin?pm_tahun=2016")['body'];
    $cek('Kepala: laporan tampil di dasbor tanpa tombol catat', str_contains($dk, '2.155 Jiwa') && !str_contains($dk, '/admin/penerima-manfaat'));
    $cek('Kepala: menu input ditolak (403)', req($jk, 'GET', "$base/admin/penerima-manfaat")['kode'] === 403);
    $jo = "$tmp/pm-operator.txt"; masuk($base, $jo, 'uji_pm_operator', $PASS);
    $cek('Operator: bisa membuka menu', req($jo, 'GET', "$base/admin/penerima-manfaat")['kode'] === 200);
    $cek('cari menurut tahun-bulan', str_contains(req($jar, 'GET', "$base/admin/penerima-manfaat?q=2016-10")['body'], 'Oktober 2016') && !str_contains(req($jar, 'GET', "$base/admin/penerima-manfaat?q=2016-10")['body'], 'Januari 2016'));

    $t = token(req($jar, 'GET', "$base/admin/penerima-manfaat")['body']);
    req($jar, 'POST', "$base/admin/penerima-manfaat/{$b['id']}/hapus", ['_token' => $t]);
    $cek('hapus', !$pdo->query("SELECT 1 FROM penerima_manfaat WHERE periode = '2016-01-01'")->fetchColumn());
} finally {
    $bersihkan();
    $hapusCache();
}
$cek('data kembali seperti semula', (int) $pdo->query('SELECT COUNT(*) FROM penerima_manfaat')->fetchColumn() === $jumlahAwal);
echo $gagal === 0 ? "\nSEMUA LULUS — 0 gagal\n" : "\nADA YANG GAGAL — $gagal gagal\n";
exit($gagal === 0 ? 0 : 1);
