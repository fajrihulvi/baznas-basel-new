<?php

/**
 * Konsolidasi artikel, berita, berkah_zakat, program, event → tabel `post`.
 *
 * - Konten disanitasi (HtmlSanitizer), ringkasan dibuat otomatis, foto_w/foto_h dari public/uploads/post.
 * - Duplikat (slug atau judul sama lintas tabel) disimpan satu. Versi berita/artikel diutamakan
 *   dan mewarisi id_sub_program dari kembarannya di `program`, sehingga tetap tampil di halaman pilar.
 * - Semua URL lama konten → /berita/{slug} dicatat ke `redirect`, ditambah URL lama non-konten
 *   dari docs/fase-0/url-lama.csv.
 * - Mengisi video.youtube_id.
 *
 *   php database/scripts/migrate_posts.php           # sekali; menolak bila post hasil migrasi sudah ada
 *   php database/scripts/migrate_posts.php --ulang   # hapus post hasil migrasi lalu ulangi
 *
 * Jalankan setelah migrate.php dan generate_image_variants.php.
 * Laporan: docs/fase-2/laporan-migrasi.md
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Database;
use App\Core\Slug;
use App\Services\HtmlSanitizer;

/** tabel lama => [jenis post, kolom id, prefix URL lama detail] */
const SUMBER = [
    'berita'       => ['berita', 'id_berita', '/home/kabar/berita/detail/'],
    'artikel'      => ['artikel', 'id_artikel', '/home/kabar/artikel/detail/'],
    'berkah_zakat' => ['berkah_zakat', 'id_berkah_zakat', '/home/kabar/berkah_zakat/detail/'],
    'event'        => ['event', 'id_event', '/home/kabar/event/detail/'],
    'program'      => ['program', 'id_program', '/home/program/detail/'],
];

$sudahAda = (int) Database::fetchColumn('SELECT COUNT(*) FROM post WHERE legacy_table IS NOT NULL');
if ($sudahAda > 0 && !opsi('ulang')) {
    fwrite(STDERR, "Tabel post sudah berisi {$sudahAda} baris hasil migrasi. Pakai --ulang untuk menghapus dan mengulang.\n");
    exit(1);
}

$sanitizer = new HtmlSanitizer();
$uploadDir = public_path('uploads/post');

// --- 1. Kumpulkan semua baris lama ---------------------------------------------

$baris = [];
foreach (SUMBER as $tabel => [$jenis, $pk, $prefix]) {
    $kolomSub = $tabel === 'program' ? 'id_sub_program' : 'NULL';
    $rows = Database::fetchAll(
        "SELECT {$pk} AS id, judul, slug, deskripsi, foto, tgl, diposting, publish, {$kolomSub} AS id_sub_program
           FROM {$tabel} ORDER BY {$pk}"
    );
    foreach ($rows as $r) {
        $baris[] = $r + ['tabel' => $tabel, 'jenis' => $jenis, 'prefix' => $prefix];
    }
}

// --- 2. Kelompokkan duplikat (slug ATAU judul sama) dengan union-find ------------

$induk = array_keys($baris);
$cari = function (int $i) use (&$induk, &$cari): int {
    return $induk[$i] === $i ? $i : ($induk[$i] = $cari($induk[$i]));
};
$kunciTerlihat = [];
foreach ($baris as $i => $r) {
    $kunci = ['slug:' . trim((string) $r['slug']), 'judul:' . normal($r['judul'])];
    foreach ($kunci as $k) {
        if ($k === 'slug:') {
            continue;
        }
        if (isset($kunciTerlihat[$k])) {
            $induk[$cari($i)] = $cari($kunciTerlihat[$k]);
        } else {
            $kunciTerlihat[$k] = $i;
        }
    }
}
$grup = [];
foreach (array_keys($baris) as $i) {
    $grup[$cari($i)][] = $i;
}

// --- 3. Simpan ke post ------------------------------------------------------------

$laporan = ['per_jenis' => [], 'duplikat' => [], 'foto_hilang' => [], 'slug_diubah' => [], 'redirect' => 0];

Database::transaction(function () use ($grup, $baris, $sanitizer, $uploadDir, &$laporan): void {
    Database::execute('DELETE FROM post WHERE legacy_table IS NOT NULL');

    foreach ($grup as $anggota) {
        $rows = array_map(fn (int $i) => $baris[$i], $anggota);

        // Pemenang: bukan program > publish Y > konten terpanjang > id terkecil
        usort($rows, fn (array $a, array $b) => [$a['jenis'] === 'program', $a['publish'] !== 'Y', -strlen((string) $a['deskripsi']), $a['id']]
            <=> [$b['jenis'] === 'program', $b['publish'] !== 'Y', -strlen((string) $b['deskripsi']), $b['id']]);
        $p = $rows[0];

        $idSub = $p['id_sub_program'];
        foreach ($rows as $r) {
            $idSub ??= $r['id_sub_program'];
        }

        $slugLama = trim((string) $p['slug']);
        $slug = preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slugLama) && !slug_terpakai($slugLama)
            ? $slugLama
            : Slug::unique($slugLama !== '' ? $slugLama : $p['judul'], 'post');
        if ($slug !== $slugLama) {
            $laporan['slug_diubah'][] = "{$p['tabel']}#{$p['id']}: `{$slugLama}` → `{$slug}`";
        }

        $konten = $sanitizer->clean((string) $p['deskripsi']);
        [$fotoW, $fotoH] = [null, null];
        $foto = trim((string) $p['foto']) ?: null;
        if ($foto !== null) {
            $dim = @getimagesize("{$uploadDir}/{$foto}");
            if ($dim === false) {
                $laporan['foto_hilang'][] = "{$p['tabel']}#{$p['id']}: {$foto}";
            } else {
                [$fotoW, $fotoH] = [$dim[0], $dim[1]];
            }
        }

        Database::execute(
            'INSERT INTO post (jenis, judul, slug, ringkasan, konten, foto, foto_w, foto_h, id_sub_program, penulis,
                               tgl, publish, legacy_table, legacy_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $p['jenis'], trim($p['judul']), $slug, ringkasan($konten), $konten, $foto, $fotoW, $fotoH,
                $idSub, trim((string) $p['diposting']) ?: null, $p['tgl'], $p['publish'],
                $p['tabel'], $p['id'], $p['tgl'] . ' 00:00:00',
            ]
        );
        $laporan['per_jenis'][$p['jenis']] = ($laporan['per_jenis'][$p['jenis']] ?? 0) + 1;

        if (count($rows) > 1) {
            $lain = array_map(fn (array $r) => "{$r['tabel']}#{$r['id']}", array_slice($rows, 1));
            $laporan['duplikat'][] = "{$p['tabel']}#{$p['id']} disimpan (sub_program: " . ($idSub ?? '-') . '); digabung: ' . implode(', ', $lain);
        }

        foreach ($rows as $r) {
            if (trim((string) $r['slug']) !== '') {
                simpan_redirect($r['prefix'] . trim($r['slug']), '/berita/' . $slug);
                $laporan['redirect']++;
            }
        }
    }
});

