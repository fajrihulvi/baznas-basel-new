<?php
// Uji warna/transparansi kotak ikon 5 pilar + Ikon Navigasi bawah + kartu Tunaikan Zakat (admin → situs). Pemakaian: php tests/http/uji_tampilan.php http://localhost:8002
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://localhost:8002', '/');
$proyek = dirname(__DIR__, 2);
$tmp = dirname(__DIR__) . '/tmp';
@mkdir($tmp);
$pdo = new PDO('mysql:host=127.0.0.1;dbname=baznas_basel;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$hapusCache = function () use ($proyek): void { foreach ([...glob("$proyek/storage/cache/*.cache") ?: [], ...glob("$proyek/storage/cache/rl-*.json") ?: []] as $f) unlink($f); };

// Simpan keadaan awal (dipulihkan di akhir)
$pilarAwal = $pdo->query('SELECT * FROM kategori_program WHERE id_kategori_program = 1')->fetch();
$layananAwal = $pdo->query('SELECT * FROM layanan WHERE id = 1')->fetch();
$waAwal = $pdo->query("SELECT nilai FROM setting WHERE kunci = 'whatsapp'")->fetchColumn();
$halamanAwal = $pdo->query('SELECT * FROM halaman WHERE id = 1')->fetch();
$mulaiUji = time() - 1;
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
    $nav = $nav[0] ?? '';
    $cek('bottom nav: bilah 80px, ikon 48px (2×), Bayar Zakat 80px & label dua baris', str_contains($nav, 'grid h-20') && substr_count($nav, 'size-12') >= 4
        && str_contains($nav, 'size-20') && str_contains($nav, 'Bayar<br>Zakat'));
    $cek('body memberi ruang 5rem untuk bilah bawah', str_contains(req($jP, 'GET', "$base/")['body'], 'pb-[calc(5rem+env(safe-area-inset-bottom))]'));

    echo "\n== Tunaikan Zakat (beranda)\n";
    $pdo->exec("INSERT INTO layanan (judul, deskripsi, link, urutan, publish) VALUES ('UJI-TP Kartu kelima', 'x', '/bayar-zakat', 99, 'Y')");
    $pdo->exec("UPDATE setting SET nilai = '6281234567890' WHERE kunci = 'whatsapp'");
    $hapusCache();
    $u = req($jA, 'GET', "$base/admin/layanan/1/ubah");
    $cek('form admin: Gambar, Judul, Deskripsi singkat, Tautan', str_contains($u['body'], 'name="gambar"') && str_contains($u['body'], 'name="judul"') && str_contains($u['body'], 'name="deskripsi"') && str_contains($u['body'], 'name="link"'));
    $g = imagecreatetruecolor(800, 600); imagefill($g, 0, 0, imagecolorallocate($g, 11, 107, 58)); imagejpeg($g, "$tmp/layanan-uji.jpg");
    $l1 = $pdo->query('SELECT * FROM layanan WHERE id = 1')->fetch();
    $r = req($jA, 'POST', "$base/admin/layanan/1", ['_token' => token($u['body']), 'judul' => $l1['judul'], 'deskripsi' => (string) $l1['deskripsi'], 'link' => $l1['link'],
        'urutan' => (string) $l1['urutan'], 'publish' => '1', 'gambar' => new CURLFile("$tmp/layanan-uji.jpg", 'image/jpeg', 'kartu.jpg')]);
    $gambar = (string) $pdo->query('SELECT gambar FROM layanan WHERE id = 1')->fetchColumn();
    $cek('unggah gambar kartu → uploads/layanan, ukuran tersimpan 800×600', $r['kode'] === 303 && $gambar !== '' && is_file("$proyek/public/uploads/layanan/$gambar")
        && (int) $pdo->query('SELECT gambar_w FROM layanan WHERE id = 1')->fetchColumn() === 800, $r['lokasi']);
    $h = req($jP, 'GET', "$base/")['body'];
    preg_match('#aria-labelledby="judul-layanan".*?</section>#s', $h, $sek);
    $sek = $sek[0] ?? '';
    $cek('beranda: tepat 4 kartu (kartu ke-5 tidak tampil), tanpa ikon lama', substr_count($sek, '<article') === 4 && !str_contains($sek, 'UJI-TP Kartu kelima') && !str_contains($sek, 'bg-accent/20'));
    $cek('gambar tampil di atas judul kartu pertama', (bool) preg_match('#<article.*?uploads/layanan/' . preg_quote(substr($gambar, 0, -4), '#') . '.*?<h3#s', $sek));
    $cek('Jemput Zakat: nomor & tautan WhatsApp dari Pengaturan', str_contains($sek, 'WhatsApp <span class="whitespace-nowrap">0812-3456-7890</span>') && str_contains($sek, 'https://wa.me/6281234567890?text='));

    preg_match('#<section id="mitra".*?</section>#s', $h, $mitra);
    $mitra = $mitra[0] ?? '';
    $cek('Mitra Kami: logo berwarna asli (tanpa grayscale), zoom in saat disorot', $mitra !== '' && !str_contains($mitra, 'grayscale')
        && str_contains($mitra, 'group-hover:scale-120') && str_contains($mitra, 'overflow-hidden'));

    echo "\n== Gambar di isi editor (Halaman)\n";
    $u = req($jA, 'GET', "$base/admin/halaman/1/ubah");
    $cek('editor isi: textarea membawa alamat unggah gambar', str_contains($u['body'], 'data-editor data-unggah="' . $base . '/admin/unggah-gambar"'));
    $unggah = function (string $file, string $mime, ?string $tok) use ($jA, $base): array {
        $ch = curl_init("$base/admin/unggah-gambar");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $jA, CURLOPT_COOKIEFILE => $jA, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['gambar' => new CURLFile($file, $mime, basename($file))], CURLOPT_HTTPHEADER => $tok !== null ? ["X-CSRF-Token: $tok", 'Accept: application/json'] : ['Accept: application/json']]);
        $body = (string) curl_exec($ch);
        return ['kode' => curl_getinfo($ch, CURLINFO_RESPONSE_CODE), 'json' => json_decode($body, true) ?? []];
    };
    $g = imagecreatetruecolor(1600, 900); imagefill($g, 0, 0, imagecolorallocate($g, 235, 104, 52)); imagejpeg($g, "$tmp/isi-uji.jpg");
    file_put_contents("$tmp/bukan-gambar.jpg", '<?php echo 1;');
    $r = $unggah("$tmp/isi-uji.jpg", 'image/jpeg', null);
    $cek('unggah tanpa token CSRF → 419', $r['kode'] === 419);
    $r = $unggah("$tmp/bukan-gambar.jpg", 'image/jpeg', token($u['body']));
    $cek('file bukan gambar (berkedok .jpg) → 422 + pesan', $r['kode'] === 422 && str_contains((string) ($r['json']['galat'] ?? ''), 'Format gambar'));
    $r = $unggah("$tmp/isi-uji.jpg", 'image/jpeg', token($u['body']));
    $src = (string) ($r['json']['src'] ?? '');
    $cek('unggah gambar → JSON src varian WebP 1200 px (tanpa domain), 1200×675', $r['kode'] === 200 && preg_match('#^/uploads/konten/[a-f0-9]{32}-1200\.webp$#', $src) === 1
        && ($r['json']['width'] ?? 0) === 1200 && ($r['json']['height'] ?? 0) === 675 && is_file("$proyek/public$src"), $src);
    $isi = '<p>Paragraf uji.</p><p><img src="' . $src . '" alt="Foto uji" width="1200" height="675"></p>';
    $r = req($jA, 'POST', "$base/admin/halaman/1", ['_token' => token($u['body']), 'judul' => $halamanAwal['judul'], 'grup' => $halamanAwal['grup'], 'konten' => $isi,
        'slug' => $halamanAwal['slug'], 'urutan' => (string) $halamanAwal['urutan'], 'publish' => $halamanAwal['publish'] === 'Y' ? '1' : '']);
    $tersimpan = (string) $pdo->query('SELECT konten FROM halaman WHERE id = 1')->fetchColumn();
    $cek('isi halaman tersimpan dengan <img> (lolos sanitasi, alt & ukuran tetap)', $r['kode'] === 303 && str_contains($tersimpan, 'src="' . $src . '"') && str_contains($tersimpan, 'alt="Foto uji"') && str_contains($tersimpan, 'width="1200"'));
    $hal = req($jP, 'GET', "$base/" . ($halamanAwal['grup'] === 'tentang' ? 'tentang' : 'halaman') . '/' . $halamanAwal['slug']);
    $cek('halaman publik menampilkan gambar (lazy)', $hal['kode'] === 200 && (bool) preg_match('#<img[^>]*src="' . preg_quote($src, '#') . '"[^>]*>#', $hal['body']) && str_contains($hal['body'], 'loading="lazy"'));

    echo "\n== Akses\n";
    $pdo->exec("UPDATE user SET level = 3 WHERE username = 'uji_tp_admin'");
    @unlink($jA); $f = req($jA, 'GET', "$base/admin/masuk");
    req($jA, 'POST', "$base/admin/masuk", ['_token' => token($f['body']), 'username' => 'uji_tp_admin', 'password' => $PASS]);
    $cek('Kepala: /admin/ikon-navigasi → 403', req($jA, 'GET', "$base/admin/ikon-navigasi")['kode'] === 403);
    $cek('Kepala: unggah gambar isi → 403', $unggah("$tmp/isi-uji.jpg", 'image/jpeg', token(req($jA, 'GET', "$base/admin")['body']) ?: 'x')['kode'] === 403);
} finally {
    $st = $pdo->prepare('UPDATE kategori_program SET warna_latar = ?, opasitas_latar = ?, label = ?, kategori_program = ?, deskripsi = ?, urutan = ? WHERE id_kategori_program = 1');
    $st->execute([$pilarAwal['warna_latar'], $pilarAwal['opasitas_latar'], $pilarAwal['label'], $pilarAwal['kategori_program'], $pilarAwal['deskripsi'], $pilarAwal['urutan']]);
    $st = $pdo->prepare('UPDATE ikon_nav SET label = ?, ikon = ? WHERE id = ?');
    foreach ($navAwal as $n) { $st->execute([$n['label'], $n['ikon'], $n['id']]); }
    $pdo->exec("DELETE FROM user WHERE username = 'uji_tp_admin'");
    $pdo->exec("DELETE FROM layanan WHERE judul LIKE 'UJI-TP %'");
    $pdo->prepare("UPDATE setting SET nilai = ? WHERE kunci = 'whatsapp'")->execute([$waAwal]);
    $sisaGambar = (string) $pdo->query('SELECT gambar FROM layanan WHERE id = 1')->fetchColumn();
    if ($sisaGambar !== '' && $sisaGambar !== (string) $layananAwal['gambar']) {
        foreach (glob("$proyek/public/uploads/layanan/" . pathinfo($sisaGambar, PATHINFO_FILENAME) . '*') ?: [] as $berkas) unlink($berkas);
    }
    $pdo->prepare('UPDATE layanan SET gambar = ?, gambar_w = ?, gambar_h = ? WHERE id = 1')->execute([$layananAwal['gambar'], $layananAwal['gambar_w'], $layananAwal['gambar_h']]);
    @rmdir("$proyek/public/uploads/layanan"); // hanya terhapus bila kosong
    @unlink("$tmp/layanan-uji.jpg");
    $pdo->prepare('UPDATE halaman SET konten = ? WHERE id = 1')->execute([$halamanAwal['konten']]);
    foreach (glob("$proyek/public/uploads/konten/*") ?: [] as $berkas) {
        if (filemtime($berkas) >= $mulaiUji) unlink($berkas); // hanya berkas yang dibuat uji ini
    }
    @rmdir("$proyek/public/uploads/konten");
    @unlink("$tmp/isi-uji.jpg"); @unlink("$tmp/bukan-gambar.jpg");
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
