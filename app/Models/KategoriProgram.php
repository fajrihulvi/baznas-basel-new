<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** 5 pilar program (Basel Peduli/Cerdas/Sehat/Taqwa/Makmur). */
final class KategoriProgram
{
    /**
     * SELECT * agar tetap jalan sebelum migrasi 011 dijalankan (kolom warna belum ada → null).
     *
     * @return list<array{id: int, nama: string, label: string, slug: string, deskripsi: ?string, ikon: ?string, warna_latar: ?string, opasitas_latar: ?int}>
     */
    public static function pilar(): array
    {
        $baris = Database::fetchAll(
            'SELECT * FROM kategori_program WHERE slug IS NOT NULL ORDER BY urutan, id_kategori_program'
        );

        return array_map(static fn (array $b): array => [
            'id'             => (int) $b['id_kategori_program'],
            'nama'           => (string) $b['kategori_program'],
            'label'          => (string) $b['label'],
            'slug'           => (string) $b['slug'],
            'deskripsi'      => $b['deskripsi'],
            'ikon'           => $b['ikon'],
            'warna_latar'    => $b['warna_latar'] ?? null,
            'opasitas_latar' => isset($b['opasitas_latar']) ? (int) $b['opasitas_latar'] : null,
        ], $baris);
    }
}
