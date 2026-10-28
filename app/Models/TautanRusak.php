<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Alamat 404 yang dibuka pengunjung (pemantauan tautan rusak). */
final class TautanRusak
{
    /** Batas jumlah baris agar pemindai/bot tidak bisa membanjiri tabel. */
    public const MAKS_BARIS = 2000;

    public static function catat(string $path, ?string $referer): void
    {
        $sekarang = date('Y-m-d H:i:s');
        $ada = Database::execute(
            'UPDATE tautan_rusak SET hit = hit + 1, terakhir = ?, referer = COALESCE(?, referer) WHERE path = ?',
            [$sekarang, $referer, $path]
        );
        if ($ada > 0 || (int) Database::fetchColumn('SELECT COUNT(*) FROM tautan_rusak') >= self::MAKS_BARIS) {
            return;
        }
        Database::execute(
            'INSERT IGNORE INTO tautan_rusak (path, referer, pertama, terakhir) VALUES (?, ?, ?, ?)',
            [$path, $referer, $sekarang, $sekarang]
        );
    }

    public static function hapusPath(string $path): void
    {
        Database::execute('DELETE FROM tautan_rusak WHERE path = ?', [$path]);
    }

    public static function hitung(): int
    {
        return (int) Database::fetchColumn('SELECT COUNT(*) FROM tautan_rusak');
    }
}
