<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Testimoni
{
    public static function terbit(int $limit): array
    {
        return Database::fetchAll(
            "SELECT id, nama, profesi, foto, isi, rating FROM testimoni
              WHERE publish = 'Y' ORDER BY urutan, created_at DESC LIMIT ?",
            [$limit]
        );
    }
}
