<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Models\TautanRusak;
use Throwable;

/**
 * Pemeriksaan kesiapan server untuk Admin → Status Sistem (dipakai juga setelah deploy).
 * Setiap baris: label, nilai, status (ok | peringatan | galat), saran perbaikan.
 */
final class StatusSistemService
{
    /** Ekstensi PHP yang dipakai aplikasi. */
    private const EKSTENSI = ['pdo_mysql' => 'Database', 'mbstring' => 'Teks UTF-8', 'gd' => 'Olah gambar', 'fileinfo' => 'Cek jenis file unggahan', 'dom' => 'Sanitasi HTML editor'];

    /** Folder yang harus bisa ditulis PHP. */
    private const FOLDER_TULIS = ['storage/cache', 'storage/logs', 'storage/bukti']; // + folder uploads di document root

    /** @return array<string, list<array{label: string, nilai: string, status: string, saran: ?string}>> */
    public static function periksa(Request $request): array
    {
        return [
            'Aplikasi'   => self::aplikasi($request),
            'PHP'        => self::php(),
            'Penyimpanan' => self::penyimpanan(),
            'Database'   => self::database(),
            'Pemantauan' => self::pemantauan(),
        ];
    }

    private static function aplikasi(Request $request): array
    {
        $produksi = config('app.env') === 'production';
        $https = str_starts_with((string) config('app.url'), 'https://');
        $hsts = (int) config('app.hsts', 0);

        return [
            self::baris('Mode (APP_ENV)', (string) config('app.env'), $produksi ? 'ok' : 'peringatan', $produksi ? null : 'Di server publik isi APP_ENV=production.'),
            self::baris('Mode debug (APP_DEBUG)', config('app.debug') ? 'Aktif' : 'Mati', config('app.debug') ? ($produksi ? 'galat' : 'peringatan') : 'ok',
                config('app.debug') ? 'Matikan (APP_DEBUG=false) di server publik — detail error tidak boleh terlihat pengunjung.' : null),
            self::baris('Alamat situs (APP_URL)', (string) config('app.url'), $https ? 'ok' : ($produksi ? 'galat' : 'peringatan'), $https ? null : 'Pakai https:// di server publik.'),
            self::baris('Koneksi saat ini', $request->secure() ? 'HTTPS' : 'HTTP', $request->secure() ? 'ok' : ($produksi ? 'galat' : 'peringatan'),
                $request->secure() ? null : 'Aktifkan SSL (AutoSSL/Let’s Encrypt di cPanel).'),
            self::baris('Paksa HTTPS (APP_FORCE_HTTPS)', config('app.force_https') ? 'Aktif' : 'Mati', config('app.force_https') || !$produksi ? 'ok' : 'peringatan',
                config('app.force_https') ? null : 'Isi APP_FORCE_HTTPS=true setelah sertifikat SSL aktif.'),
            self::baris('HSTS (APP_HSTS)', $hsts > 0 ? 'max-age=' . $hsts : 'Mati', $hsts > 0 || !$produksi ? 'ok' : 'peringatan',
                $hsts > 0 ? null : 'Setelah HTTPS stabil beberapa hari, isi APP_HSTS=31536000.'),
        ];
    }

    private static function php(): array
    {
        $baris = [
            self::baris('Versi PHP', PHP_VERSION, version_compare(PHP_VERSION, '8.2.0', '>=') ? 'ok' : 'galat',
                version_compare(PHP_VERSION, '8.2.0', '>=') ? null : 'Pilih PHP 8.2 atau lebih baru (cPanel → Select PHP Version / MultiPHP Manager).'),
        ];
        foreach (self::EKSTENSI as $ext => $guna) {
            $ada = extension_loaded($ext);
            $baris[] = self::baris('Ekstensi ' . $ext, $ada ? 'Aktif' : 'Tidak ada', $ada ? 'ok' : 'galat', $ada ? null : "Aktifkan ekstensi {$ext} ({$guna}).");
        }
        $webp = function_exists('imagewebp');
        $baris[] = self::baris('GD dukungan WebP', $webp ? 'Ya' : 'Tidak', $webp ? 'ok' : 'peringatan', $webp ? null : 'Varian WebP tidak dibuat; gambar tetap tampil tetapi lebih berat.');

        $opcache = function_exists('opcache_get_status') && (bool) ini_get('opcache.enable');
        $baris[] = self::baris('OPcache', $opcache ? 'Aktif' : 'Mati', $opcache ? 'ok' : 'peringatan', $opcache ? null : 'Aktifkan ekstensi opcache — PHP jauh lebih cepat.');

        $unggah = ukuran_ini((string) ini_get('upload_max_filesize'));
        $post = ukuran_ini((string) ini_get('post_max_size'));
        $memori = ini_get('memory_limit') === '-1' ? PHP_INT_MAX : ukuran_ini((string) ini_get('memory_limit'));
        $baris[] = self::baris('upload_max_filesize', (string) ini_get('upload_max_filesize'), $unggah >= 11 * 1048576 ? 'ok' : 'peringatan',
            $unggah >= 11 * 1048576 ? null : 'Minimal 11M (PDF laporan hingga 10 MB).');
        $baris[] = self::baris('post_max_size', (string) ini_get('post_max_size'), $post >= 12 * 1048576 ? 'ok' : 'peringatan',
            $post >= 12 * 1048576 ? null : 'Minimal 12M.');
        $baris[] = self::baris('memory_limit', (string) ini_get('memory_limit'), $memori >= 256 * 1048576 ? 'ok' : 'peringatan',
            $memori >= 256 * 1048576 ? null : 'Disarankan 256M agar foto kamera HP besar bisa diolah.');
        $tampil = filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN);
        $baris[] = self::baris('display_errors', $tampil ? 'On' : 'Off', $tampil && config('app.env') === 'production' ? 'peringatan' : 'ok',
            $tampil ? 'Matikan display_errors di server publik (error tetap tercatat di storage/logs).' : null);

