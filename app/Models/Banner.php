<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Banner
{
    public static function terbit(int $limit): array
    {
        return Database::fetchAll(
            "SELECT id_banner AS id, judul, foto, foto_mobile, link FROM banner
              WHERE publish = 'Y' ORDER BY urutan, tgl DESC, id_banner DESC LIMIT ?",
            [$limit]
        );
    }
}
