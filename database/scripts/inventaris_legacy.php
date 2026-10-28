<?php

/**
 * Fase 0 — Inventaris situs lama (baznasbasel.id).
 *
 * Menghasilkan:
 *   docs/fase-0/url-lama.csv   : URL lama → URL baru (usulan) untuk tabel `redirect` (Fase 2/5)
 *   docs/fase-0/file-lama.csv  : file upload konten publik + status ketersediaan di server lama
 *   docs/fase-0/duplikat.csv   : kandidat duplikat lintas tabel konten (slug/judul sama)
 * Mengunduh file konten publik ke $BACKUP_DIR (di luar proyek) bila dijalankan dengan --unduh.
 *
 * Data pribadi (bukti_pembayaran, foto user) sengaja tidak disentuh.
 *
 * Pemakaian: php database/scripts/inventaris_legacy.php [--unduh]
 */

declare(strict_types=1);

const OLD_BASE = 'https://baznasbasel.id';
const DB_DSN   = 'mysql:host=127.0.0.1;dbname=baznasba_baznas;charset=utf8mb4';
const DB_USER  = 'root';
const DB_PASS  = '';

$root      = dirname(__DIR__, 2);
$outDir    = $root . '/docs/fase-0';
$backupDir = getenv('HOME') . '/Backups/baznas-basel/uploads-lama';
$unduh     = in_array('--unduh', $argv, true);

$pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

function writeCsv(string $path, array $header, array $rows): void
{
    $fh = fopen($path, 'w');
    fputcsv($fh, $header, ',', '"', '');
    foreach ($rows as $row) {
        fputcsv($fh, $row, ',', '"', '');
    }
    fclose($fh);
}

// --- 1. URL lama → URL baru (usulan) ------------------------------------------

$pilarSlug = [1 => 'basel-peduli', 2 => 'basel-cerdas', 3 => 'basel-sehat', 4 => 'basel-taqwa', 5 => 'basel-makmur'];

$urls = [];
$add = static function (string $lama, string $baru, string $sumber, string $catatan = '') use (&$urls): void {
    $urls[$lama] = [$lama, $baru, $sumber, $catatan];
};

// Halaman statis (hasil crawl 28-10-2026)
$add('/', '/', 'crawl');
$add('/home/profil', '/tentang/profil', 'crawl');
$add('/home/hubungi_kami', '/halaman/kontak', 'crawl', 'Belum ada di peta route §6 — perlu halaman kontak');
$add('/home/mitra', '/#mitra', 'crawl', 'Tidak ada route /mitra di §6; arahkan ke section beranda atau buat halaman');
$add('/home/perundangan', '/regulasi', 'crawl');
$add('/home/laporan_keuangan', '/laporan', 'crawl');
$add('/home/layanan/rekening', '/rekening', 'crawl');
$add('/home/layanan/kalkulator', '/kalkulator-zakat', 'crawl');
$add('/home/layanan/kurban', '/halaman/kurban', 'crawl', 'Layanan kurban belum ada di rencana — perlu keputusan');
$add('/home/pembayaran/zakat', '/bayar-zakat', 'crawl', 'Query ?nominal= ikut dibawa');
$add('/home/pembayaran/konfirmasi', '/konfirmasi', 'crawl', 'Query ?kode= ikut dibawa');
$add('/home/pembayaran/keterangan', '/bayar-zakat/{kode}', 'crawl', 'Dinamis: ?kode=X → /bayar-zakat/X (tangani di Router, bukan tabel)');
$add('/home/kabar/berita', '/berita?jenis=berita', 'crawl');
$add('/home/kabar/artikel', '/berita?jenis=artikel', 'crawl');
$add('/home/kabar/berkah_zakat', '/berita?jenis=berkah_zakat', 'crawl');
$add('/home/kabar/event', '/berita?jenis=event', 'pola', 'Tabel event kosong');
$add('/home/kabar/video', '/video', 'crawl');

// Detail konten → /berita/{slug}
$konten = [
    'berita'       => ['id_berita', '/home/kabar/berita/detail/'],
    'artikel'      => ['id_artikel', '/home/kabar/artikel/detail/'],
    'berkah_zakat' => ['id_berkah_zakat', '/home/kabar/berkah_zakat/detail/'],
    'event'        => ['id_event', '/home/kabar/event/detail/'],
    'program'      => ['id_program', '/home/program/detail/'],
];
$semuaKonten = [];
foreach ($konten as $tabel => [$pk, $prefix]) {
    foreach ($pdo->query("SELECT {$pk} AS id, judul, slug, publish FROM {$tabel} ORDER BY {$pk}") as $r) {
        $semuaKonten[] = ['tabel' => $tabel] + $r;
        if ($r['slug'] === null || trim($r['slug']) === '') {
            continue;
        }
        $catatan = $r['publish'] === 'Y' ? '' : 'publish=N di situs lama';
        $add($prefix . $r['slug'], '/berita/' . $r['slug'], "db:{$tabel}#{$r['id']}", $catatan);
    }
}

// Sub program → halaman pilar
foreach ($pdo->query('SELECT id_sub_program, slug, id_kategori_program FROM sub_program') as $r) {
    $baru = isset($pilarSlug[(int) $r['id_kategori_program']]) ? '/program/' . $pilarSlug[(int) $r['id_kategori_program']] : '/program';
    $add('/home/program/' . $r['slug'], $baru, "db:sub_program#{$r['id_sub_program']}");
}