        return $baris;
    }

    private static function penyimpanan(): array
    {
        $baris = [];
        $folder = array_combine(self::FOLDER_TULIS, array_map('base_path', self::FOLDER_TULIS)) + ['uploads (document root)' => public_path('uploads')];
        foreach ($folder as $label => $path) {
            $bisa = is_dir($path) && is_writable($path);
            $baris[] = self::baris('Folder ' . $label, $bisa ? 'Bisa ditulis' : 'Tidak bisa ditulis', $bisa ? 'ok' : 'galat', $bisa ? null : 'Buat folder & beri izin tulis (755/775): ' . $path);
        }
        $baris[] = self::baris('Document root', public_path() === base_path('public') ? 'public/ di dalam proyek' : 'Terpisah: ' . public_path(), 'ok', null);
        foreach (glob(public_path('uploads/*'), GLOB_ONLYDIR) ?: [] as $dir) {
            if (!is_writable($dir)) {
                $baris[] = self::baris('Folder public/uploads/' . basename($dir), 'Tidak bisa ditulis', 'galat', 'Beri izin tulis (755/775).');
            }
        }
        $proteksi = is_file(public_path('uploads/.htaccess'));
        $baris[] = self::baris('Blokir PHP di uploads', $proteksi ? 'Ada (.htaccess)' : 'Tidak ada', $proteksi ? 'ok' : 'galat', $proteksi ? null : 'Salin public/uploads/.htaccess dari paket rilis.');

        $sisa = @disk_free_space(base_path());
        if ($sisa !== false) {
            $baris[] = self::baris('Ruang disk tersisa', number_format($sisa / 1073741824, 1, ',', '.') . ' GB', $sisa > 1073741824 ? 'ok' : 'peringatan',
                $sisa > 1073741824 ? null : 'Kurang dari 1 GB.');
        }

        return $baris;
    }

    private static function database(): array
    {
        try {
            $versi = (string) Database::fetchColumn('SELECT VERSION()');
            $sudah = array_column(Database::fetchAll('SELECT nama FROM migrasi'), 'nama');
        } catch (Throwable $e) {
            return [self::baris('Koneksi', 'Gagal', 'galat', 'Periksa DB_* di .env.')];
        }
        $file = array_map('basename', glob(base_path('database/migrations/*.sql')) ?: []);
        $belum = array_values(array_diff($file, $sudah));

        return [
            self::baris('Server', $versi, 'ok', null),
            self::baris('Migrasi', $belum === [] ? 'Semua sudah dijalankan' : count($belum) . ' belum: ' . implode(', ', $belum),
                $belum === [] ? 'ok' : 'peringatan', $belum === [] ? null : 'Jalankan php database/scripts/migrate.php (backup dulu; 005 hanya setelah admin lama dipensiunkan).'),
        ];
    }

    private static function pemantauan(): array
    {
        $galat = 0;
        foreach (glob(base_path('storage/logs/app-*.log')) ?: [] as $log) {
            if (filemtime($log) >= strtotime('-7 days')) {
                $galat += substr_count((string) file_get_contents($log), '] ERROR: ');
            }
        }
        try {
            $rusak = TautanRusak::hitung();
        } catch (Throwable) {
            $rusak = null;
        }

        return [
            self::baris('Error 7 hari terakhir', (string) $galat, $galat === 0 ? 'ok' : 'peringatan', $galat === 0 ? null : 'Lihat storage/logs/app-*.log (cPanel → File Manager).'),
            self::baris('Tautan rusak tercatat', $rusak === null ? 'Tabel belum ada' : (string) $rusak, $rusak === null ? 'peringatan' : 'ok',
                $rusak === null ? 'Jalankan migrasi 008.' : ($rusak > 0 ? 'Tinjau di /admin/tautan-rusak.' : null)),
        ];
    }

    private static function baris(string $label, string $nilai, string $status, ?string $saran): array
    {
        return ['label' => $label, 'nilai' => $nilai, 'status' => $status, 'saran' => $saran];
    }
}