// --- 4. Redirect URL lama non-konten (docs/fase-0/url-lama.csv) -------------------

$csv = base_path('docs/fase-0/url-lama.csv');
if (is_file($csv) && ($fh = fopen($csv, 'r')) !== false) {
    fgetcsv($fh, null, ',', '"', '');
    while (($row = fgetcsv($fh, null, ',', '"', '')) !== false) {
        [$dari, $ke, $sumber] = $row;
        $sumberKonten = (bool) preg_match('/^db:(berita|artikel|berkah_zakat|event|program)#/', $sumber);
        if ($dari === '/' || $sumberKonten || str_contains($ke, '{')) {
            continue;
        }
        simpan_redirect($dari, $ke);
        $laporan['redirect']++;
    }
    fclose($fh);
}

// --- 5. video.youtube_id ------------------------------------------------------------

$video = ['ok' => 0, 'gagal' => []];
foreach (Database::fetchAll('SELECT id_video, link FROM video') as $v) {
    $id = HtmlSanitizer::youtubeId((string) $v['link']);
    if ($id === null) {
        $video['gagal'][] = "video#{$v['id_video']}: {$v['link']}";
        continue;
    }
    Database::execute('UPDATE video SET youtube_id = ? WHERE id_video = ?', [$id, $v['id_video']]);
    $video['ok']++;
}

// --- 6. Laporan ------------------------------------------------------------------------

$totalLama = count($baris);
$totalPost = (int) Database::fetchColumn('SELECT COUNT(*) FROM post WHERE legacy_table IS NOT NULL');
$md = "# Laporan migrasi konten (Fase 2)\n\nDijalankan: " . date('Y-m-d H:i') . ' — database `' . config('database.name') . "`\n\n";
$md .= "| | Jumlah |\n|---|---|\n";
$md .= "| Baris di tabel lama | {$totalLama} |\n| Baris di `post` | {$totalPost} |\n";
foreach ($laporan['per_jenis'] as $jenis => $n) {
    $md .= "| · jenis `{$jenis}` | {$n} |\n";
}
$md .= '| Kelompok duplikat digabung | ' . count($laporan['duplikat']) . " |\n";
$md .= "| Redirect dicatat/diperbarui | {$laporan['redirect']} |\n";
$md .= "| video.youtube_id terisi | {$video['ok']} |\n\n";
$md .= daftar('Duplikat yang digabung', $laporan['duplikat']);
$md .= daftar('Foto tidak ditemukan di public/uploads/post', $laporan['foto_hilang']);
$md .= daftar('Slug yang diubah', $laporan['slug_diubah']);
$md .= daftar('Link video yang tidak dikenali', $video['gagal']);

if (!is_dir(base_path('docs/fase-2'))) {
    mkdir(base_path('docs/fase-2'), 0755, true);
}
file_put_contents(base_path('docs/fase-2/laporan-migrasi.md'), $md);
echo $md;

// --- helper ---------------------------------------------------------------------------

function normal(string $s): string
{
    return trim((string) preg_replace('/\s+/', ' ', mb_strtolower($s)));
}

function slug_terpakai(string $slug): bool
{
    return Database::fetchColumn('SELECT 1 FROM post WHERE slug = ?', [$slug]) !== null;
}

/** Teks polos ±160 karakter, dipotong di batas kata. */
function ringkasan(string $html): string
{
    $teks = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace('<', ' <', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    $teks = str_replace("\u{00A0}", ' ', $teks);
    if (mb_strlen($teks) <= 160) {
        return $teks;
    }
    $potong = mb_substr($teks, 0, 160);

    return rtrim(mb_substr($potong, 0, (int) mb_strrpos($potong, ' ')), " ,.;:-") . '…';
}

function simpan_redirect(string $dari, string $ke): void
{
    Database::execute(
        'INSERT INTO redirect (dari, ke) VALUES (?, ?) ON DUPLICATE KEY UPDATE ke = VALUES(ke)',
        [$dari, $ke]
    );
}

function daftar(string $judul, array $items): string
{
    if ($items === []) {
        return "## {$judul}\nTidak ada.\n\n";
    }

    return "## {$judul}\n" . implode('', array_map(fn ($i) => "- {$i}\n", $items)) . "\n";
}
