<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Layanan
{
    public static function terbit(): array
    {
        return Database::fetchAll(
            "SELECT id, judul, deskripsi, ikon, link FROM layanan WHERE publish = 'Y' ORDER BY urutan, id"
        );
    }
}
