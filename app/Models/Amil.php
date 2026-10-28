<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Amil
{
    public static function terbit(): array
    {
        return Database::fetchAll(
            "SELECT id, nama, jabatan, bidang, foto FROM amil WHERE publish = 'Y'
              ORDER BY FIELD(bidang, 'pimpinan', 'pelaksana'), urutan, id"
        );
    }
}
