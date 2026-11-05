<?php

/**
 * Perawatan berkala — jadwalkan harian lewat cPanel → Cron Jobs, mis. pukul 02.00:
 *   php /home/USER/baznas-basel/database/scripts/rawat.php
 *
 * - cache & file sementara yang kedaluwarsa di storage/cache
 * - penghitung percobaan (rl-*.json) yang sudah lewat masanya
 * - log storage/logs/app-*.log lebih tua dari --log-hari (default 90)
 * - catatan tautan rusak yang tidak dibuka lagi > 180 hari
 * - sidik kunjungan berita hari sebelumnya (post_kunjungan)
 *
 *   php database/scripts/rawat.php --coba     # tampilkan saja, tidak menghapus
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Database;

$coba     = (bool) opsi('coba', false);
$logHari  = max(7, (int) opsi('log-hari', 90));
$sekarang = time();
$hapus    = [];

foreach (glob(base_path('storage/cache/*')) ?: [] as $file) {
    $nama = basename($file);
    if (str_ends_with($nama, '.tmp')) {
        $alasan = filemtime($file) < $sekarang - 3600 ? 'file sementara' : null;
    } elseif (str_ends_with($nama, '.cache')) {
        $isi = @unserialize((string) file_get_contents($file), ['allowed_classes' => false]);
        $alasan = !is_array($isi) || (($isi['expires'] ?? 0) > 0 && $isi['expires'] < $sekarang) ? 'cache kedaluwarsa' : null;
    } elseif (str_starts_with($nama, 'rl-') && str_ends_with($nama, '.json')) {
        $isi = json_decode((string) file_get_contents($file), true);
        $alasan = !is_array($isi) || ($isi['reset'] ?? 0) <= $sekarang ? 'penghitung percobaan' : null;
    } else {
        $alasan = null;
    }
    if ($alasan !== null) {
        $hapus[] = [$file, $alasan];
    }
}

foreach (glob(base_path('storage/logs/app-*.log')) ?: [] as $file) {
    if (filemtime($file) < $sekarang - $logHari * 86400) {
        $hapus[] = [$file, "log > {$logHari} hari"];
    }
}

$ringkas = [];
foreach ($hapus as [$file, $alasan]) {
    if (!$coba) {
        @unlink($file);
    }
    $ringkas[$alasan] = ($ringkas[$alasan] ?? 0) + 1;
}

$batas = date('Y-m-d H:i:s', $sekarang - 180 * 86400);
$tautan = $coba
    ? (int) Database::fetchColumn('SELECT COUNT(*) FROM tautan_rusak WHERE terakhir < ?', [$batas])
    : Database::execute('DELETE FROM tautan_rusak WHERE terakhir < ?', [$batas]);
if ($tautan > 0) {
    $ringkas['catatan tautan rusak > 180 hari'] = $tautan;
}

$hariIni = date('Y-m-d');
try {
    $kunjungan = $coba
        ? (int) Database::fetchColumn('SELECT COUNT(*) FROM post_kunjungan WHERE tgl < ?', [$hariIni])
        : App\Models\Post::hapusKunjunganLama($hariIni);
    if ($kunjungan > 0) {
        $ringkas['sidik kunjungan hari sebelumnya'] = $kunjungan;
    }
} catch (PDOException) {
    // tabel belum ada (migrasi 014 belum dijalankan)
}

printf("%s%s\n", $coba ? '[COBA — tidak ada yang dihapus] ' : '', date('Y-m-d H:i:s'));
if ($ringkas === []) {
    echo "Tidak ada yang perlu dibersihkan.\n";
}
foreach ($ringkas as $alasan => $jumlah) {
    printf("  %-34s %d\n", $alasan, $jumlah);
}
