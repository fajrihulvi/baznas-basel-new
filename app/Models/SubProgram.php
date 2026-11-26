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

    /**
     * Semua Kegiatan BAZNAS (sub-program) urut pilar, untuk bagian "Kegiatan Kami" di /tentang.
     * SELECT s.* agar tetap jalan sebelum migrasi 018 (kolom deskripsi/foto belum ada → null).
     *
     * @return list<array{id: int, id_pilar: int, nama: string, slug: string, deskripsi: ?string, foto: ?string, foto_w: ?int, foto_h: ?int}>
     */
    public static function semua(): array
    {
        $baris = Database::fetchAll(
            'SELECT s.* FROM sub_program s
               JOIN kategori_program k ON k.id_kategori_program = s.id_kategori_program
              WHERE s.slug IS NOT NULL AND s.slug <> \'\'
              ORDER BY k.urutan, k.id_kategori_program, s.id_sub_program'
        );

        return array_map(static fn (array $b): array => [
            'id'        => (int) $b['id_sub_program'],
            'id_pilar'  => (int) $b['id_kategori_program'],
            'nama'      => (string) $b['sub_program'],
            'slug'      => (string) $b['slug'],
            'deskripsi' => isset($b['deskripsi']) && trim((string) $b['deskripsi']) !== '' ? (string) $b['deskripsi'] : null,
            'foto'      => $b['foto'] ?? null,
            'foto_w'    => isset($b['foto_w']) ? (int) $b['foto_w'] : null,
            'foto_h'    => isset($b['foto_h']) ? (int) $b['foto_h'] : null,
        ], $baris);
    }
}
