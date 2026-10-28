<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Pembatas percobaan berbasis file (storage/cache/rl-*.json), per kunci (mis. "bayar:{ip}").
 *
 *   if (RateLimiter::terlalu('bayar:' . $ip, 5, 600)) throw new HttpException(429);
 */
final class RateLimiter
{
    /** Catat satu percobaan; true bila jumlah dalam jendela waktu sudah melebihi $maks. */
    public static function terlalu(string $kunci, int $maks, int $detik): bool
    {
        $fh = fopen(self::file($kunci), 'c+');
        if ($fh === false) {
            return false; // gagal membuka file: jangan blokir pengguna
        }

        try {
            flock($fh, LOCK_EX);
            $data = json_decode((string) stream_get_contents($fh), true);
            $sekarang = time();
            if (!is_array($data) || ($data['reset'] ?? 0) <= $sekarang) {
                $data = ['jumlah' => 0, 'reset' => $sekarang + $detik];
            }
            $data['jumlah']++;

            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, (string) json_encode($data));

            return $data['jumlah'] > $maks;
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    public static function bersihkan(string $kunci): void
    {
        $file = self::file($kunci);
        if (is_file($file)) {
            unlink($file);
        }
    }

    private static function file(string $kunci): string
    {
        return base_path('storage/cache/rl-' . sha1($kunci) . '.json');
    }
}
