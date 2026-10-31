<?php
// Uji menu Distribusi (CRUD) + statistik distribusi di dasbor. Pemakaian: php tests/http/uji_distribusi.php http://localhost:8002
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://localhost:8002', '/');
$proyek = dirname(__DIR__, 2);
$tmp = dirname(__DIR__) . '/tmp';
@mkdir($tmp);
$pdo = new PDO('mysql:host=127.0.0.1;dbname=baznas_basel;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$hapusRl = function () use ($proyek): void { foreach (glob("$proyek/storage/cache/rl-*.json") ?: [] as $f) unlink($f); };
$hapusRl();

$ADMIN = 'uji_ds_admin'; $KEPALA = 'uji_ds_kepala'; $PASS = 'Uji-Distribusi-' . bin2hex(random_bytes(3));
$pdo->exec("DELETE FROM user WHERE username LIKE 'uji\\_ds\\_%'");
$pdo->exec("DELETE FROM distribusi WHERE nama_program LIKE 'UJI-DS %'");
$jumlahAwal = (int) $pdo->query('SELECT COUNT(*) FROM distribusi')->fetchColumn();
$ins = $pdo->prepare("INSERT INTO user (username, password, f_name, l_name, level, status) VALUES (?, ?, ?, 'Uji', ?, '1')");
$ins->execute([$ADMIN, password_hash($PASS, PASSWORD_BCRYPT), 'Petugas', 1]);
$ins->execute([$KEPALA, password_hash($PASS, PASSWORD_BCRYPT), 'Kepala', 3]);
$sub = $pdo->query("SELECT s.id_sub_program, k.label FROM sub_program s JOIN kategori_program k USING (id_kategori_program) WHERE k.label = 'Basel Sehat' LIMIT 1")->fetch();

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
    printf("%s %-72s %s\n", $ok ? 'OK  ' : 'GAGAL', $label, $info);
};
$teks = fn (string $html) => preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html)));
$jA = "$tmp/ds-admin.txt"; $jK = "$tmp/ds-kepala.txt";
$hariIni = date('Y-m-d');