// Jenis zakat (/home/zakat/{id_jenis_zakat}) → /zakat/{slug deskripsi_zakat}, dicocokkan lewat nama
$deskripsi = [];
foreach ($pdo->query('SELECT slug, judul FROM deskripsi_zakat') as $r) {
    $deskripsi[normalisasi($r['judul'])] = $r['slug'];
}
foreach ($pdo->query('SELECT id_jenis_zakat, jenis_zakat FROM jenis_zakat') as $r) {
    $kunci = normalisasi($r['jenis_zakat']);
    $kunci = $kunci === 'zakat perusahan' ? 'zakat perusahaan' : $kunci; // typo di data lama
    $slug = $deskripsi[$kunci] ?? null;
    $add(
        '/home/zakat/' . $r['id_jenis_zakat'],
        $slug ? '/zakat/' . $slug : '/zakat',
        "db:jenis_zakat#{$r['id_jenis_zakat']}",
        $slug ? '' : 'Tidak ada deskripsi_zakat yang cocok'
    );
}

// Kategori perundangan → /regulasi?kategori={id}
foreach ($pdo->query('SELECT id_kategori_perundangan AS id FROM kategori_perundangan') as $r) {
    $add('/home/detail_perundangan/' . $r['id'], '/regulasi?kategori=' . $r['id'], "db:kategori_perundangan#{$r['id']}");
}

writeCsv($outDir . '/url-lama.csv', ['url_lama', 'url_baru_usulan', 'sumber', 'catatan'], array_values($urls));

// --- 2. Kandidat duplikat lintas tabel -----------------------------------------

$bySlug = [];
$byJudul = [];
foreach ($semuaKonten as $k) {
    $ref = "{$k['tabel']}#{$k['id']}";
    if ($k['slug']) {
        $bySlug[$k['slug']][] = $ref;
    }
    $byJudul[normalisasi($k['judul'])][] = $ref;
}
$duplikat = [];
foreach ($bySlug as $slug => $refs) {
    if (count($refs) > 1) {
        $duplikat[] = ['slug', $slug, implode(' ', $refs)];
    }
}
foreach ($byJudul as $judul => $refs) {
    if (count($refs) > 1) {
        $duplikat[] = ['judul', $judul, implode(' ', $refs)];
    }
}
writeCsv($outDir . '/duplikat.csv', ['berdasarkan', 'nilai', 'baris'], $duplikat);

// --- 3. File upload konten publik ---------------------------------------------

$sumberFile = [
    ['banner', 'id_banner', 'foto'],
    ['berita', 'id_berita', 'foto'],
    ['artikel', 'id_artikel', 'foto'],
    ['berkah_zakat', 'id_berkah_zakat', 'foto'],
    ['event', 'id_event', 'foto'],
    ['program', 'id_program', 'foto'],
    ['deskripsi_zakat', 'id_deskripsi_zakat', 'foto'],
    ['mitra', 'id_mitra', 'logo'],
    ['rekening', 'id_rekening', 'logo'],
    ['perundangan', 'id_perundangan', 'file'],
    ['laporan_keuangan', 'id_laporan_keuangan', 'file'],
];

$files = [];
foreach ($sumberFile as [$tabel, $pk, $kolom]) {
    foreach ($pdo->query("SELECT {$pk} AS id, {$kolom} AS f FROM {$tabel} WHERE {$kolom} IS NOT NULL AND {$kolom} <> ''") as $r) {
        $files[] = [$tabel, (int) $r['id'], $kolom, trim($r['f']), "/file/{$tabel}/" . trim($r['f'])];
    }
}
foreach ($pdo->query("SELECT logo FROM konfig WHERE logo IS NOT NULL AND logo <> ''") as $r) {
    $files[] = ['konfig', 1, 'logo', $r['logo'], '/images/' . $r['logo']];
}

$fileRows = [];
$hilang = 0;
foreach ($files as [$tabel, $id, $kolom, $nama, $path]) {
    $tujuan = "{$backupDir}/{$tabel}/{$nama}";
    $status = 'tidak-dicek';
    $bytes = $w = $h = '';

    if ($unduh) {
        if (!is_file($tujuan)) {
            if (!is_dir(dirname($tujuan))) {
                mkdir(dirname($tujuan), 0755, true);
            }
            $ch = curl_init(OLD_BASE . str_replace(' ', '%20', $path));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_USERAGENT      => 'BaznasBasel-Inventory/1.0 (redesign audit)',
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
            if ($code === 200 && is_string($body) && !str_starts_with($type, 'text/html')) {
                file_put_contents($tujuan, $body);
            }
            usleep(200000);
        }
        $status = is_file($tujuan) ? 'ada' : 'HILANG';
    }

    if (is_file($tujuan)) {
        $bytes = (string) filesize($tujuan);
        $info = @getimagesize($tujuan);
        if ($info !== false) {
            [$w, $h] = [(string) $info[0], (string) $info[1]];
        }
    }
    if ($status === 'HILANG') {
        $hilang++;
    }
    $fileRows[] = [$tabel, $id, $kolom, $nama, $path, $status, $bytes, $w, $h];
}
writeCsv($outDir . '/file-lama.csv', ['tabel', 'id', 'kolom', 'file', 'url_lama', 'status', 'bytes', 'lebar', 'tinggi'], $fileRows);

// --- Ringkasan -----------------------------------------------------------------

printf("URL lama        : %d → docs/fase-0/url-lama.csv\n", count($urls));
printf("Kandidat duplikat: %d → docs/fase-0/duplikat.csv\n", count($duplikat));
printf("File konten     : %d (hilang: %s) → docs/fase-0/file-lama.csv\n", count($fileRows), $unduh ? (string) $hilang : 'tidak dicek');
if ($unduh) {
    printf("Backup file     : %s\n", $backupDir);
}

function normalisasi(string $s): string
{
    return trim(preg_replace('/\s+/', ' ', mb_strtolower($s)));
}
