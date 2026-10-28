<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Galeri
{
    public const JENIS = ['foto', 'poster', 'infografis'];

    public static function terbit(?string $jenis, int $limit, int $offset): array
    {
        [$where, $params] = self::filter($jenis);

        return Database::fetchAll(
            "SELECT id, jenis, judul, foto, foto_w, foto_h, tgl FROM galeri WHERE publish = 'Y'{$where}
              ORDER BY tgl DESC, id DESC LIMIT ? OFFSET ?",
            [...$params, $limit, $offset]
        );
    }

    public static function hitung(?string $jenis): int
    {
        [$where, $params] = self::filter($jenis);

        return (int) Database::fetchColumn("SELECT COUNT(*) FROM galeri WHERE publish = 'Y'{$where}", $params);
    }

    /** @return array{0: string, 1: list<string>} */
    private static function filter(?string $jenis): array
    {
        return $jenis !== null && in_array($jenis, self::JENIS, true) ? [' AND jenis = ?', [$jenis]] : ['', []];
    }
}