try {
    masuk($base, $jA, $ADMIN, $PASS);
    echo "== Menu & form\n";
    $d = req($jA, 'GET', "$base/admin");
    $cek('menu memuat Distribusi', str_contains($d['body'], 'href="' . $base . '/admin/distribusi"'));
    $f = req($jA, 'GET', "$base/admin/distribusi/baru");
    $cek('form tambah: pilihan "Basel Sehat — …", nominal rupiah, asnaf, kecamatan, petugas terisi nama admin', $f['kode'] === 200
        && str_contains($f['body'], 'Basel Sehat — ') && str_contains($f['body'], 'data-rupiah') && str_contains($f['body'], 'value="ibnu_sabil"')
        && str_contains($f['body'], 'value="Toboali"') && str_contains($f['body'], 'value="Petugas Uji"'));

    echo "\n== Validasi\n";
    $isian = ['tgl' => $hariIni, 'id_sub_program' => $sub['id_sub_program'], 'nama_program' => 'UJI-DS Bantuan berobat warga', 'nominal' => 'Rp1.500.000',
        'sumber_dana' => 'zakat', 'asnaf' => 'miskin', 'sifat' => 'konsumtif', 'penerima' => '3', 'kecamatan' => 'Toboali', 'lokasi' => 'Desa Rias', 'keterangan' => 'Uji', 'petugas' => 'Petugas Uji'];
    $tolak = function (string $label, array $ubah, string $pesan) use ($jA, $base, $isian, $cek, $teks): void {
        $t = token(req($jA, 'GET', "$base/admin/distribusi/baru")['body']);
        $r = req($jA, 'POST', "$base/admin/distribusi", ['_token' => $t] + $ubah + $isian);
        $g = req($jA, 'GET', "$base/admin/distribusi/baru");
        $cek($label, $r['kode'] === 303 && str_ends_with($r['lokasi'], '/admin/distribusi/baru') && str_contains($teks($g['body']), $pesan), $r['kode'] . ' ' . $r['lokasi']);
    };
    $tolak('dana zakat tanpa asnaf ditolak', ['asnaf' => ''], 'Asnaf wajib diisi');
    $tolak('tanggal di masa depan ditolak', ['tgl' => date('Y-m-d', strtotime('+3 days'))], 'tidak boleh melewati hari ini');
    $tolak('nominal 0 ditolak', ['nominal' => '0'], 'Nominal disalurkan harus antara');
    $tolak('nominal kosong ditolak', ['nominal' => ''], 'Nominal disalurkan wajib diisi');
    $tolak('sub-program tidak dikenal ditolak', ['id_sub_program' => '99999'], 'Pilihan Pilar — sub-program tidak valid');
    $tolak('asnaf tidak dikenal ditolak', ['asnaf' => 'bukan'], 'Pilihan Asnaf penerima tidak valid');
    $tolak('nama program terlalu pendek ditolak', ['nama_program' => 'abc'], 'minimal 5 karakter');
    $cek('jumlah data tidak bertambah', (int) $pdo->query('SELECT COUNT(*) FROM distribusi')->fetchColumn() === $jumlahAwal);

    echo "\n== Tambah, daftar, ubah\n";
    $t = token(req($jA, 'GET', "$base/admin/distribusi/baru")['body']);
    $r = req($jA, 'POST', "$base/admin/distribusi", ['_token' => $t] + $isian);
    $baru = $pdo->query("SELECT * FROM distribusi WHERE nama_program = 'UJI-DS Bantuan berobat warga'")->fetch();
    $cek('tersimpan; "Rp1.500.000" → 1500000; penerima 3', $r['kode'] === 303 && $baru && (int) $baru['nominal'] === 1500000 && (int) $baru['penerima'] === 3, $r['lokasi']);
    $t = token(req($jA, 'GET', "$base/admin/distribusi/baru")['body']);
    req($jA, 'POST', "$base/admin/distribusi", ['_token' => $t] + ['nama_program' => 'UJI-DS Modal usaha mustahik', 'nominal' => '5.000.000', 'sumber_dana' => 'infaq', 'asnaf' => '',
        'sifat' => 'produktif', 'penerima' => '', 'kecamatan' => ''] + $isian);
    $kedua = $pdo->query("SELECT * FROM distribusi WHERE nama_program = 'UJI-DS Modal usaha mustahik'")->fetch();
    $cek('dana infak boleh tanpa asnaf; penerima kosong → NULL (bukan 0)', $kedua && $kedua['asnaf'] === null && $kedua['penerima'] === null && (int) $kedua['nominal'] === 5000000);
    $l = req($jA, 'GET', "$base/admin/distribusi?q=UJI-DS");
    $cek('daftar: 2 data, nominal "Rp1.500.000", nama sub-program tampil', str_contains($l['body'], '2 data') && str_contains($l['body'], 'Rp1.500.000') && str_contains($l['body'], 'Basel Sehat — '));
    $u = req($jA, 'GET', "$base/admin/distribusi/{$baru['id']}/ubah");
    $cek('form ubah menampilkan nominal "1.500.000"', str_contains($u['body'], 'value="1.500.000"'));
    $r = req($jA, 'POST', "$base/admin/distribusi/{$baru['id']}", ['_token' => token($u['body']), 'nominal' => '2.250.000'] + $isian);
    $cek('ubah nominal → 2.250.000', $r['kode'] === 303 && (int) $pdo->query("SELECT nominal FROM distribusi WHERE id = {$baru['id']}")->fetchColumn() === 2250000);

    echo "\n== Dasbor\n";
    $d = $teks(req($jA, 'GET', "$base/admin?periode=30h")['body']);
    $cek('admin: Total disalurkan Rp7.250.000, 2 kegiatan, 3 jiwa', str_contains($d, 'Total disalurkan Rp7.250.000') && str_contains($d, 'Kegiatan penyaluran 2') && str_contains($d, 'Penerima manfaat 3 jiwa'));
    $cek('admin: grafik per pilar menampilkan Basel Sehat & sub-programnya', str_contains($d, 'Menurut pilar & sub-program') && str_contains($d, 'Basel Sehat'));
    $cek('admin: % produktif (5 jt / 7,25 jt = 69%)', str_contains($d, '69% produktif'));
    $cek('admin: asnaf hanya dana zakat (Miskin 100%), kecamatan "Toboali"', str_contains($d, 'Miskin 1 kegiatan Rp2,3 jt · 100,0%') && str_contains($d, 'Toboali'));
    $cek('admin: tabel "Distribusi terbaru" bertautan ke form ubah', str_contains(req($jA, 'GET', "$base/admin?periode=30h")['body'], '/admin/distribusi/' . $baru['id'] . '/ubah'));
    $cek('rentang tanpa distribusi → empty state', str_contains($teks(req($jA, 'GET', "$base/admin?dari=2021-01-01&sampai=2021-02-01")['body']), 'Belum ada distribusi tercatat'));

    masuk($base, $jK, $KEPALA, $PASS);
    $dk = req($jK, 'GET', "$base/admin?periode=30h");
    $cek('kepala: melihat statistik distribusi yang sama', str_contains($teks($dk['body']), 'Total disalurkan Rp7.250.000'));
    $cek('kepala: tanpa tombol "Catat distribusi" & tanpa tautan ubah', !str_contains($dk['body'], '/admin/distribusi'));
    $cek('kepala: /admin/distribusi → 403', req($jK, 'GET', "$base/admin/distribusi")['kode'] === 403 && req($jK, 'GET', "$base/admin/distribusi/baru")['kode'] === 403);
    $r = req($jK, 'POST', "$base/admin/distribusi/{$baru['id']}/hapus", ['_token' => token($dk['body'])]);
    $cek('kepala: hapus distribusi → 403, data tetap', $r['kode'] === 403 && $pdo->query("SELECT COUNT(*) FROM distribusi WHERE id = {$baru['id']}")->fetchColumn() == 1);

    echo "\n== Relasi & hapus\n";
    $sp = req($jA, 'GET', "$base/admin/sub-program");
    $r = req($jA, 'POST', "$base/admin/sub-program/{$sub['id_sub_program']}/hapus", ['_token' => token($sp['body'])]);
    $g = req($jA, 'GET', "$base/admin/sub-program");
    $cek('sub-program yang punya distribusi tidak bisa dihapus (pesan jelas)', $pdo->query("SELECT COUNT(*) FROM sub_program WHERE id_sub_program = {$sub['id_sub_program']}")->fetchColumn() == 1
        && str_contains($teks($g['body']), 'tidak dapat dihapus'));
    $l = req($jA, 'GET', "$base/admin/distribusi?q=UJI-DS");
    $r = req($jA, 'POST', "$base/admin/distribusi/{$kedua['id']}/hapus", ['_token' => token($l['body'])]);
    $cek('hapus distribusi', $r['kode'] === 303 && $pdo->query("SELECT COUNT(*) FROM distribusi WHERE id = {$kedua['id']}")->fetchColumn() == 0);
} finally {
    $pdo->exec("DELETE FROM distribusi WHERE nama_program LIKE 'UJI-DS %'");
    $pdo->exec("DELETE FROM user WHERE username LIKE 'uji\\_ds\\_%'");
    $hapusRl();
    foreach ([$jA, $jK] as $j) @unlink($j);
}
$cek('data kembali seperti semula', (int) $pdo->query('SELECT COUNT(*) FROM distribusi')->fetchColumn() === $jumlahAwal);
printf("\n%s — %d gagal\n", $gagal === 0 ? 'SEMUA LULUS' : 'ADA YANG GAGAL', $gagal);
exit($gagal === 0 ? 0 : 1);
