<?php

declare(strict_types=1);

namespace App\Core;

use GdImage;
use RuntimeException;

/**
 * Optimasi gambar dengan GD: re-encode (membuang metadata EXIF/GPS), sisi terpanjang maks 1600px,
 * dan varian WebP {nama}-240.webp, -480, -800, -1200. Varian tidak pernah diperbesar:
 * bila gambar asli lebih kecil, varian memakai lebar asli (helper img() memakai foto_w untuk srcset).
 * 240 untuk logo mitra, foto testimoni, dan thumbnail kecil.
 */
final class Image
{
    public const WIDTHS = [240, 480, 800, 1200];
    public const MAX_DIMENSION = 1600;

    private const JPEG_QUALITY = 82;
    private const WEBP_QUALITY = 78;

    /**
     * Proses $source menjadi $targetDir/$filename + varian WebP.
     *
     * @return array{width: int, height: int}
     */
    public static function optimize(string $source, string $targetDir, string $filename): array
    {
        [$img, $type] = self::muat($source);
        $img = self::fit($img, self::MAX_DIMENSION, self::MAX_DIMENSION);
        $info = [2 => $type];

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new RuntimeException("Tidak bisa membuat folder: {$targetDir}");
        }

        $target = rtrim($targetDir, '/') . '/' . $filename;
        imageinterlace($img, true); // JPEG progresif
        $saved = match ($info[2]) {
            IMAGETYPE_JPEG => imagejpeg($img, $target, self::JPEG_QUALITY),
            IMAGETYPE_WEBP => imagewebp($img, $target, self::WEBP_QUALITY),
            default        => self::keepAlpha($img) && imagepng($img, $target, 9),
        };
        if (!$saved) {
            throw new RuntimeException("Gagal menyimpan gambar: {$target}");
        }

        foreach (self::WIDTHS as $width) {
            $variant = self::fit($img, $width, PHP_INT_MAX);
            self::keepAlpha($variant);
            imagewebp($variant, rtrim($targetDir, '/') . '/' . self::variant($filename, $width), self::WEBP_QUALITY);
        }

        return ['width' => imagesx($img), 'height' => imagesy($img)];
    }

    /**
     * Encode ulang satu gambar menjadi WebP tanpa varian (mis. bukti transfer): membuang metadata
     * EXIF/GPS dan payload tersembunyi, sisi terpanjang maks $maks px.
     *
     * @return array{width: int, height: int}
     */
    public static function simpanWebp(string $source, string $target, int $maks = self::MAX_DIMENSION): array
    {
        [$img] = self::muat($source);
        $img = self::fit($img, $maks, $maks);
        self::keepAlpha($img);

        $folder = dirname($target);
        if (!is_dir($folder) && !mkdir($folder, 0750, true) && !is_dir($folder)) {
            throw new RuntimeException("Tidak bisa membuat folder: {$folder}");
        }
        if (!imagewebp($img, $target, 80)) {
            throw new RuntimeException("Gagal menyimpan gambar: {$target}");
        }

        return ['width' => imagesx($img), 'height' => imagesy($img)];
    }

    /**
     * Baca gambar (JPEG/PNG/WebP/GIF) sebagai truecolor, sudah diputar sesuai EXIF.
     *
     * @return array{0: GdImage, 1: int} gambar & konstanta IMAGETYPE_*
     */
    private static function muat(string $source): array
    {
        $info = @getimagesize($source);
        if ($info === false) {
            throw new RuntimeException("Bukan file gambar: {$source}");
        }

        $img = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG  => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            IMAGETYPE_GIF  => @imagecreatefromgif($source),
            default        => throw new RuntimeException("Format gambar tidak didukung: {$source}"),
        };
        if ($img === false) {
            throw new RuntimeException("Gagal membaca gambar: {$source}");
        }

        if (!imageistruecolor($img)) {
            imagepalettetotruecolor($img);
        }
        if ($info[2] === IMAGETYPE_JPEG) {
            $img = self::orient($img, $source);
        }

        return [$img, $info[2]];
    }

    /** "abc.jpg", 480 → "abc-480.webp" */
    public static function variant(string $filename, int $width): string
    {
        return pathinfo($filename, PATHINFO_FILENAME) . '-' . $width . '.webp';
    }

    /** Perkecil agar muat di $maxW × $maxH (tidak pernah memperbesar). */
    private static function fit(GdImage $img, int $maxW, int $maxH): GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $scale = min($maxW / $w, $maxH / $h, 1);
        if ($scale >= 1) {
            return $img;
        }

        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $resized = imagecreatetruecolor($nw, $nh);
        self::keepAlpha($resized);
        imagefill($resized, 0, 0, (int) imagecolorallocatealpha($resized, 0, 0, 0, 127));
        if (!imagecopyresampled($resized, $img, 0, 0, 0, 0, $nw, $nh, $w, $h)) {
            throw new RuntimeException('Gagal mengubah ukuran gambar');
        }

        return $resized;
    }

    /** Putar foto sesuai tag EXIF Orientation (foto HP), karena re-encode membuang tag tersebut. */
    private static function orient(GdImage $img, string $source): GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $img;
        }

        $exif = @exif_read_data($source);
        $rotated = match ((int) ($exif['Orientation'] ?? 1)) {
            3       => imagerotate($img, 180, 0),
            6       => imagerotate($img, -90, 0),
            8       => imagerotate($img, 90, 0),
            default => $img,
        };

        return $rotated === false ? $img : $rotated;
    }

    private static function keepAlpha(GdImage $img): bool
    {
        imagealphablending($img, false);

        return imagesavealpha($img, true);
    }
}
