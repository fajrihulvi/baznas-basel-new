<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Penyimpanan unggahan yang aman (§10):
 * - jenis ditentukan dari isi file (finfo), bukan dari nama/ekstensi kiriman browser;
 * - gambar di-encode ulang (membuang EXIF/GPS & payload tersembunyi);
 * - PDF dicek tanda tangan "%PDF-";
 * - nama file acak; data pribadi disimpan di luar public/.
 *
 * Kesalahan yang perlu ditampilkan ke pengguna dilempar sebagai UploadException.
 */
final class Upload
{
    private const GAMBAR = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /**
     * Bukti transfer (data pribadi): gambar → WebP, atau PDF, di {folder}/YYYY/MM/.
     *
     * @param array{name: string, type: string, tmp_name: string, error: int, size: int}|null $file
     * @return string path relatif terhadap $folder, mis. "2026/10/3f…a1.webp"
     */
    public static function simpan(?array $file, string $folder, int $maksByte, bool $bolehPdf = true): string
    {
        $mime = self::periksa($file, $maksByte);
        $relatif = date('Y/m') . '/' . bin2hex(random_bytes(16));

        if (isset(self::GAMBAR[$mime])) {
            $relatif .= '.webp';
            try {
                Image::simpanWebp($file['tmp_name'], rtrim($folder, '/') . '/' . $relatif);
            } catch (RuntimeException) {
                throw new UploadException('Gambar tidak dapat dibaca. Gunakan file JPG, PNG, atau WebP.');
            }

            return $relatif;
        }

        if ($bolehPdf && self::pdfAsli($file['tmp_name'], $mime)) {
            $relatif .= '.pdf';
            self::pindahkan($file['tmp_name'], rtrim($folder, '/') . '/' . $relatif);

            return $relatif;
        }

        throw new UploadException($bolehPdf ? 'Format file harus JPG, PNG, WebP, atau PDF.' : 'Format file harus JPG, PNG, atau WebP.');
    }

    /**
     * Gambar konten publik: public/uploads/{folder}/{acak}.{ext} + varian WebP 240/480/800/1200.
     *
     * @return array{file: string, width: int, height: int}
     */
    public static function gambarPublik(?array $file, string $folder, int $maksByte): array
    {
        $mime = self::periksa($file, $maksByte);
        if (!isset(self::GAMBAR[$mime])) {
            throw new UploadException('Format gambar harus JPG, PNG, atau WebP.');
        }

        $nama = bin2hex(random_bytes(16)) . '.' . self::GAMBAR[$mime];
        try {
            $dim = Image::optimize($file['tmp_name'], base_path('public/uploads/' . $folder), $nama);
        } catch (RuntimeException) {
            throw new UploadException('Gambar tidak dapat dibaca. Gunakan file JPG, PNG, atau WebP.');
        }

        return ['file' => $nama, 'width' => $dim['width'], 'height' => $dim['height']];
    }

    /** Dokumen PDF publik (regulasi, laporan): public/uploads/{folder}/{acak}.pdf */
    public static function pdfPublik(?array $file, string $folder, int $maksByte): string
    {
        $mime = self::periksa($file, $maksByte);
        if (!self::pdfAsli($file['tmp_name'], $mime)) {
            throw new UploadException('File harus berupa PDF.');
        }

        $nama = bin2hex(random_bytes(16)) . '.pdf';
        self::pindahkan($file['tmp_name'], base_path('public/uploads/' . $folder . '/' . $nama));

        return $nama;
    }

    /** Hapus file di public/uploads/{folder} beserta varian WebP-nya. */
    public static function hapusPublik(string $folder, ?string $nama): void
    {
        if ($nama === null || $nama === '' || basename($nama) !== $nama) {
            return;
        }
        $dir = base_path('public/uploads/' . $folder . '/');
        foreach ([$nama, ...array_map(static fn (int $w): string => Image::variant($nama, $w), Image::WIDTHS)] as $f) {
            if (is_file($dir . $f)) {
                unlink($dir . $f);
            }
        }
    }

    public static function siapkanFolder(string $folder): void
    {
        if (!is_dir($folder) && !mkdir($folder, 0750, true) && !is_dir($folder)) {
            throw new RuntimeException("Tidak bisa membuat folder: {$folder}");
        }
    }

    /** Pemeriksaan umum; mengembalikan MIME dari isi file. */
    private static function periksa(?array $file, int $maksByte): string
    {
        $batas = batas_unggah($maksByte);
        $teksBatas = number_format($batas / 1048576, 1, ',', '.') . ' MB';

        if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
            throw new UploadException('File wajib diunggah.');
        }
        if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $file['size'] > $batas) {
            throw new UploadException("Ukuran file maksimal {$teksBatas}.");
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new UploadException('File gagal diunggah. Silakan coba lagi.');
        }

        return (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    }

    private static function pdfAsli(string $path, string $mime): bool
    {
        return $mime === 'application/pdf' && file_get_contents($path, false, null, 0, 5) === '%PDF-';
    }

    private static function pindahkan(string $tmp, string $tujuan): void
    {
        self::siapkanFolder(dirname($tujuan));
        if (!move_uploaded_file($tmp, $tujuan)) {
            throw new UploadException('File gagal disimpan. Silakan coba lagi.');
        }
    }
}
