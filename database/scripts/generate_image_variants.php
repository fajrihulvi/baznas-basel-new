<?php

/**
 * Salin & optimasi file upload lama ke public/uploads/{folder}/ + varian WebP 480/800/1200.
 * Jalankan SEBELUM migrate_posts.php (dimensi foto post dibaca dari hasil skrip ini).
 *
 *   php database/scripts/generate_image_variants.php
 *   php database/scripts/generate_image_variants.php --sumber=/path/uploads-lama   # default ~/Backups/baznas-basel/uploads-lama
 *   php database/scripts/generate_image_variants.php --ulang                      # proses ulang walau sudah ada
 *
 * Struktur sumber: {tabel_lama}/{file} (hasil docs/fase-0 inventaris_legacy.php --unduh,
 * atau isi folder file/ dari backup cPanel).
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Image;

/** tabel lama → folder di public/uploads */
const FOLDER = [
    'berita'           => 'post',
    'artikel'          => 'post',
    'berkah_zakat'     => 'post',
    'program'          => 'post',
    'event'            => 'post',
    'banner'           => 'banner',
    'deskripsi_zakat'  => 'zakat',
    'mitra'            => 'mitra',
    'rekening'         => 'rekening',
    'perundangan'      => 'perundangan',
    'laporan_keuangan' => 'laporan',
];

$sumber = rtrim((string) opsi('sumber', getenv('HOME') . '/Backups/baznas-basel/uploads-lama'), '/');
$ulang  = (bool) opsi('ulang', false);

if (!is_dir($sumber)) {
    fwrite(STDERR, "Folder sumber tidak ada: {$sumber}\n");
    exit(1);
}

$stat = ['gambar' => 0, 'dokumen' => 0, 'dilewati' => 0, 'gagal' => 0, 'byte_lama' => 0, 'byte_baru' => 0];
$gagal = [];

foreach (FOLDER as $tabel => $folder) {
    $target = public_path("uploads/{$folder}");
    foreach (glob("{$sumber}/{$tabel}/*") ?: [] as $file) {
        $nama = basename($file);
        $ext  = strtolower(pathinfo($nama, PATHINFO_EXTENSION));
        $tujuan = "{$target}/{$nama}";

        try {
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                if (!$ulang && is_file($tujuan) && is_file($target . '/' . Image::variant($nama, 1200))) {
                    $stat['dilewati']++;
                    continue;
                }
                Image::optimize($file, $target, $nama);
                $stat['gambar']++;
                $stat['byte_lama'] += filesize($file);
                $stat['byte_baru'] += filesize($tujuan);
            } elseif ($ext === 'pdf') {
                if (!$ulang && is_file($tujuan)) {
                    $stat['dilewati']++;
                    continue;
                }
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
                copy($file, $tujuan);
                $stat['dokumen']++;
            } else {
                $gagal[] = "{$tabel}/{$nama}: ekstensi tidak didukung";
                $stat['gagal']++;
            }
        } catch (Throwable $e) {
            $gagal[] = "{$tabel}/{$nama}: " . $e->getMessage();
            $stat['gagal']++;
        }
    }
}

printf("Gambar diproses : %d (%s → %s untuk file utama)\n", $stat['gambar'], ukuran($stat['byte_lama']), ukuran($stat['byte_baru']));
printf("Dokumen disalin : %d\n", $stat['dokumen']);
printf("Dilewati (ada)  : %d\n", $stat['dilewati']);
printf("Gagal           : %d\n", $stat['gagal']);
foreach ($gagal as $baris) {
    echo "  - {$baris}\n";
}

exit($stat['gagal'] > 0 ? 1 : 0);

function ukuran(int $byte): string
{
    return $byte >= 1048576 ? round($byte / 1048576, 1) . ' MB' : round($byte / 1024) . ' KB';
}
