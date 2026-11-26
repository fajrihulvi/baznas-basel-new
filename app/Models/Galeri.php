<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Galeri
{
    public const JENIS = ['foto', 'poster', 'infografis'];

    /** @param string|null $cari kata dicari pada judul (halaman /arsip) */
    public static function terbit(?string $jenis, int $limit, int $offset, ?string $cari = null): array
    {
        [$where, $params] = self::filter($jenis, $cari);

        return Database::fetchAll(
            "SELECT id, jenis, judul, foto, foto_w, foto_h, tgl FROM galeri WHERE publish = 'Y'{$where}
              ORDER BY tgl DESC, id DESC LIMIT ? OFFSET ?",
            [...$params, $limit, $offset]
        );
    }

    public static function hitung(?string $jenis, ?string $cari = null): int
    {
        [$where, $params] = self::filter($jenis, $cari);

        return (int) Database::fetchColumn("SELECT COUNT(*) FROM galeri WHERE publish = 'Y'{$where}", $params);
    }

    /** @return array{0: string, 1: list<string>} */
    private static function filter(?string $jenis, ?string $cari = null): array
    {
        $where = '';
        $params = [];
        if ($jenis !== null && in_array($jenis, self::JENIS, true)) {
            $where .= ' AND jenis = ?';
            $params[] = $jenis;
        }
        if ($cari !== null && $cari !== '') {
            $where .= ' AND judul LIKE ?';
            $params[] = '%' . addcslashes($cari, '%_\\') . '%';
        }

        return [$where, $params];
    }
}
