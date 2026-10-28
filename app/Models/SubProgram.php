<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class SubProgram
{
    /** @return list<array{id: int, nama: string, slug: string}> */
    public static function perPilar(int $idKategori): array
    {
        return Database::fetchAll(
            'SELECT id_sub_program AS id, sub_program AS nama, slug FROM sub_program
              WHERE id_kategori_program = ? ORDER BY id_sub_program',
            [$idKategori]
        );
    }
}
