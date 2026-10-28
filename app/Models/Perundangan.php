<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Regulasi zakat (perundangan + kategori_perundangan). */
final class Perundangan
{
    /** @return list<array{id: int, nama: string, jumlah: int}> */
    public static function kategori(): array
    {
        return Database::fetchAll(
            "SELECT k.id_kategori_perundangan AS id, TRIM(k.kategori_perundangan) AS nama, COUNT(p.id_perundangan) AS jumlah
               FROM kategori_perundangan k
               LEFT JOIN perundangan p ON p.id_kategori_perundangan = k.id_kategori_perundangan AND p.publish = 'Y'
              GROUP BY k.id_kategori_perundangan, k.kategori_perundangan
              ORDER BY k.id_kategori_perundangan"
        );
    }

    public static function terbit(?int $idKategori): array
    {
        $where = $idKategori !== null ? ' AND p.id_kategori_perundangan = ?' : '';

        return Database::fetchAll(
            "SELECT p.id_perundangan AS id, TRIM(p.judul) AS judul, p.file, p.id_kategori_perundangan AS id_kategori,
                    TRIM(k.kategori_perundangan) AS kategori
               FROM perundangan p
               JOIN kategori_perundangan k ON k.id_kategori_perundangan = p.id_kategori_perundangan
              WHERE p.publish = 'Y'{$where}
              ORDER BY p.id_kategori_perundangan, p.id_perundangan",
            $idKategori !== null ? [$idKategori] : []
        );
    }
}
