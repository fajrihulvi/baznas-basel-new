<?php
// Uji menu Pengumpulan & Perangkat Daerah (CRUD) + statistik pengumpulan di dasbor. Pemakaian: php tests/http/uji_pengumpulan.php http://localhost:8002
// Data uji memakai tahun 2016 & PD bernama "UJI-PG …" agar tidak tercampur data asli; semuanya dihapus di akhir.
declare(strict_types=1);
date_default_timezone_set('Asia/Jakarta'); // sama dengan config/app.php (bulan bawaan form)

$base = rtrim($argv[1] ?? 'http://localhost:8002', '/');
$proyek = dirname(__DIR__, 2);
$tmp = dirname(__DIR__) . '/tmp';
@mkdir($tmp);
$pdo = new PDO('mysql:host=127.0.0.1;dbname=baznas_basel;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$hapusRl = function () use ($proyek): void { foreach (glob("$proyek/storage/cache/rl-*.json") ?: [] as $f) unlink($f); };
$hapusRl();

$ADMIN = 'uji_pg_admin'; $KEPALA = 'uji_pg_kepala'; $PASS = 'Uji-Pengumpulan-' . bin2hex(random_bytes(3));
$bersihkan = function () use ($pdo): void {
    $pdo->exec("DELETE p FROM pengumpulan p JOIN perangkat_daerah d ON d.id = p.id_perangkat_daerah WHERE d.nama LIKE 'UJI-PG %'");
    $pdo->exec("DELETE FROM perangkat_daerah WHERE nama LIKE 'UJI-PG %'");
    $pdo->exec("DELETE FROM user WHERE username LIKE 'uji\\_pg\\_%'");
};
$bersihkan();
$jumlahAwal = (int) $pdo->query('SELECT COUNT(*) FROM pengumpulan')->fetchColumn();
$pdAwal = (int) $pdo->query('SELECT COUNT(*) FROM perangkat_daerah')->fetchColumn();
$ins = $pdo->prepare("INSERT INTO user (username, password, f_name, l_name, level, status) VALUES (?, ?, ?, 'Uji', ?, '1')");
$ins->execute([$ADMIN, password_hash($PASS, PASSWORD_BCRYPT), 'Petugas', 1]);
$ins->execute([$KEPALA, password_hash($PASS, PASSWORD_BCRYPT), 'Kepala', 3]);

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
    printf("%s %-74s %s\n", $ok ? 'OK  ' : 'GAGAL', $label, $info);
};
$teks = fn (string $html) => preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html)));
$jA = "$tmp/pg-admin.txt"; $jK = "$tmp/pg-kepala.txt";
$satu = fn (string $sql) => $pdo->query($sql)->fetch();

