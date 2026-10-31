<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Testimoni
{
    public const PERAN = ['muzaki' => 'Muzaki', 'mustahik' => 'Mustahik'];

    public static function terbit(int $limit): array
    {
        // no_hp sengaja tidak diambil: hanya untuk verifikasi admin
        return Database::fetchAll(
            "SELECT id, nama, profesi, peran, foto, isi, rating FROM testimoni
              WHERE publish = 'Y' ORDER BY urutan, created_at DESC LIMIT ?",
            [$limit]
        );
    }

    /** Kiriman pengunjung: selalu draf (publish N) sampai disetujui admin. */
    public static function kirim(array $d): int
    {
        Database::execute(
            "INSERT INTO testimoni (nama, profesi, peran, no_hp, isi, rating, publish, sumber, persetujuan)
             VALUES (?, ?, ?, ?, ?, ?, 'N', 'pengunjung', NOW())",
            [$d['nama'], $d['profesi'], $d['peran'], $d['no_hp'], $d['isi'], $d['rating']]
        );

        return Database::lastInsertId();
    }

    /** Jumlah kiriman pengunjung yang belum dimoderasi (dasbor admin). */
    public static function menunggu(): int
    {
        return (int) Database::fetchColumn("SELECT COUNT(*) FROM testimoni WHERE sumber = 'pengunjung' AND publish = 'N'");
    }
}
