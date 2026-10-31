<?php
// Uji form testimoni pengunjung (beranda & /testimoni/kirim) + moderasi admin. Pemakaian: php tests/http/uji_testimoni.php http://localhost:8002
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://localhost:8002', '/');
$proyek = dirname(__DIR__, 2);
$tmp = dirname(__DIR__) . '/tmp';
@mkdir($tmp);
$pdo = new PDO('mysql:host=127.0.0.1;dbname=baznas_basel;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$hapusRl = function () use ($proyek): void { foreach (glob("$proyek/storage/cache/rl-*.json") ?: [] as $f) unlink($f); };
$hapusCache = function () use ($proyek): void { foreach (glob("$proyek/storage/cache/*.cache") ?: [] as $f) unlink($f); };
$bersihkan = function () use ($pdo, $hapusRl, $hapusCache): void {
    $pdo->exec("DELETE FROM testimoni WHERE nama LIKE 'UJI-TS%'");
    $pdo->exec("DELETE FROM user WHERE username = 'uji_ts_admin'");
    $hapusRl();
    $hapusCache();
};
$bersihkan();
$jumlahAwal = (int) $pdo->query('SELECT COUNT(*) FROM testimoni')->fetchColumn();

function req(string $jar, string $method, string $url, array $data = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]);
    if ($method === 'POST') { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); }
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
$baris = fn (string $nama) => $pdo->query('SELECT * FROM testimoni WHERE nama = ' . $pdo->quote($nama))->fetch() ?: null;
$isian = ['asal' => 'beranda', 'peran' => 'mustahik', 'nama' => 'UJI-TS Ibu Aminah', 'profesi' => 'Penerima modal usaha, Payung', 'rating' => '5',
    'isi' => 'Alhamdulillah, bantuan modal usaha dari BAZNAS membuat warung saya bisa buka kembali.', 'no_hp' => '0812 3456 7890', 'setuju' => '1', 'website' => ''];
$jP = "$tmp/ts-pengunjung.txt";

