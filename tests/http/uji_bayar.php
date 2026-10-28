<?php
// Uji end-to-end alur bayar & konfirmasi (Fase 6). Pemakaian: php uji_bayar.php http://localhost:8002
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://localhost:8002', '/');
$proyek = dirname(__DIR__, 2);
$tmp = dirname(__DIR__) . '/tmp';
@mkdir($tmp);
$jar = "$tmp/cookie.txt";
@unlink($jar);
$pdo = new PDO('mysql:host=127.0.0.1;dbname=baznas_basel;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
require __DIR__ . '/zakat_kosong.inc.php';
$awal = [(int) $pdo->query('SELECT COUNT(*) FROM pembayaran')->fetchColumn(), (int) $pdo->query('SELECT COUNT(*) FROM konfirmasi_pembayaran')->fetchColumn()];
foreach (glob("$proyek/storage/cache/rl-*.json") ?: [] as $f) { unlink($f); }

function req(string $method, string $url, array $data = [], bool $multipart = false, array $header = []): array
{
    global $jar;
    $ch = curl_init($url);
    $opt = [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => $header];
    if ($method === 'POST') {
        $opt[CURLOPT_POST] = true;
        $opt[CURLOPT_POSTFIELDS] = $multipart ? $data : http_build_query($data);
    }
    curl_setopt_array($ch, $opt);
    $raw = (string) curl_exec($ch);
    $kode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $head = substr($raw, 0, $hs);
    preg_match('/^location:\s*(\S+)/mi', $head, $m);
    return ['kode' => $kode, 'lokasi' => $m[1] ?? '', 'head' => $head, 'body' => substr($raw, $hs)];
}
function token(string $html): string { preg_match('/name="_token" value="([a-f0-9]{64})"/', $html, $m); return $m[1] ?? ''; }

$gagal = 0;
$cek = function (string $label, bool $ok, string $info = '') use (&$gagal): void {
    if (!$ok) $gagal++;
    printf("%s %-62s %s\n", $ok ? 'OK  ' : 'GAGAL', $label, $info);
};
$NAMA = 'Uji Coba Fase Enam';

echo "== Bayar zakat\n";
$f = req('GET', "$base/bayar-zakat?jenis=8&nominal=250000");
$t = token($f['body']);
$cek('GET /bayar-zakat → 200 + token CSRF', $f['kode'] === 200 && $t !== '');
$cek('prefill ?nominal → 250.000', str_contains($f['body'], 'value="250.000"'));

$r = req('POST', "$base/bayar-zakat", ['_token' => $t, 'jenis' => 8, 'nominal' => '250.000', 'sapaan' => 'BAPAK', 'nama_lengkap' => $NAMA, 'no_hp' => '+62 812-3456-7890', 'email' => 'uji@contoh.id', 'website' => '']);
$ok = preg_match('#/bayar-zakat/([A-Z2-9]{8})$#', $r['lokasi'], $m);
$KODE = $m[1] ?? '';
$cek('POST valid → 303 ke /bayar-zakat/{KODE 8 karakter}', $r['kode'] === 303 && $ok === 1, $r['kode'] . ' ' . $r['lokasi']);
$row = $pdo->query("SELECT * FROM pembayaran WHERE kode_pembayaran = " . $pdo->quote($KODE))->fetch(PDO::FETCH_ASSOC);
$cek('DB: nominal 250000, HP dinormalkan 081234567890, status 0', $row && (int) $row['nominal'] === 250000 && $row['no_hp'] === '081234567890' && $row['status'] === '0', json_encode([$row['nominal'] ?? null, $row['no_hp'] ?? null]));

$i = req('GET', "$base/bayar-zakat/$KODE");
$cek('instruksi: 200, nominal, kode, nama, pesan sukses', $i['kode'] === 200 && str_contains($i['body'], 'Rp250.000') && str_contains($i['body'], $KODE) && str_contains($i['body'], $NAMA) && str_contains($i['body'], 'Data pembayaran tersimpan'));
$cek('instruksi: noindex + Cache-Control no-store', str_contains($i['body'], 'noindex') && preg_match('/cache-control:.*no-store/i', $i['head']) === 1);
preg_match('#id="judul-rekening".*?</section>#s', $i['body'], $sek);
$cek('instruksi: rekening zakat saja (bukan infaq)', str_contains($sek[0] ?? '', '(ZAKAT)') && !str_contains($sek[0] ?? '', '(INFAQ)'));
$cek('instruksi: tautan WA berisi kode', str_contains($i['body'], 'wa.me/') && str_contains($i['body'], rawurlencode($KODE)));
$cek('instruksi kode tak dikenal → 404', req('GET', "$base/bayar-zakat/ZZZZZZZZ")['kode'] === 404);
$cek('instruksi kode berkarakter aneh → 404', req('GET', "$base/bayar-zakat/" . rawurlencode("AB'--"))['kode'] === 404);

echo "\n== Validasi server\n";
$t = token(req('GET', "$base/bayar-zakat")['body']);
$r = req('POST', "$base/bayar-zakat", ['_token' => $t, 'jenis' => 7, 'nominal' => '5.000', 'sapaan' => 'BAPAK', 'nama_lengkap' => '', 'no_hp' => '12345', 'email' => 'bukan-email']);
$g = req('GET', "$base/bayar-zakat");
$cek('isian salah → 303 kembali ke form', $r['kode'] === 303 && str_ends_with($r['lokasi'], '/bayar-zakat'));
$cek('pesan: nama wajib, HP tidak valid, email tidak valid, nominal minimal', str_contains($g['body'], 'Nama lengkap wajib diisi') && str_contains($g['body'], 'Format Nomor WhatsApp tidak valid') && str_contains($g['body'], 'Format Email tidak valid') && str_contains($g['body'], 'Nominal harus antara'));
$cek('isian lama dipertahankan (email)', str_contains($g['body'], 'value="bukan-email"'));
$cek('error ditandai aria-invalid', substr_count($g['body'], 'aria-invalid="true"') >= 3);

$t = token($g['body']);
$r = req('POST', "$base/bayar-zakat", ['_token' => $t, 'jenis' => 999, 'nominal' => '100.000', 'sapaan' => 'IBU', 'nama_lengkap' => $NAMA, 'no_hp' => '081234567890']);
$cek('jenis palsu (999) ditolak', $r['kode'] === 303 && str_ends_with($r['lokasi'], '/bayar-zakat'));
$t = token(req('GET', "$base/bayar-zakat")['body']);
$r = req('POST', "$base/bayar-zakat", ['_token' => $t, 'jenis' => 3, 'hari' => '5', 'nominal' => '1', 'sapaan' => 'IBU', 'nama_lengkap' => $NAMA . ' Fidyah', 'no_hp' => '081234567890']);
preg_match('#/bayar-zakat/([A-Z2-9]{8})$#', $r['lokasi'], $m);
$fid = $pdo->query("SELECT nominal, jml_hari FROM pembayaran WHERE kode_pembayaran = " . $pdo->quote($m[1] ?? ''))->fetch(PDO::FETCH_ASSOC);
$cek('fidyah: nominal dihitung server (5 × 20.000), abaikan nominal=1', $fid && (int) $fid['nominal'] === 100000 && (int) $fid['jml_hari'] === 5, json_encode($fid));
$t = token(req('GET', "$base/bayar-zakat")['body']);
$r = req('POST', "$base/bayar-zakat", ['_token' => $t, 'jenis' => 1, 'jiwa' => '3', 'nominal' => '135.000', 'sapaan' => 'BAPAK', 'nama_lengkap' => $NAMA . ' Fitrah', 'no_hp' => '6281234567890']);
preg_match('#/bayar-zakat/([A-Z2-9]{8})$#', $r['lokasi'], $m);
$fit = $pdo->query("SELECT nominal, jml_jiwa, no_hp FROM pembayaran WHERE kode_pembayaran = " . $pdo->quote($m[1] ?? ''))->fetch(PDO::FETCH_ASSOC);
$cek('fitrah (nilai belum diatur): nominal isian + jml_jiwa 3, HP 62→0', $fit && (int) $fit['nominal'] === 135000 && (int) $fit['jml_jiwa'] === 3 && $fit['no_hp'] === '081234567890', json_encode($fit));

$sebelum = (int) $pdo->query('SELECT COUNT(*) FROM pembayaran')->fetchColumn();
$t = token(req('GET', "$base/bayar-zakat")['body']);
$r = req('POST', "$base/bayar-zakat", ['_token' => $t, 'jenis' => 7, 'nominal' => '100.000', 'sapaan' => 'BAPAK', 'nama_lengkap' => 'Bot Spam', 'no_hp' => '081234567890', 'website' => 'http://spam.example']);
$cek('honeypot terisi → ditolak, tidak tersimpan', (int) $pdo->query('SELECT COUNT(*) FROM pembayaran')->fetchColumn() === $sebelum && str_ends_with($r['lokasi'], '/bayar-zakat'));
$cek('POST tanpa token CSRF → 419', req('POST', "$base/bayar-zakat", ['jenis' => 7, 'nominal' => '100.000'])['kode'] === 419);

echo "\n== Konfirmasi & unggah\n";
$jpg = "$tmp/bukti.jpg"; $im = imagecreatetruecolor(1200, 900); imagefill($im, 0, 0, imagecolorallocate($im, 30, 120, 60)); imagejpeg($im, $jpg, 90);
$pdf = "$tmp/bukti.pdf"; file_put_contents($pdf, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");
$php = "$tmp/shell.jpg"; file_put_contents($php, "<?php system(\$_GET['c']); ?>");
$poly = "$tmp/poly.gif"; file_put_contents($poly, "GIF89a\x01\x00\x01\x00\x00\x00\x00;<?php echo 'x'; ?>");
$palsu = "$tmp/palsu.pdf"; file_put_contents($palsu, "ini bukan pdf");
$besar = "$tmp/besar.jpg"; file_put_contents($besar, str_repeat('A', (int) (5.5 * 1048576)));
$sangatBesar = "$tmp/sangat-besar.bin"; file_put_contents($sangatBesar, str_repeat('B', 13 * 1048576));

$k = req('GET', "$base/konfirmasi?kode=$KODE");
$cek('GET /konfirmasi?kode → 200, ringkasan nominal', $k['kode'] === 200 && str_contains($k['body'], 'Rp250.000') && !str_contains($k['body'], $NAMA));
$rek = (int) $pdo->query("SELECT id_rekening FROM rekening ORDER BY id_rekening LIMIT 1")->fetchColumn();
$isian = fn (string $file, string $mime, array $ubah = []) => array_merge(['_token' => token(req('GET', "$base/konfirmasi")['body']), 'kode' => $KODE, 'bank_pengirim' => 'BSI', 'rekening' => $rek, 'tgl_transfer' => date('Y-m-d'), 'nominal_transfer' => '250.000', 'bukti' => new CURLFile($file, $mime, basename($file))], $ubah);

$tolak = function (string $label, array $data, string $pesan) use ($base, $cek): void {
    $r = req('POST', "$base/konfirmasi", $data, true);
    $g = req('GET', $r['lokasi'] ?: "$base/konfirmasi");
    $cek($label, $r['kode'] === 303 && str_contains(html_entity_decode($g['body']), $pesan), $r['kode'] . ' ' . (str_contains(html_entity_decode($g['body']), $pesan) ? '' : 'pesan tidak ditemukan'));
};
$tolak('file PHP berkedok .jpg ditolak', $isian($php, 'image/jpeg'), 'Format file harus');
$tolak('GIF polyglot berisi PHP ditolak', $isian($poly, 'image/gif'), 'Format file harus');
$tolak('teks berkedok .pdf ditolak', $isian($palsu, 'application/pdf'), 'Format file harus');
$tolak('file > 5 MB ditolak', $isian($besar, 'image/jpeg'), 'Ukuran file maksimal');
$tolak('kode tidak dikenal ditolak', $isian($jpg, 'image/jpeg', ['kode' => 'QQQQQQQQ']), 'Kode pembayaran tidak ditemukan');
$tolak('tanggal transfer di masa depan ditolak', $isian($jpg, 'image/jpeg', ['tgl_transfer' => date('Y-m-d', strtotime('+3 days'))]), 'tidak boleh melewati hari ini');
$r = req('POST', "$base/konfirmasi", $isian($sangatBesar, 'application/octet-stream'), true);
$cek('body > post_max_size → 413 (bukan 419)', $r['kode'] === 413, (string) $r['kode']);
$cek('tidak ada file tersimpan dari percobaan ditolak', count(glob("$proyek/storage/bukti/*/*/*") ?: []) === 0);

foreach (glob("$proyek/storage/cache/rl-*.json") ?: [] as $x) { unlink($x); }
$r = req('POST', "$base/konfirmasi", $isian($jpg, 'image/jpeg'), true);
$cek('konfirmasi JPG valid → 303 ke instruksi', $r['kode'] === 303 && str_ends_with($r['lokasi'], "/bayar-zakat/$KODE"), $r['kode'] . ' ' . $r['lokasi']);
$kr = $pdo->query("SELECT * FROM konfirmasi_pembayaran WHERE kode_pembayaran = " . $pdo->quote($KODE))->fetch(PDO::FETCH_ASSOC);
$cek('DB: konfirmasi terhubung id_pembayaran, status baru, nominal 250000', $kr && (int) $kr['id_pembayaran'] === (int) $row['id_pembayaran'] && $kr['status'] === 'baru' && (int) $kr['nominal_transfer'] === 250000);
$fileBukti = "$proyek/storage/bukti/" . ($kr['bukti_pembayaran'] ?? '');
$info = @getimagesize($fileBukti);
$cek('bukti di-encode ulang → WebP di storage/bukti (luar public)', is_file($fileBukti) && $info !== false && $info[2] === IMAGETYPE_WEBP && str_ends_with($fileBukti, '.webp'), $kr['bukti_pembayaran'] ?? '');
$cek('bukti tidak bisa diakses lewat web', req('GET', "$base/storage/bukti/" . $kr['bukti_pembayaran'])['kode'] === 404 && req('GET', "$base/../storage/bukti/" . $kr['bukti_pembayaran'])['kode'] !== 200);
$i = req('GET', "$base/bayar-zakat/$KODE");
$cek('instruksi: status "Menunggu verifikasi" + alert konfirmasi', str_contains($i['body'], 'Menunggu verifikasi') && str_contains($i['body'], 'Konfirmasi sedang diverifikasi'));
$r = req('POST', "$base/konfirmasi", $isian($pdf, 'application/pdf'), true);
$kr2 = $pdo->query("SELECT bukti_pembayaran FROM konfirmasi_pembayaran WHERE kode_pembayaran = " . $pdo->quote($KODE) . " ORDER BY id_konfirmasi_pembayaran DESC")->fetchColumn();
$cek('konfirmasi PDF valid tersimpan sebagai .pdf', $r['kode'] === 303 && str_ends_with((string) $kr2, '.pdf') && is_file("$proyek/storage/bukti/$kr2"));

echo "\n== Kalkulator tanpa JS (hitung di server)\n";
$kal = fn (string $q) => html_entity_decode(req('GET', "$base/kalkulator-zakat?$q")['body']);
$h = $kal('jenis=penghasilan&hitung=1&gaji=20.000.000&bonus=0&harga_emas=2.000.000');
$cek('penghasilan 20 jt, emas 2 jt/g → wajib, Rp500.000', str_contains($h, 'Wajib zakat') && str_contains($h, 'Rp500.000') && str_contains($h, 'Rp14.166.667'));
$h = $kal('jenis=penghasilan&hitung=1&gaji=10.000.000&harga_emas=2.000.000');
$cek('penghasilan 10 jt → belum mencapai nishab', str_contains($h, 'Belum mencapai nishab'));
$h = $kal('jenis=emas&hitung=1&gram=100&harga_emas=2.000.000');
$cek('emas 100 g → Rp5.000.000', str_contains($h, 'Rp5.000.000'));
$h = $kal('jenis=perdagangan&hitung=1&modal=150.000.000&untung=40.000.000&piutang=10.000.000&utang=20.000.000&harga_emas=2.000.000');
$cek('perdagangan bersih 180 jt → Rp4.500.000', str_contains($h, 'Rp4.500.000'));
$h = $kal('jenis=fidyah&hitung=1&hari=3');
$cek('fidyah 3 hari → Rp60.000 + tautan bayar jenis=3&jumlah=3', str_contains($h, 'Rp60.000') && str_contains($h, 'jenis=3&jumlah=3&nominal=60000'));

echo "\n== Rate limit\n";
$kodeTerakhir = 0;
for ($n = 1; $n <= 12; $n++) {
    $t = token(req('GET', "$base/bayar-zakat")['body']);
    $kodeTerakhir = req('POST', "$base/bayar-zakat", ['_token' => $t, 'jenis' => 7, 'nominal' => '1'])['kode'];
    if ($kodeTerakhir === 429) break;
}
$cek('POST bayar berulang → 429 (maks 10 per 10 menit)', $kodeTerakhir === 429, "pada percobaan bayar ke-$n dalam loop");

echo "\n== Bersihkan data uji\n";
$ids = $pdo->query("SELECT id_pembayaran FROM pembayaran WHERE nama_lengkap LIKE " . $pdo->quote($NAMA . '%'))->fetchAll(PDO::FETCH_COLUMN);
$bukti = $ids ? $pdo->query('SELECT bukti_pembayaran FROM konfirmasi_pembayaran WHERE id_pembayaran IN (' . implode(',', array_map('intval', $ids)) . ')')->fetchAll(PDO::FETCH_COLUMN) : [];
foreach ($bukti as $b) { @unlink("$proyek/storage/bukti/$b"); }
if ($ids) {
    $pdo->exec('DELETE FROM konfirmasi_pembayaran WHERE id_pembayaran IN (' . implode(',', array_map('intval', $ids)) . ')');
    $pdo->exec('DELETE FROM pembayaran WHERE id_pembayaran IN (' . implode(',', array_map('intval', $ids)) . ')');
}
foreach (glob("$proyek/storage/cache/rl-*.json") ?: [] as $f) { unlink($f); }
foreach (array_reverse(glob("$proyek/storage/bukti/*/*") ?: []) as $d) { @rmdir($d); }
foreach (glob("$proyek/storage/bukti/*") ?: [] as $d) { @rmdir($d); }
$akhir = [(int) $pdo->query('SELECT COUNT(*) FROM pembayaran')->fetchColumn(), (int) $pdo->query('SELECT COUNT(*) FROM konfirmasi_pembayaran')->fetchColumn()];
$cek("data uji dihapus (pembayaran {$akhir[0]}, konfirmasi {$akhir[1]} = semula)", $akhir === $awal, count($ids) . ' pembayaran uji, ' . count($bukti) . ' file bukti dihapus');
array_map('unlink', glob("$tmp/*") ?: []);

echo $gagal === 0 ? "\nSEMUA LULUS\n" : "\n$gagal GAGAL\n";
