<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class JenisZakat
{
    /** @return list<array{id: int, nama: string, id_kategori: int, fidyah_perhari: ?int}> */
    public static function semua(): array
    {
        return Database::fetchAll(
            'SELECT id_jenis_zakat AS id, TRIM(jenis_zakat) AS nama, id_kategori_zakat AS id_kategori, fidyah_perhari
               FROM jenis_zakat ORDER BY id_jenis_zakat'
        );
    }
}
