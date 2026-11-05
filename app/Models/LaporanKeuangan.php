<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class LaporanKeuangan
{
    /** @return list<array{id: int, judul: string, file: string, tahun: ?string, tgl: ?string}> file = PDF atau Excel (.xlsx/.xls) */
    public static function terbit(): array
    {
        return Database::fetchAll(
            "SELECT id_laporan_keuangan AS id, TRIM(judul) AS judul, file, tahun, tgl
               FROM laporan_keuangan WHERE publish = 'Y'
              ORDER BY tahun DESC, tgl DESC, id_laporan_keuangan DESC"
        );
    }
}
