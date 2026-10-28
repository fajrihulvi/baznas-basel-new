<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** 5 pilar program (Basel Peduli/Cerdas/Sehat/Taqwa/Makmur). */
final class KategoriProgram
{
    /** @return list<array{id: int, nama: string, label: string, slug: string, deskripsi: ?string, ikon: ?string}> */
    public static function pilar(): array
    {
        return Database::fetchAll(
            'SELECT id_kategori_program AS id, kategori_program AS nama, label, slug, deskripsi, ikon
               FROM kategori_program
              WHERE slug IS NOT NULL
              ORDER BY urutan, id_kategori_program'
        );
    }
}
