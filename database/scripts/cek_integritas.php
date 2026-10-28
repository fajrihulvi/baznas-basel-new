<?php

/**
 * Cek integritas file setelah deploy — mendeteksi kode yang diubah/disisipkan (mis. web shell).
 *
 *   php database/scripts/cek_integritas.php --buat   # buat integritas.json (dijalankan build-rilis.sh)
 *   php database/scripts/cek_integritas.php          # bandingkan dengan integritas.json + pindai folder unggahan
 *
 * Jadwalkan harian lewat cPanel → Cron Jobs; cron mengirim email bila ada keluaran.
 * Simpan juga salinan integritas.json di luar server (ikut di zip rilis) untuk pembanding tepercaya.
 * Kode keluar: 0 = bersih, 1 = ada temuan.
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

/** Folder kode yang dipantau (relatif terhadap akar proyek). public/uploads dikecualikan — isinya berubah. */
const PANTAU = ['app', 'config', 'routes', 'public', 'vendor', 'database/scripts', 'database/migrations'];
const KECUALI = ['public/uploads/'];
const MANIFEST = 'integritas.json';

/** @return array<string, string> path relatif => sha256 */
function hitung_hash(): array
{
    $hasil = [];
    foreach (PANTAU as $akar) {
        $dir = base_path($akar);
        if (!is_dir($dir)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $rel = substr($file->getPathname(), strlen(base_path()) + 1);
            foreach (KECUALI as $k) {
                if (str_starts_with($rel, $k)) {
                    continue 2;
                }
            }
            $hasil[$rel] = hash_file('sha256', $file->getPathname());
        }
    }
    ksort($hasil);

    return $hasil;
}

if (opsi('buat')) {
    $hash = hitung_hash();
    file_put_contents(base_path(MANIFEST), json_encode(['dibuat' => date('c'), 'file' => $hash], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    printf("%s dibuat: %d file.\n", MANIFEST, count($hash));
    exit(0);
}

$temuan = [];

// 1. Bandingkan kode dengan manifest rilis
$manifest = is_file(base_path(MANIFEST)) ? json_decode((string) file_get_contents(base_path(MANIFEST)), true) : null;
if (!is_array($manifest) || !is_array($manifest['file'] ?? null)) {
    $temuan[] = MANIFEST . ' tidak ada — jalankan dengan --buat dari kode yang tepercaya.';
} else {
    $sekarang = hitung_hash();
    foreach ($manifest['file'] as $rel => $hash) {
        if (!isset($sekarang[$rel])) {
            $temuan[] = "HILANG  {$rel}";
        } elseif ($sekarang[$rel] !== $hash) {
            $temuan[] = "BERUBAH {$rel}";
        }
    }
    foreach (array_diff_key($sekarang, $manifest['file']) as $rel => $_) {
        $temuan[] = "BARU    {$rel}";
    }
}

// 2. Folder unggahan hanya boleh berisi gambar/PDF/SVG bawaan — tidak boleh ada skrip
$bolehEkstensi = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'pdf'];
foreach (['public/uploads', 'storage/bukti'] as $akar) {
    $dir = base_path($akar);
    if (!is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $rel = substr($file->getPathname(), strlen(base_path()) + 1);
        $nama = $file->getFilename();
        if ($rel === 'public/uploads/.htaccess' || $nama === '.gitkeep') {
            continue;
        }
        $ext = strtolower($file->getExtension());
        $awal = (string) file_get_contents($file->getPathname(), false, null, 0, 1048576);
        if (!in_array($ext, $bolehEkstensi, true)) {
            $temuan[] = "MENCURIGAKAN {$rel} (ekstensi .{$ext})";
        } elseif (preg_match('/<\?php|<\?=\s*\$|<script\b|\b(eval|assert|system|passthru|shell_exec|base64_decode)\s*\(\s*\$/i', $awal)) {
            $temuan[] = "MENCURIGAKAN {$rel} (berisi kode)";
        }
    }
}

if ($temuan === []) {
    exit(0); // tanpa keluaran → cron tidak mengirim email
}
printf("Cek integritas %s — %d temuan:\n", date('Y-m-d H:i'), count($temuan));
foreach ($temuan as $t) {
    echo "  {$t}\n";
}
echo "\nBila perubahan bukan dari Anda: ganti password cPanel & admin, unggah ulang paket rilis, periksa storage/logs.\n";
exit(1);
