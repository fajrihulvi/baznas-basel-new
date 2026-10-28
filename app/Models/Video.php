<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Video
{
    public static function terbaru(int $limit, int $offset = 0): array
    {
        return Database::fetchAll(
            'SELECT id_video AS id, judul, youtube_id, tgl FROM video
              WHERE youtube_id IS NOT NULL ORDER BY tgl DESC, id_video DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    public static function hitung(): int
    {
        return (int) Database::fetchColumn('SELECT COUNT(*) FROM video WHERE youtube_id IS NOT NULL');
    }
}
