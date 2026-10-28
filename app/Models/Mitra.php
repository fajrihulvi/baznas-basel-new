<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Mitra
{
    public static function all(): array
    {
        return Database::fetchAll(
            'SELECT id_mitra AS id, TRIM(nama_mitra) AS nama, logo FROM mitra ORDER BY id_mitra'
        );
    }
}
