<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Rekening
{
    /** @return list<array{id: int, nama_bank: string, nomor_rekening: string, atas_nama: string, logo: ?string}> */
    public static function all(): array
    {
        return Database::fetchAll(
            'SELECT id_rekening AS id, nama_bank, nomor_rekening, atas_nama, logo
               FROM rekening
              ORDER BY id_rekening'
        );
    }
}
