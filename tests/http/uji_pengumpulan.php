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
    if ($method === 'POST') { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, array_filter($data, fn ($x) => $x instanceof CURLFile) ? $data : http_build_query($data)); }
    $raw = (string) curl_exec($ch);
    $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $r = ['kode' => curl_getinfo($ch, CURLINFO_RESPONSE_CODE), 'body' => substr($raw, $hs), 'head' => substr($raw, 0, $hs)];
    preg_match('/^location:\s*(\S+)/mi', $r['head'], $m);
    $r['lokasi'] = $m[1] ?? '';
    return $r;
}
/** Buat .xlsx gaya Excel (sharedStrings, angka, tanggal seri) untuk diunggah. @param list<list<mixed>> $baris */
function buatXlsx(string $path, array $baris): void
{
    $ss = []; $xml = '';
    foreach ($baris as $r => $sel) {
        $xml .= '<row r="' . ($r + 1) . '">';
        foreach ($sel as $c => $v) {
            $ref = chr(65 + $c) . ($r + 1);
            if ($v === null) continue;
            if ($v instanceof DateTimeImmutable) {
                $xml .= '<c r="' . $ref . '"><v>' . (new DateTimeImmutable('1899-12-30'))->diff($v)->days . '</v></c>';
            } elseif (is_int($v) || is_float($v)) {
                $xml .= '<c r="' . $ref . '"><v>' . $v . '</v></c>';
            } else {
                $ss[] = $v;
                $xml .= '<c r="' . $ref . '" t="s"><v>' . (count($ss) - 1) . '</v></c>';
            }
        }
        $xml .= '</row>';
    }
    $z = new ZipArchive(); @unlink($path); $z->open($path, ZipArchive::CREATE);
    $z->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/></Types>');
    $z->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $z->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Data" sheetId="1" r:id="rId7"/></sheets></workbook>');
    $z->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId7" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="/xl/worksheets/lembar9.xml"/></Relationships>');
    $z->addFromString('xl/sharedStrings.xml', '<?xml version="1.0"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . implode('', array_map(fn ($t) => '<si><t>' . htmlspecialchars($t, ENT_XML1) . '</t></si>', $ss)) . '</sst>');
    $z->addFromString('xl/worksheets/lembar9.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $xml . '</sheetData></worksheet>');
    $z->close();
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

    echo "\n== Impor Excel\n";
    $l = req($jA, 'GET', "$base/admin/pengumpulan");
    $cek('daftar: tombol "Unduh format Excel" & "Impor Excel"', str_contains($l['body'], '/admin/pengumpulan/format-impor') && str_contains($l['body'], '/admin/pengumpulan/impor"'));
    $fmt = req($jA, 'GET', "$base/admin/pengumpulan/format-impor");
    file_put_contents("$tmp/pg-format.xlsx", $fmt['body']);
    $cek('unduh format: .xlsx lampiran', $fmt['kode'] === 200 && str_contains($fmt['head'], 'spreadsheetml.sheet') && str_contains($fmt['head'], 'format-impor-pengumpulan.xlsx') && str_starts_with($fmt['body'], "PK\x03\x04"));
    $z = new ZipArchive(); $z->open("$tmp/pg-format.xlsx"); $lembar = (string) $z->getFromName('xl/worksheets/sheet1.xml'); $z->close();
    $cek("format berisi semua PD aktif ($aktif baris), PD nonaktif tidak", substr_count($lembar, '<row ') === 5 + $aktif && str_contains($lembar, 'UJI-PG Dinas Alfa') && !str_contains($lembar, 'UJI-PG Dinas Lama')
        && str_contains($lembar, 'Kecamatan Toboali') && str_contains($lembar, '>Infaq/Sedekah<'));
    $jumlahPg = fn () => (int) $pdo->query("SELECT COUNT(*) FROM pengumpulan p JOIN perangkat_daerah d ON d.id = p.id_perangkat_daerah WHERE d.nama LIKE 'UJI-PG %' AND p.periode = '2016-06-01'")->fetchColumn();
    $impor = function (string $file, array $isian = []) use ($jA, $base): array {
        $f = req($jA, 'GET', "$base/admin/pengumpulan/impor");
        $r = req($jA, 'POST', "$base/admin/pengumpulan/impor", $isian + ['_token' => token($f['body']), 'periode_bulan' => '6', 'periode_tahun' => '2016', 'tgl' => '2016-06-10', 'mode' => 'lewati',
            'berkas' => new CURLFile($file, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'isi.xlsx')]);
        $ikuti = req($jA, 'GET', str_starts_with($r['lokasi'], 'http') ? $r['lokasi'] : "$base/admin/pengumpulan");
        return $r + ['lanjut' => $ikuti['body']];
    };
    $judul = [['Format Impor'], [], ['No', 'ID', 'Perangkat Daerah', 'Zakat', 'Infaq/Sedekah', 'DSKL', 'Tanggal Pengumpulan', 'Keterangan']];
    buatXlsx("$tmp/pg-ok.xlsx", [...$judul,
        [1, $pdA['id'], 'UJI-PG Dinas Alfa', 1500000, '250.000', null, null, 'bukti 01'],
        [2, null, 'uji-b', null, null, 'Rp500.000,00', new DateTimeImmutable('2016-06-03'), null],
        [3, $pdo->query("SELECT id FROM perangkat_daerah WHERE nama = 'Setwan' OR singkatan = 'Setwan'")->fetchColumn(), 'Sekretariat DPRD', null, null, null, null, null],
    ]);
    $r = $impor("$tmp/pg-ok.xlsx");
    $ia = $satu("SELECT * FROM pengumpulan WHERE id_perangkat_daerah = {$pdA['id']} AND periode = '2016-06-01'");
    $ib = $satu("SELECT * FROM pengumpulan WHERE id_perangkat_daerah = {$pdB['id']} AND periode = '2016-06-01'");
    $cek('impor: 2 setoran tersimpan (cocok lewat ID & singkatan), PD tanpa nominal dilewati', $r['kode'] === 303 && $jumlahPg() === 2
        && str_contains($teks($r['lanjut']), 'Impor Juni 2016 selesai: 2 setoran tersimpan (Rp2.250.000), 1 PD tanpa nominal tidak dicatat'), $r['lokasi']);
    $cek('impor: nominal "250.000" & "Rp500.000,00" terbaca, total dihitung', $ia && (int) $ia['zakat'] === 1500000 && (int) $ia['infaq'] === 250000 && $ia['dskl'] === null && (int) $ia['total'] === 1750000
        && $ib && (int) $ib['dskl'] === 500000);
    $cek('impor: tanggal kosong → tanggal form; tanggal Excel terbaca; keterangan & petugas', $ia['tgl'] === '2016-06-10' && $ib['tgl'] === '2016-06-03' && $ia['keterangan'] === 'bukti 01' && $ia['petugas'] === 'Petugas Uji');
    $r = $impor("$tmp/pg-ok.xlsx");
    $cek('impor ulang (mode lewati): tidak ada data ganda', $jumlahPg() === 2 && str_contains($teks($r['lanjut']), '2 PD dilewati karena sudah punya data bulan ini'));
    buatXlsx("$tmp/pg-ganti.xlsx", [...$judul, [1, $pdA['id'], 'UJI-PG Dinas Alfa', 2000000, null, null, null, null]]);
    $r = $impor("$tmp/pg-ganti.xlsx", ['mode' => 'ganti']);
    $cek('mode ganti: data lama PD diganti isi file', $jumlahPg() === 2 && (int) $pdo->query("SELECT total FROM pengumpulan WHERE id_perangkat_daerah = {$pdA['id']} AND periode = '2016-06-01'")->fetchColumn() === 2000000);
    $r = $impor("$tmp/pg-ganti.xlsx", ['mode' => 'tambah']);
    $cek('mode tambah: dicatat sebagai setoran baru', $jumlahPg() === 3);

    buatXlsx("$tmp/pg-salah.xlsx", [...$judul,
        [1, $pdA['id'], 'UJI-PG Dinas Alfa', 100000, null, null, null, null],
        [2, null, 'Dinas Tidak Ada', 100000, null, null, null, null],
        [3, null, 'UJI-PG Dinas Beta', 'seratus ribu', null, null, null, null],
        [4, $pdA['id'], 'UJI-PG Dinas Alfa', 5000, null, null, null, null],
        [5, $pdB['id'], 'UJI-PG Dinas Beta', 5000, null, null, date('d/m/Y', strtotime('+5 days')), null],
    ]);
    $sebelum = $jumlahPg();
    $r = $impor("$tmp/pg-salah.xlsx", ['mode' => 'tambah']);
    $g = $teks($r['lanjut']);
    $cek('file bermasalah: tidak ada yang tersimpan (semua atau tidak sama sekali)', $jumlahPg() === $sebelum && str_ends_with($r['lokasi'], '/admin/pengumpulan/impor'));
    $cek('kesalahan per baris: PD tak dikenal, bukan angka, PD ganda, tanggal masa depan', str_contains($g, 'Baris 5: Perangkat Daerah "Dinas Tidak Ada" tidak dikenal')
        && str_contains($g, 'Baris 6: Zakat bukan angka') && str_contains($g, 'Baris 7: UJI-PG Dinas Alfa sudah ada di baris 4') && str_contains($g, 'Baris 8: Tanggal pengumpulan') && str_contains($g, 'melewati hari ini'));
    file_put_contents("$tmp/pg-palsu.xlsx", "No,PD,Zakat\n1,Setda,1000\n");
    $r = $impor("$tmp/pg-palsu.xlsx");
    $cek('file bukan .xlsx (CSV berganti nama) ditolak', str_contains($teks($r['lanjut']), 'Format file harus Excel .xlsx'));
    buatXlsx("$tmp/pg-tanpajudul.xlsx", [['Apa saja', 1, 2]]);
    $cek('file tanpa judul kolom ditolak dengan petunjuk', str_contains($teks($impor("$tmp/pg-tanpajudul.xlsx")['lanjut']), 'Baris judul kolom tidak ditemukan'));
    $cek('bulan & tahun wajib dipilih', str_contains($teks($impor("$tmp/pg-ok.xlsx", ['periode_bulan' => ''])['lanjut']), 'Bulan dan tahun wajib diisi'));
    $cek('form impor: field bulan, tanggal, pilihan data lama, file', str_contains(req($jA, 'GET', "$base/admin/pengumpulan/impor")['body'], 'name="mode" value="ganti"'));
    $cek('kepala: impor & unduh format → 403', req($jK, 'GET', "$base/admin/pengumpulan/impor")['kode'] === 403 && req($jK, 'GET', "$base/admin/pengumpulan/format-impor")['kode'] === 403);
    foreach (glob("$tmp/pg-*.xlsx") ?: [] as $x) unlink($x);

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