try {
    masuk($base, $jA, $ADMIN, $PASS);

    echo "== Perangkat Daerah\n";
    $cek('data awal 34 PD (OPD + 8 kecamatan) dari migrasi 012', (int) $pdo->query("SELECT COUNT(*) FROM perangkat_daerah WHERE nama NOT LIKE 'UJI-PG %'")->fetchColumn() >= 34
        && $satu("SELECT id FROM perangkat_daerah WHERE nama = 'Dinas Komunikasi dan Informatika' AND singkatan = 'Diskominfo'") !== false);
    $simpanPd = function (array $isian, ?int $id = null) use ($jA, $base): array {
        $f = req($jA, 'GET', $id === null ? "$base/admin/perangkat-daerah/baru" : "$base/admin/perangkat-daerah/$id/ubah");
        return req($jA, 'POST', $id === null ? "$base/admin/perangkat-daerah" : "$base/admin/perangkat-daerah/$id", ['_token' => token($f['body'])] + $isian);
    };
    $f = req($jA, 'GET', "$base/admin/perangkat-daerah/baru");
    $cek('form PD baru: "Aktif" tercentang bawaan', $f['kode'] === 200 && (bool) preg_match('/name="aktif" value="1"[^>]*checked/', $f['body']));
    $r = $simpanPd(['nama' => '  UJI-PG  Dinas   Alfa ', 'singkatan' => 'UJI-A', 'urutan' => '90', 'aktif' => '1']);
    $pdA = $satu("SELECT * FROM perangkat_daerah WHERE singkatan = 'UJI-A'");
    $cek('PD tersimpan, spasi berlebih dirapikan', $r['kode'] === 303 && $pdA && $pdA['nama'] === 'UJI-PG Dinas Alfa' && (int) $pdA['aktif'] === 1);
    $simpanPd(['nama' => 'UJI-PG Dinas Beta', 'singkatan' => 'UJI-B', 'urutan' => '91', 'aktif' => '1']);
    $simpanPd(['nama' => 'UJI-PG Dinas Lama', 'singkatan' => 'UJI-L', 'urutan' => '92']); // tanpa centang → nonaktif
    $pdB = $satu("SELECT * FROM perangkat_daerah WHERE singkatan = 'UJI-B'");
    $pdL = $satu("SELECT * FROM perangkat_daerah WHERE singkatan = 'UJI-L'");
    $cek('PD tanpa centang Aktif → nonaktif', $pdL && (int) $pdL['aktif'] === 0);
    $r = $simpanPd(['nama' => 'uji-pg dinas alfa', 'singkatan' => 'X', 'aktif' => '1']);
    $g = req($jA, 'GET', "$base/admin/perangkat-daerah/baru");
    $cek('nama PD kembar ditolak (bukan error 500)', $r['kode'] === 303 && str_ends_with($r['lokasi'], '/baru') && str_contains($teks($g['body']), 'nama ini sudah ada'));
    $aktif = (int) $pdo->query('SELECT COUNT(*) FROM perangkat_daerah WHERE aktif = 1')->fetchColumn();

    echo "\n== Form pengumpulan\n";
    $d = req($jA, 'GET', "$base/admin");
    $cek('menu memuat Pengumpulan & Perangkat Daerah', str_contains($d['body'], 'href="' . $base . '/admin/pengumpulan"') && str_contains($d['body'], 'href="' . $base . '/admin/perangkat-daerah"'));
    $f = req($jA, 'GET', "$base/admin/pengumpulan/baru");
    $cek('form: pilih bulan + tahun (bawaan bulan ini), tanggal, PD, petugas', $f['kode'] === 200 && str_contains($f['body'], 'name="periode_bulan"') && str_contains($f['body'], 'name="periode_tahun"')
        && str_contains($f['body'], '<option value="' . (int) date('n') . '" selected>') && str_contains($f['body'], '<option value="' . date('Y') . '" selected>')
        && str_contains($f['body'], 'Dinas Komunikasi dan Informatika') && str_contains($f['body'], 'UJI-PG Dinas Lama (nonaktif)') && str_contains($f['body'], 'value="Petugas Uji"'));
    $cek('form: 3 nominal rupiah dalam satu kelompok + total otomatis', substr_count($f['body'], 'data-rupiah') === 3 && str_contains($f['body'], '<legend')
        && str_contains($f['body'], 'Jenis pengumpulan &amp; nominal') && str_contains($f['body'], 'data-jumlah="zakat infaq dskl"'));

    echo "\n== Validasi\n";
    $isian = ['periode_bulan' => '3', 'periode_tahun' => '2016', 'tgl' => '2016-03-05', 'id_perangkat_daerah' => $pdA['id'],
        'zakat' => 'Rp1.500.000', 'infaq' => '250.000', 'dskl' => '', 'keterangan' => 'Uji', 'petugas' => 'Petugas Uji'];
    $tolak = function (string $label, array $ubah, string $pesan) use ($jA, $base, $isian, $cek, $teks): void {
        $t = token(req($jA, 'GET', "$base/admin/pengumpulan/baru")['body']);
        $r = req($jA, 'POST', "$base/admin/pengumpulan", ['_token' => $t] + $ubah + $isian);
        $g = req($jA, 'GET', "$base/admin/pengumpulan/baru");
        $cek($label, $r['kode'] === 303 && str_ends_with($r['lokasi'], '/admin/pengumpulan/baru') && str_contains($teks($g['body']), $pesan), $r['kode'] . ' ' . $r['lokasi']);
    };
    $tolak('semua nominal kosong ditolak', ['zakat' => '', 'infaq' => '', 'dskl' => ''], 'Isi minimal satu nominal');
    $tolak('nominal 0 ditolak', ['zakat' => '0', 'infaq' => ''], 'Zakat harus antara');
    $tolak('bulan 13 ditolak', ['periode_bulan' => '13'], 'Bulan dan tahun');
    $tolak('bulan kosong ditolak', ['periode_bulan' => '', 'periode_tahun' => ''], 'Bulan dan tahun wajib diisi');
    $tolak('tanggal di masa depan ditolak', ['tgl' => date('Y-m-d', strtotime('+3 days'))], 'tidak boleh melewati hari ini');
    $tolak('PD tidak dikenal ditolak', ['id_perangkat_daerah' => '999999'], 'Pilihan Perangkat Daerah tidak valid');
    $t = token(req($jA, 'GET', "$base/admin/pengumpulan/baru")['body']);
    req($jA, 'POST', "$base/admin/pengumpulan", ['_token' => $t, 'zakat' => '', 'infaq' => '', 'dskl' => ''] + $isian);
    $g = req($jA, 'GET', "$base/admin/pengumpulan/baru");
    $cek('setelah gagal: bulan/tahun & isian lama tetap terpilih', str_contains($g['body'], '<option value="3" selected>') && str_contains($g['body'], '<option value="2016" selected>'));
    $cek('jumlah data tidak bertambah', (int) $pdo->query('SELECT COUNT(*) FROM pengumpulan')->fetchColumn() === $jumlahAwal);

    echo "\n== Tambah, daftar, ubah\n";
    $tambah = function (array $isian) use ($jA, $base): array {
        $t = token(req($jA, 'GET', "$base/admin/pengumpulan/baru")['body']);
        return req($jA, 'POST', "$base/admin/pengumpulan", ['_token' => $t] + $isian);
    };
    $r = $tambah($isian);
    $a = $satu("SELECT * FROM pengumpulan WHERE id_perangkat_daerah = {$pdA['id']} AND periode = '2016-03-01'");
    $cek('tersimpan: periode 2016-03-01, zakat 1.500.000 + infaq 250.000, DSKL NULL, total 1.750.000', $r['kode'] === 303 && $a
        && (int) $a['zakat'] === 1500000 && (int) $a['infaq'] === 250000 && $a['dskl'] === null && (int) $a['total'] === 1750000, $r['lokasi']);
    $tambah(['id_perangkat_daerah' => $pdB['id'], 'zakat' => '', 'infaq' => '', 'dskl' => '500.000', 'tgl' => '2016-04-02'] + $isian);
    $tambah(['periode_bulan' => '4', 'tgl' => '2016-04-10', 'zakat' => '1.000.000', 'infaq' => ''] + $isian);
    $b = $satu("SELECT * FROM pengumpulan WHERE id_perangkat_daerah = {$pdB['id']}");
    $cek('hanya DSKL pun boleh (total 500.000)', $b && $b['zakat'] === null && $b['infaq'] === null && (int) $b['total'] === 500000);
    $r = $tambah(['total' => '99999999'] + $isian);
    $c = $satu("SELECT * FROM pengumpulan WHERE id_perangkat_daerah = {$pdA['id']} AND periode = '2016-03-01' AND id <> {$a['id']}");
    $cek('"total" dari form diabaikan, dihitung ulang server', $c && (int) $c['total'] === 1750000);
    if ($c) $pdo->exec("DELETE FROM pengumpulan WHERE id = {$c['id']}");

    $l = req($jA, 'GET', "$base/admin/pengumpulan?q=UJI-PG");
    $lt = $teks($l['body']);
    $cek('daftar: cari nama PD → 3 data, "Maret 2016", "Rp1.750.000", jenis kosong "—", PD tampil singkatan', str_contains($lt, '3 data') && str_contains($lt, 'Maret 2016')
        && str_contains($lt, 'Rp1.750.000') && str_contains($l['body'], '<span class="text-muted">—</span>') && str_contains($lt, 'UJI-B') && !str_contains($lt, 'UJI-PG Dinas Beta')); // daftar: singkatan PD
    $cek('daftar: judul kolom ID, Bulan, Perangkat Daerah, Infaq/Sedekah, DSKL, Total', (bool) preg_match('/ID.*Bulan.*Perangkat Daerah.*Zakat.*Infaq\/Sedekah.*DSKL.*Total/s', strip_tags(explode('</thead>', $l['body'])[0])));
    $cek('daftar: cari singkatan PD juga bisa', str_contains($teks(req($jA, 'GET', "$base/admin/pengumpulan?q=UJI-B")['body']), '1 data'));
    $u = req($jA, 'GET', "$base/admin/pengumpulan/{$a['id']}/ubah");
    $cek('form ubah: bulan Maret 2016 terpilih, "1.500.000", total Rp1.750.000', str_contains($u['body'], '<option value="3" selected>') && str_contains($u['body'], '<option value="2016" selected>')
        && str_contains($u['body'], 'value="1.500.000"') && (bool) preg_match('/data-jumlah[^>]*>Rp1\.750\.000</', $u['body']));
    $r = req($jA, 'POST', "$base/admin/pengumpulan/{$a['id']}", ['_token' => token($u['body']), 'infaq' => '500.000'] + $isian);
    $cek('ubah infaq → total 2.000.000', $r['kode'] === 303 && (int) $pdo->query("SELECT total FROM pengumpulan WHERE id = {$a['id']}")->fetchColumn() === 2000000);

    echo "\n== Dasbor\n";
    $h = req($jA, 'GET', "$base/admin?pg_tahun=2016&pg_bulan=3&periode=30h");
    $d = $teks($h['body']);
    $cek('Maret 2016: Total Rp2.500.000; Zakat Rp1.500.000; Infaq Rp500.000; DSKL Rp500.000', str_contains($d, 'Total pengumpulan Rp2.500.000')
        && str_contains($d, 'Zakat Rp1.500.000') && str_contains($d, 'Infaq/Sedekah Rp500.000') && str_contains($d, 'DSKL Rp500.000'));
    $cek("Maret 2016: \"2 dari $aktif perangkat daerah menyetor\"", str_contains($d, "2 dari $aktif perangkat daerah menyetor"));
    $cek('pembanding: vs Februari 2016 → "baru"', str_contains($d, 'baru vs Februari 2016'));
    $cek('grafik per bulan: Maret disorot (data-grafik-awal="2"), tahun 2016', str_contains($h['body'], 'data-grafik-awal="2"') && str_contains($d, 'Pemasukan pengumpulan per bulan · 2016'));
    $posA = strpos($d, 'UJI-A · UJI-PG Dinas Alfa'); $posB = strpos($d, 'UJI-B · UJI-PG Dinas Beta');
    $cek('grafik per PD: Alfa (Rp2 jt · 80%) di atas Beta (Rp500 rb · 20%)', $posA !== false && $posB !== false && $posA < $posB
        && str_contains($d, 'Rp2 jt · 80,0%') && str_contains($d, 'Rp500 rb · 20,0%'));
    $cek('belum menyetor: ' . ($aktif - 2) . ' PD, PD nonaktif tidak dihitung', str_contains($d, 'Belum menyetor pada Maret 2016 (' . ($aktif - 2) . ' perangkat daerah)') && !str_contains($d, 'UJI-PG Dinas Lama'));
    $cek('tabel per PD: baris jumlah Rp2.500.000, 2 setoran', (bool) preg_match('/Jumlah ?Rp1\.500\.000 ?Rp500\.000 ?Rp500\.000 ?Rp2\.500\.000 ?2/', $d));
    $cek('filter zakat membawa pg_tahun/pg_bulan; filter pengumpulan membawa periode', str_contains($h['body'], 'name="pg_tahun" value="2016"') && str_contains($h['body'], 'name="periode" value="30h"'));
    $d = $teks(req($jA, 'GET', "$base/admin?pg_tahun=2016")['body']);
    $cek('Tahun 2016 (semua bulan): Total Rp3.500.000, vs tahun 2015', str_contains($d, 'Total pengumpulan Rp3.500.000') && str_contains($d, 'vs tahun 2015'));
    $cek('tabel bulanan: April 2016 Rp1.000.000 … total Rp1.000.000, 1 PD', (bool) preg_match('/April 2016 ?Rp1\.000\.000 ?Rp0 ?Rp0 ?Rp1\.000\.000 ?1/', $d));
    $d = $teks(req($jA, 'GET', "$base/admin?pg_tahun=2016&pg_bulan=7")['body']);
    $cek('Juli 2016 kosong: pesan "Belum ada setoran" + daftar belum menyetor terbuka', str_contains($d, 'Belum ada setoran pada Juli 2016') && str_contains($d, "Belum menyetor pada Juli 2016 ($aktif perangkat daerah)"));
    $d = $teks(req($jA, 'GET', "$base/admin?pg_tahun=1999&pg_bulan=abc")['body']);
    $cek('tahun di luar data → tahun ini (tanpa error)', str_contains($d, 'Pengumpulan dari Perangkat Daerah') && str_contains($d, 'Tahun ' . date('Y')));

    masuk($base, $jK, $KEPALA, $PASS);
    $dk = req($jK, 'GET', "$base/admin?pg_tahun=2016&pg_bulan=3");
    $cek('kepala: melihat statistik pengumpulan yang sama', str_contains($teks($dk['body']), 'Total pengumpulan Rp2.500.000'));
    $cek('kepala: tanpa tombol/menu ke /admin/pengumpulan', !str_contains($dk['body'], '/admin/pengumpulan') && !str_contains($dk['body'], '/admin/perangkat-daerah'));
    $cek('kepala: /admin/pengumpulan & /admin/perangkat-daerah → 403', req($jK, 'GET', "$base/admin/pengumpulan")['kode'] === 403 && req($jK, 'GET', "$base/admin/perangkat-daerah/baru")['kode'] === 403);
    $r = req($jK, 'POST', "$base/admin/pengumpulan/{$a['id']}/hapus", ['_token' => token($dk['body'])]);
    $cek('kepala: hapus pengumpulan → 403, data tetap', $r['kode'] === 403 && $pdo->query("SELECT COUNT(*) FROM pengumpulan WHERE id = {$a['id']}")->fetchColumn() == 1);

    echo "\n== Relasi & hapus\n";
    $lp = req($jA, 'GET', "$base/admin/perangkat-daerah");
    req($jA, 'POST', "$base/admin/perangkat-daerah/{$pdA['id']}/hapus", ['_token' => token($lp['body'])]);
    $g = req($jA, 'GET', "$base/admin/perangkat-daerah");
    $cek('PD yang punya data pengumpulan tidak bisa dihapus (pesan jelas)', $pdo->query("SELECT COUNT(*) FROM perangkat_daerah WHERE id = {$pdA['id']}")->fetchColumn() == 1
        && str_contains($teks($g['body']), 'tidak dapat dihapus'));
    $r = req($jA, 'POST', "$base/admin/perangkat-daerah/{$pdL['id']}/hapus", ['_token' => token($g['body'])]);
    $cek('PD tanpa data bisa dihapus', $r['kode'] === 303 && $pdo->query("SELECT COUNT(*) FROM perangkat_daerah WHERE id = {$pdL['id']}")->fetchColumn() == 0);
    $l = req($jA, 'GET', "$base/admin/pengumpulan?q=UJI-B");
    $r = req($jA, 'POST', "$base/admin/pengumpulan/{$b['id']}/hapus", ['_token' => token($l['body'])]);
    $cek('hapus pengumpulan', $r['kode'] === 303 && $pdo->query("SELECT COUNT(*) FROM pengumpulan WHERE id = {$b['id']}")->fetchColumn() == 0);
} finally {
    $bersihkan();
    $hapusRl();
    foreach ([$jA, $jK] as $j) @unlink($j);
}
$cek('data kembali seperti semula', (int) $pdo->query('SELECT COUNT(*) FROM pengumpulan')->fetchColumn() === $jumlahAwal
    && (int) $pdo->query('SELECT COUNT(*) FROM perangkat_daerah')->fetchColumn() === $pdAwal);
printf("\n%s — %d gagal\n", $gagal === 0 ? 'SEMUA LULUS' : 'ADA YANG GAGAL', $gagal);
exit($gagal === 0 ? 0 : 1);