try {
    echo "== Beranda\n";
    @unlink($jP);
    $h = req($jP, 'GET', "$base/");
    $cek('beranda 200, form "Bagikan pengalaman Anda" ada (terlipat)', $h['kode'] === 200 && str_contains($h['body'], 'id="kirim-testimoni"') && str_contains($h['body'], 'data-testimoni')
        && !preg_match('#<details id="kirim-testimoni"[^>]*\sopen#', $h['body']));
    $cek('beranda tetap TANPA cookie sesi (token belum dibuat)', !preg_match('/^set-cookie:/mi', $h['head']) && str_contains($h['body'], 'name="_token" value=""'));
    $cek('nomor WhatsApp tidak pernah ada di HTML publik', !str_contains($h['body'], '0812 3456 7890'));

    echo "\n== Token & pengiriman\n";
    $t = req($jP, 'GET', "$base/testimoni/token");
    $tok = (string) (json_decode($t['body'], true)['token'] ?? '');
    $cek('/testimoni/token → JSON token + cookie sesi, no-store', $t['kode'] === 200 && strlen($tok) === 64 && preg_match('/^set-cookie:/mi', $t['head']) === 1 && preg_match('/cache-control:[^\n]*no-store/i', $t['head']) === 1);
    $cek('kirim tanpa token → 419', req($jP, 'POST', "$base/testimoni/kirim", $isian)['kode'] === 419);

    $tolak = function (string $label, array $ubah, string $pesan) use ($jP, $base, $isian, $cek, $teks, $tok): void {
        $r = req($jP, 'POST', "$base/testimoni/kirim", ['_token' => $tok] + $ubah + $isian);
        $g = req($jP, 'GET', "$base/");
        $buka = (bool) preg_match('#<details id="kirim-testimoni"[^>]*\sopen#', $g['body']);
        $cek($label, $r['kode'] === 303 && str_ends_with($r['lokasi'], '/#kirim-testimoni') && $buka && str_contains($teks($g['body']), $pesan), $r['kode'] . ' ' . $r['lokasi']);
    };
    $tolak('tanpa memilih peran ditolak (form terbuka, pesan tampil)', ['peran' => ''], 'Anda sebagai wajib diisi');
    $tolak('cerita < 20 karakter ditolak', ['isi' => 'Bagus sekali.'], 'Cerita Anda minimal 20 karakter');
    $tolak('cerita berisi tautan ditolak (anti-spam)', ['isi' => 'Bagus sekali programnya, kunjungi https://contoh-spam.xyz untuk promo.'], 'tidak boleh berisi tautan');
    $tolak('tanpa centang persetujuan ditolak', ['setuju' => ''], 'Centang persetujuan');
    $tolak('nomor WhatsApp tidak valid ditolak', ['no_hp' => '12345'], 'Format Nomor WhatsApp tidak valid');
    $tolak('penilaian di luar 1–5 ditolak', ['rating' => '9'], 'Pilihan Penilaian tidak valid');
    $g = req($jP, 'POST', "$base/testimoni/kirim", ['_token' => $tok] + ['peran' => ''] + $isian);
    $g = req($jP, 'GET', "$base/");
    $cek('isian lama kembali setelah gagal (nama & cerita)', str_contains($g['body'], 'value="UJI-TS Ibu Aminah"') && str_contains($g['body'], 'warung saya bisa buka kembali'));
    $cek('tidak ada data tersimpan dari kiriman yang ditolak', $baris('UJI-TS Ibu Aminah') === null);

    $r = req($jP, 'POST', "$base/testimoni/kirim", ['_token' => $tok] + $isian);
    $b = $baris('UJI-TS Ibu Aminah');
    $cek('kiriman valid → 303 ke beranda #kirim-testimoni', $r['kode'] === 303 && str_ends_with($r['lokasi'], '/#kirim-testimoni'));
    $cek('tersimpan sebagai DRAF (publish N), sumber pengunjung, peran, no_hp, persetujuan', $b !== null && $b['publish'] === 'N' && $b['sumber'] === 'pengunjung'
        && $b['peran'] === 'mustahik' && $b['no_hp'] === '0812 3456 7890' && $b['persetujuan'] !== null && (int) $b['rating'] === 5);
    $g = req($jP, 'GET', "$base/");
    $cek('beranda menampilkan "Terima kasih…" (form terbuka)', str_contains($teks($g['body']), 'Terima kasih, testimoni Anda sudah kami terima'));
    $cek('belum dimoderasi → tidak tampil di beranda maupun /testimoni', !str_contains(req($jP, 'GET', "$base/")['body'], 'warung saya bisa buka kembali')
        && !str_contains(req($jP, 'GET', "$base/testimoni")['body'], 'UJI-TS'));

    $r = req($jP, 'POST', "$base/testimoni/kirim", ['_token' => $tok, 'nama' => 'UJI-TS Bot', 'website' => 'http://spam.example'] + $isian);
    $cek('honeypot terisi → tampak berhasil, tapi tidak disimpan', $r['kode'] === 303 && $baris('UJI-TS Bot') === null);

    echo "\n== Halaman /testimoni/kirim (tanpa JS) & batas kiriman\n";
    $jH = "$tmp/ts-halaman.txt"; @unlink($jH);
    $f = req($jH, 'GET', "$base/testimoni/kirim");
    $cek('halaman form 200 dengan token CSRF langsung', $f['kode'] === 200 && token($f['body']) !== '' && str_contains($f['body'], 'Bagikan Pengalaman Anda'));
    $r = req($jH, 'POST', "$base/testimoni/kirim", ['_token' => token($f['body']), 'asal' => 'halaman', 'nama' => 'UJI-TS Bapak Rudi', 'peran' => 'muzaki', 'profesi' => 'ASN'] + $isian);
    $g = req($jH, 'GET', "$base/testimoni/kirim");
    $cek('kirim dari halaman → kembali ke /testimoni/kirim + pesan terima kasih', $r['kode'] === 303 && str_ends_with($r['lokasi'], '/testimoni/kirim') && str_contains($teks($g['body']), 'Terima kasih') && $baris('UJI-TS Bapak Rudi') !== null);
    $cek('/testimoni menautkan ke form kirim', str_contains(req($jH, 'GET', "$base/testimoni")['body'], '/testimoni/kirim'));
    // Sudah 2 kiriman tersimpan dari IP ini (beranda + halaman); ke-3 masih boleh, ke-4 ditolak
    req($jP, 'POST', "$base/testimoni/kirim", ['_token' => $tok, 'nama' => 'UJI-TS Ketiga'] + $isian);
    $r = req($jP, 'POST', "$base/testimoni/kirim", ['_token' => $tok, 'nama' => 'UJI-TS Keempat'] + $isian);
    $g = req($jP, 'GET', "$base/");
    $cek('batas 3 kiriman tersimpan/jam per IP (ke-4 ditolak dengan pesan)', $baris('UJI-TS Ketiga') !== null && $baris('UJI-TS Keempat') === null && str_contains($teks($g['body']), 'Terlalu banyak kiriman'));

    echo "\n== Admin: moderasi\n";
    $PASS = 'Uji-Ts-' . bin2hex(random_bytes(4));
    $pdo->prepare("INSERT INTO user (username, password, f_name, l_name, level, status) VALUES ('uji_ts_admin', ?, 'Admin', 'Uji', 1, '1')")->execute([password_hash($PASS, PASSWORD_BCRYPT)]);
    $jA = "$tmp/ts-admin.txt"; @unlink($jA);
    $f = req($jA, 'GET', "$base/admin/masuk");
    req($jA, 'POST', "$base/admin/masuk", ['_token' => token($f['body']), 'username' => 'uji_ts_admin', 'password' => $PASS]);
    $d = $teks(req($jA, 'GET', "$base/admin")['body']);
    $cek('dasbor: pemberitahuan "3 testimoni baru menunggu moderasi"', str_contains($d, '3 testimoni baru menunggu moderasi'));
    $l = req($jA, 'GET', "$base/admin/testimoni");
    $pos = strpos($l['body'], 'UJI-TS'); $posTampil = strpos($l['body'], '>Tampil<');
    $cek('daftar admin: kiriman draf di atas, kolom Sebagai & sumber "pengunjung"', $pos !== false && ($posTampil === false || $pos < $posTampil) && str_contains($l['body'], 'pengunjung') && str_contains($teks($l['body']), 'Mustahik'));
    $u = req($jA, 'GET', "$base/admin/testimoni/{$b['id']}/ubah");
    $cek('form ubah admin menampilkan WhatsApp pengirim', str_contains($u['body'], 'value="0812 3456 7890"'));
    $r = req($jA, 'POST', "$base/admin/testimoni/{$b['id']}", ['_token' => token($u['body']), 'nama' => $b['nama'], 'peran' => 'mustahik', 'profesi' => $b['profesi'],
        'no_hp' => $b['no_hp'], 'isi' => $b['isi'], 'rating' => '5', 'urutan' => '0', 'publish' => '1']);
    $b2 = $baris('UJI-TS Ibu Aminah');
    $cek('admin menyetujui (Tampilkan di situs) → publish Y, sumber tetap pengunjung', $r['kode'] === 303 && $b2['publish'] === 'Y' && $b2['sumber'] === 'pengunjung');
    $g = req($jP, 'GET', "$base/");
    $cek('tampil di beranda: "Mustahik · Penerima modal usaha, Payung"', str_contains($teks($g['body']), 'UJI-TS Ibu Aminah') && str_contains($teks($g['body']), 'Mustahik · Penerima modal usaha, Payung'));
    $cek('nomor WhatsApp tetap tidak tampil di situs', !str_contains($g['body'], '0812 3456 7890') && !str_contains(req($jP, 'GET', "$base/testimoni")['body'], '0812 3456 7890'));
} finally {
    $bersihkan();
    foreach (glob("$tmp/ts-*.txt") ?: [] as $j) @unlink($j);
}
$cek('data kembali seperti semula', (int) $pdo->query('SELECT COUNT(*) FROM testimoni')->fetchColumn() === $jumlahAwal);
printf("\n%s — %d gagal\n", $gagal === 0 ? 'SEMUA LULUS' : 'ADA YANG GAGAL', $gagal);
exit($gagal === 0 ? 0 : 1);
