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
            $dim = Image::optimize($file['tmp_name'], public_path('uploads/' . $folder), $nama);
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
        self::pindahkan($file['tmp_name'], public_path('uploads/' . $folder . '/' . $nama));

        return $nama;
    }

    /**
     * Dokumen Excel publik (laporan keuangan): public/uploads/{folder}/{acak}.xlsx|.xls
     * Dicek dari isinya: .xlsx = arsip ZIP berisi xl/workbook.xml tanpa makro (vbaProject.bin); .xls = berkas OLE lama.
     */
    public static function excelPublik(?array $file, string $folder, int $maksByte): string
    {
        self::periksa($file, $maksByte);
        $tmp = $file['tmp_name'];
        $awal = (string) file_get_contents($tmp, false, null, 0, 8);

        if (str_starts_with($awal, "PK\x03\x04")) {
            $zip = new \ZipArchive();
            $buka = $zip->open($tmp, \ZipArchive::RDONLY) === true;
            $xlsx = $buka && $zip->locateName('xl/workbook.xml') !== false;
            $makro = $xlsx && ($zip->locateName('xl/vbaProject.bin') !== false || str_contains((string) $zip->getFromName('[Content_Types].xml'), 'macroEnabled'));
            if ($buka) {
                $zip->close();
            }
            if (!$xlsx) {
                throw new UploadException('File harus berupa Excel (.xlsx atau .xls).');
            }
            if ($makro) {
                throw new UploadException('Excel bermakro (.xlsm) tidak diterima. Simpan sebagai Buku Kerja Excel (.xlsx) biasa.');
            }
            $ext = 'xlsx';
        } elseif ($awal === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
            $ext = 'xls'; // format Excel 97–2003
        } else {
            throw new UploadException('File harus berupa Excel (.xlsx atau .xls).');
        }

        $nama = bin2hex(random_bytes(16)) . '.' . $ext;
        self::pindahkan($tmp, public_path('uploads/' . $folder . '/' . $nama));

        return $nama;
    }

    /**
     * Dokumen publik PDF **atau** Excel dalam satu isian (laporan keuangan). Jenis dikenali dari isi file.
     *
     * @return string nama file acak berakhiran .pdf, .xlsx, atau .xls
     */
    public static function dokumenPublik(?array $file, string $folder, int $maksByte): string
    {
        self::periksa($file, $maksByte);
        if (file_get_contents($file['tmp_name'], false, null, 0, 5) === '%PDF-') {
            return self::pdfPublik($file, $folder, $maksByte);
        }
        try {
            return self::excelPublik($file, $folder, $maksByte);
        } catch (UploadException $e) {
            // Pesan khusus (mis. Excel bermakro) tetap; selain itu sebutkan kedua format yang diterima
            throw str_contains($e->getMessage(), 'makro') ? $e : new UploadException('File harus berupa PDF atau Excel (.xlsx/.xls).');
        }
    }

    /** Hapus file di public/uploads/{folder} beserta varian WebP-nya. */
    public static function hapusPublik(string $folder, ?string $nama): void
    {
        if ($nama === null || $nama === '' || basename($nama) !== $nama) {
            return;
        }
        $dir = public_path('uploads/' . $folder . '/');
        foreach ([$nama, ...array_map(static fn (int $w): string => Image::variant($nama, $w), Image::WIDTHS)] as $f) {
            if (is_file($dir . $f)) {
                unlink($dir . $f);
            }
        }
    }

    /**
     * File Excel .xlsx untuk diimpor (tidak disimpan — hanya dibaca dari folder sementara PHP).
     * Dicek dari isinya: arsip ZIP ("PK\x03\x04"), bukan dari nama/ekstensi kiriman browser.
     *
     * @return string path file sementara yang aman dibaca
     */
    public static function excel(?array $file, int $maksByte): string
    {
        $mime = self::periksa($file, $maksByte);
        $zip = in_array($mime, ['application/zip', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/octet-stream'], true);
        if (!$zip || file_get_contents($file['tmp_name'], false, null, 0, 4) !== "PK\x03\x04") {
            throw new UploadException('Format file harus Excel .xlsx (bukan .xls atau .csv). Di Excel: File → Simpan Sebagai → Buku Kerja Excel (.xlsx).');
        }

        return $file['tmp_name'];
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
