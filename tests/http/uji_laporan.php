<?php
// Uji Laporan Keuangan: satu isian file menerima PDF atau Excel, validasi isi file, tampilan /laporan.
// Pemakaian: php tests/http/uji_laporan.php http://localhost:8002
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://localhost:8002', '/');
$proyek = dirname(__DIR__, 2);
$tmp = dirname(__DIR__) . '/tmp';
@mkdir($tmp);
$pdo = new PDO('mysql:host=127.0.0.1;dbname=baznas_basel;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
foreach (glob("$proyek/storage/cache/rl-*.json") ?: [] as $f) unlink($f);

$PASS = 'Uji-Laporan-' . bin2hex(random_bytes(3));
$bersihkan = function () use ($pdo, $proyek): void {
    foreach ($pdo->query("SELECT file FROM laporan_keuangan WHERE judul LIKE 'UJI-LP %'")->fetchAll() as $b) {
        if ($b['file']) @unlink("$proyek/public/uploads/laporan/{$b['file']}");
    }
    $pdo->exec("DELETE FROM laporan_keuangan WHERE judul LIKE 'UJI-LP %'");
    $pdo->exec("DELETE FROM user WHERE username LIKE 'uji\\_lp\\_%'");
};
$bersihkan();
$jumlahAwal = (int) $pdo->query('SELECT COUNT(*) FROM laporan_keuangan')->fetchColumn();
$pdo->prepare("INSERT INTO user (username, password, f_name, l_name, level, status) VALUES ('uji_lp_admin', ?, 'Admin', 'Uji', 1, '1')")->execute([password_hash($PASS, PASSWORD_BCRYPT)]);

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
$jA = "$tmp/lp-admin.txt"; $jP = "$tmp/lp-publik.txt"; @unlink($jA); @unlink($jP);

// Berkas uji
$zip = function (string $path, array $isi): void { $z = new ZipArchive(); @unlink($path); $z->open($path, ZipArchive::CREATE); foreach ($isi as $n => $t) $z->addFromString($n, $t); $z->close(); };
$zip("$tmp/lp.xlsx", ['[Content_Types].xml' => '<Types/>', 'xl/workbook.xml' => '<workbook/>', 'xl/worksheets/sheet1.xml' => '<worksheet/>']);
$zip("$tmp/lp-makro.xlsx", ['[Content_Types].xml' => '<Types><Override ContentType="application/vnd.ms-excel.sheet.macroEnabled.main+xml"/></Types>', 'xl/workbook.xml' => '<workbook/>', 'xl/vbaProject.bin' => 'x']);
$zip("$tmp/lp-docx.xlsx", ['[Content_Types].xml' => '<Types/>', 'word/document.xml' => '<document/>']);
file_put_contents("$tmp/lp.xls", "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" . str_repeat("\0", 1024));
file_put_contents("$tmp/lp-csv.xlsx", "No,Uraian,Jumlah\n1,Zakat,1000\n");
file_put_contents("$tmp/lp.pdf", "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");

try {
    $f = req($jA, 'GET', "$base/admin/masuk");
    req($jA, 'POST', "$base/admin/masuk", ['_token' => token($f['body']), 'username' => 'uji_lp_admin', 'password' => $PASS]);
    $baru = req($jA, 'GET', "$base/admin/laporan/baru");
    $cek('form tambah: satu isian "File laporan (PDF atau Excel)" menerima .pdf/.xlsx/.xls', $baru['kode'] === 200 && substr_count($baru['body'], 'type="file"') === 1
        && str_contains($baru['body'], 'File laporan (PDF atau Excel)') && str_contains($baru['body'], 'accept="application/pdf,.pdf,.xlsx,.xls') && preg_match('/name="file"[^>]*required/', $baru['body']) === 1);

    $simpan = function (string $judul, array $file, ?int $id = null) use ($jA, $base): array {
        $f = req($jA, 'GET', $id === null ? "$base/admin/laporan/baru" : "$base/admin/laporan/$id/ubah");
        $r = req($jA, 'POST', $id === null ? "$base/admin/laporan" : "$base/admin/laporan/$id", ['_token' => token($f['body']), 'judul' => $judul, 'tahun' => '2025', 'tgl' => '2026-01-15', 'publish' => '1'] + $file);
        $ulang = req($jA, 'GET', str_starts_with($r['lokasi'], 'http') ? $r['lokasi'] : "$base/admin/laporan");
        return $r + ['lanjut' => $ulang['body']];
    };
    $berkas = fn (string $p, string $mime = 'application/octet-stream') => new CURLFile("$tmp/$p", $mime, $p);

    echo "== Validasi\n";
    $berkasAwal = count(glob("$proyek/public/uploads/laporan/*") ?: []);
    $r = $simpan('UJI-LP Tanpa berkas', []);
    $cek('tanpa file → ditolak (wajib)', str_ends_with($r['lokasi'], '/baru') && str_contains($teks($r['lanjut']), 'wajib diunggah'));
    $r = $simpan('UJI-LP Makro', ['file' => $berkas('lp-makro.xlsx')]);
    $cek('Excel bermakro (.xlsm) ditolak dengan pesan khusus', str_contains($teks($r['lanjut']), 'Excel bermakro (.xlsm) tidak diterima'));
    $r = $simpan('UJI-LP Docx', ['file' => $berkas('lp-docx.xlsx')]);
    $cek('arsip ZIP bukan Excel (mis. .docx berganti nama) ditolak', str_contains($teks($r['lanjut']), 'File harus berupa PDF atau Excel'));
    $r = $simpan('UJI-LP CSV', ['file' => $berkas('lp-csv.xlsx')]);
    $cek('CSV berganti nama .xlsx ditolak', str_contains($teks($r['lanjut']), 'File harus berupa PDF atau Excel'));
    $cek('kiriman salah: tidak ada data & tidak ada berkas yatim', (int) $pdo->query("SELECT COUNT(*) FROM laporan_keuangan WHERE judul LIKE 'UJI-LP %'")->fetchColumn() === 0
        && count(glob("$proyek/public/uploads/laporan/*") ?: []) === $berkasAwal);

    echo "\n== Simpan\n";
    $id = fn (string $judul) => $pdo->query("SELECT * FROM laporan_keuangan WHERE judul = " . $pdo->quote($judul))->fetch();
    $r = $simpan('UJI-LP Excel', ['file' => $berkas('lp.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')]);
    $a = $id('UJI-LP Excel');
    $cek('Excel .xlsx → tersimpan nama acak .xlsx', $r['kode'] === 303 && $a && preg_match('/^[a-f0-9]{32}\.xlsx$/', (string) $a['file']) === 1 && is_file("$proyek/public/uploads/laporan/{$a['file']}"), $r['lokasi']);
    $simpan('UJI-LP Excel lama', ['file' => $berkas('lp.xls', 'application/vnd.ms-excel')]);
    $b = $id('UJI-LP Excel lama');
    $cek('Excel 97–2003 (.xls) diterima', $b && str_ends_with((string) $b['file'], '.xls'));
    $simpan('UJI-LP PDF', ['file' => $berkas('lp.pdf', 'application/pdf')]);
    $c = $id('UJI-LP PDF');
    $cek('PDF tetap diterima di isian yang sama', $c && str_ends_with((string) $c['file'], '.pdf'));
    $l = req($jA, 'GET', "$base/admin/laporan?q=UJI-LP");
    $cek('daftar admin: kolom Format berisi "Excel" & "PDF"', str_contains($l['body'], '>Format<') && str_contains($l['body'], '>Excel</span>') && str_contains($l['body'], '>PDF</span>')
        && str_contains($teks($l['body']), '3 data'));

    echo "\n== Ubah\n";
    $u = req($jA, 'GET', "$base/admin/laporan/{$c['id_laporan_keuangan']}/ubah");
    $cek('form ubah: "File saat ini (PDF)", isian tidak wajib lagi', str_contains($u['body'], 'File saat ini (PDF)') && preg_match('/name="file"[^>]*required/', $u['body']) === 0);
    $r = $simpan('UJI-LP PDF', ['file' => $berkas('lp.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')], (int) $c['id_laporan_keuangan']);
    $c2 = $id('UJI-LP PDF');
    $cek('ganti PDF dengan Excel → file baru .xlsx, PDF lama terhapus', $r['kode'] === 303 && str_ends_with((string) $c2['file'], '.xlsx') && !is_file("$proyek/public/uploads/laporan/{$c['file']}"));
    $simpan('UJI-LP PDF', ['file' => $berkas('lp.pdf', 'application/pdf')], (int) $c['id_laporan_keuangan']);

    echo "\n== Halaman publik /laporan\n";
    $h = req($jP, 'GET', "$base/laporan")['body'];
    $blok = fn (string $judul) => preg_match('#<li[^>]*>(?:(?!</li>).)*>' . preg_quote($judul, '#') . '<.*?</li>#s', $h, $m) ? $m[0] : '';
    $ex = $blok('UJI-LP Excel');
    $cek('laporan Excel: tombol "Excel" (download, MIME xlsx)', str_contains($ex, 'uploads/laporan/' . $a['file']) && str_contains($ex, ' download') && str_contains($ex, 'spreadsheetml.sheet') && str_contains($teks($ex), 'Excel'));
    $cek('laporan .xls: MIME application/vnd.ms-excel', str_contains($blok('UJI-LP Excel lama'), 'type="application/vnd.ms-excel"'));
    $pdf = $blok('UJI-LP PDF');
    $cek('laporan PDF: tombol "PDF" (tab baru)', str_contains($pdf, 'type="application/pdf"') && str_contains($pdf, 'target="_blank"') && str_contains($teks($pdf), 'PDF'));
    $unduh = req($jP, 'GET', "$base/uploads/laporan/{$a['file']}");
    $cek('berkas Excel bisa diunduh (200)', $unduh['kode'] === 200 && str_starts_with($unduh['body'], "PK\x03\x04"));
} finally {
    $bersihkan();
    foreach (glob("$tmp/lp*") ?: [] as $x) @unlink($x);
}
$cek('data kembali seperti semula', (int) $pdo->query('SELECT COUNT(*) FROM laporan_keuangan')->fetchColumn() === $jumlahAwal);
printf("\n%s — %d gagal\n", $gagal === 0 ? 'SEMUA LULUS' : 'ADA YANG GAGAL', $gagal);
exit($gagal === 0 ? 0 : 1);
